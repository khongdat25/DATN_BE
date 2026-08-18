<?php

namespace App\Http\Controllers;

use App\Models\ProductModel;
use App\Models\AiSetting;
use App\Models\AiLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIChatController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/ai/chat",
     *     summary="Tư vấn AI Chatbot",
     *     description="Hỗ trợ tư vấn chọn size giày, gợi ý phong cách thời trang và tìm kiếm sản phẩm bằng Gemini AI",
     *     tags={"AI Chatbot"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"message"},
     *             @OA\Property(property="message", type="string", example="Chân mình dài 25.5cm thì đi size bao nhiêu?"),
     *             @OA\Property(property="history", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Phản hồi thành công từ AI",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="reply", type="string", example="Bảng quy đổi cho chiều dài chân 25.5 cm..."),
     *             @OA\Property(property="recommended_products", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array',
        ]);

        $userMessage = trim($request->input('message'));

        // Check if AI Chatbot is enabled in Admin Settings
        $setting = null;
        try {
            $setting = AiSetting::first(['*']);
        } catch (\Exception $e) {}

        if ($setting && isset($setting->is_enabled) && !$setting->is_enabled) {
            return response()->json([
                'success' => true,
                'reply' => 'Hệ thống SaigonShoes AI Assistant hiện đang được tạm dừng để bảo trì nâng cấp. Bạn vui lòng quay lại sau ít phút nhé! 👟✨',
                'recommended_products' => []
            ], 200);
        }

        $temperature = $setting->temperature ?? 0.7;
        $configuredPrompt = $setting->system_prompt ?? "Bạn là SaigonShoes AI Stylist - Trợ lý tư vấn chọn Size giày & Fashion Stylist cho cửa hàng SaigonShoes.";
        $sizeGuide = $setting->size_chart_guide ?? "";
        $shippingPolicy = $setting->shipping_policy ?? "";
        $hotline = $setting->hotline ?? "";
        $storeAddress = $setting->store_address ?? "";

        // 1. Lấy danh sách sản phẩm đang mở bán để làm ngữ cảnh dữ liệu cho AI
        $products = ProductModel::query()
            ->where('status', 1)
            ->with(['brand:id,name', 'category:id,name', 'variants:id,product_id,price,sale,image,size_id', 'variants.size:id,name'])
            ->withAvg('rating as avg_rating', 'rating')
            ->take(25)
            ->get(['id', 'name', 'slug', 'sold', 'category_id', 'brand_id', 'images', 'description']);

        $productListContext = $products->map(function ($p) {
            $minPrice = $p->variants->min('price') ?? 0;
            $sizes = $p->variants->map(fn($v) => $v->size->name ?? null)->filter()->unique()->values()->join(', ');
            $brand = $p->brand->name ?? 'SaigonShoes';
            $category = $p->category->name ?? 'Giày';
            return "• Mã sản phẩm #{$p->id}: {$p->name} - Hãng: {$brand} - Loại: {$category} - Giá: " . number_format($minPrice, 0, ',', '.') . "đ - Sizes có sẵn: {$sizes}";
        })->join("\n");

        // 2. Thiết lập System Prompt cho Chuyên gia tư vấn Chọn Size & Phối đồ
        $systemPrompt = <<<EOT
{$configuredPrompt}

HƯỚNG DẪN QUY ĐỔI SIZE:
{$sizeGuide}

THÔNG TIN CỬA HÀNG & CHÍNH SÁCH VẬN CHUYỂN:
- Địa chỉ shop: {$storeAddress}
- Hotline: {$hotline}
- Chính sách giao hàng & đổi trả: {$shippingPolicy}

DANH SÁCH SẢN PHẨM CỬA HÀNG:
{$productListContext}

QUY TẮC BẮT BUỘC:
- Trả lời bằng tiếng Việt tự nhiên, lịch sự, thân thiện, sáng tạo và chuẩn phong cách Stylist thời trang, dùng icon 👟✨.
- Nếu khách hỏi về concept chụp ảnh (ví dụ: chụp ảnh kỷ yếu học đường, phong cách học sinh cá biệt, phong cách Retro, Y2K, Streetwear, v.v.), hãy hào hứng tư vấn cách phối đồ và chọn mẫu giày cực ngầu phù hợp nhất với concept đó!
- TUYỆT ĐỐI KHÔNG in mã ID sản phẩm, không in "Mã sản phẩm #XX", không in ký tự kỹ thuật thô vào câu trả lời với khách hàng.
- Cuối câu trả lời, nếu có gợi ý sản phẩm cụ thể từ danh sách trên, thêm thẻ ẩn:
  [RECOMMENDED_PRODUCTS: id1, id2]

Câu hỏi từ khách hàng: {$userMessage}
EOT;

        // 3. Gọi Gemini API (Google AI) hoặc sử dụng phản hồi dự phòng
        $apiKey = env('GEMINI_API_KEY');
        if (empty($apiKey) && file_exists(base_path('.env'))) {
            $envContent = file_get_contents(base_path('.env'));
            if (preg_match('/GEMINI_API_KEY=(.+)/i', $envContent, $m)) {
                $apiKey = trim($m[1], " \"'\r\n");
            }
        }
        $replyText = '';
        $recommendedProductIds = [];
        $tokensUsed = 0;

        if (!empty($apiKey)) {
            try {
                $models = [
                    'gemini-3.5-flash',
                    'gemini-3.6-flash',
                    'gemini-3.1-flash-lite',
                    'gemini-flash-lite-latest'
                ];
                foreach ($models as $model) {
                    $response = Http::withoutVerifying()
                        ->timeout(120)
                        ->withHeaders([
                            'Content-Type' => 'application/json',
                        ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                            'contents' => [
                                [
                                    'parts' => [
                                        ['text' => $systemPrompt]
                                    ]
                                ]
                            ],
                            'generationConfig' => [
                                'temperature' => (float)$temperature,
                                'maxOutputTokens' => 2048,
                            ]
                        ]);

                    if ($response->successful()) {
                        $resData = $response->json();
                        $replyText = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';
                        $tokensUsed = $resData['usageMetadata']['totalTokenCount'] ?? 0;
                        if (!empty($replyText)) break;
                    } else {
                        Log::error("Gemini API ({$model}) Error: " . $response->body());
                    }
                }
            } catch (\Exception $e) {
                Log::error('Gemini API Exception: ' . $e->getMessage());
            }
        }

        // 4. Xử lý phản hồi dự phòng (Fallback) nếu chưa cấu hình API Key hoặc gọi API thất bại
        if (empty($replyText)) {
            $replyText = $this->generateFallbackReply($userMessage, $products, $recommendedProductIds);
        } else {
            // Trích xuất danh sách ID sản phẩm được tư vấn (nếu có)
            if (preg_match('/\[RECOMMENDED_PRODUCTS:\s*([0-9,\s]+)\]/i', $replyText, $matches)) {
                $ids = array_map('trim', explode(',', $matches[1]));
                $recommendedProductIds = array_filter($ids, 'is_numeric');
                // Lọc bỏ thẻ gợi ý sản phẩm khỏi văn bản phản hồi cho khách
                $replyText = trim(preg_replace('/\[RECOMMENDED_PRODUCTS:\s*([0-9,\s]+)\]/i', '', $replyText));
            }
            $replyText = trim($replyText);
        }

        // 5. Lấy thông tin chi tiết sản phẩm được gợi ý từ CSDL
        $recommendedProducts = [];
        if (!empty($recommendedProductIds)) {
            $recommendedProducts = ProductModel::whereIn('id', array_values($recommendedProductIds), 'and', false)
                ->with(['variants:id,product_id,price,sale,image'])
                ->get(['id', 'name', 'slug', 'images']);

            $recommendedProducts->transform(function ($item) {
                $firstVar = $item->variants->first();
                $img = null;
                if (!empty($item->images) && is_array($item->images)) {
                    $img = $item->images[0];
                } elseif ($firstVar && $firstVar->image) {
                    $img = $firstVar->image;
                }
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'slug' => $item->slug,
                    'price' => $firstVar ? ($firstVar->sale ?? $firstVar->price) : 0,
                    'image' => $img ? (str_starts_with($img, 'http') ? $img : url('images/' . $img)) : null,
                ];
            });
        }

        // 6. Ghi log lịch sử trò chuyện vào CSDL
        try {
            $user = auth('api')->user();
            $topic = 'Tư vấn chung';
            $lowerMsg = mb_strtolower($userMessage, 'UTF-8');
            if (str_contains($lowerMsg, 'size') || str_contains($lowerMsg, 'cm') || str_contains($lowerMsg, 'chân')) {
                $topic = 'Tư vấn Size';
            } elseif (str_contains($lowerMsg, 'phối đồ') || str_contains($lowerMsg, 'style') || str_contains($lowerMsg, 'streetwear')) {
                $topic = 'Phối đồ Style';
            } elseif (str_contains($lowerMsg, 'giảm giá') || str_contains($lowerMsg, 'khuyến mãi') || str_contains($lowerMsg, 'voucher') || str_contains($lowerMsg, 'giá')) {
                $topic = 'Giá & Khuyến mãi';
            }

            AiLog::create([
                'user_id' => $user ? $user->id : null,
                'user_name' => $user ? $user->name : 'Khách vương',
                'user_email' => $user ? $user->email : null,
                'user_phone' => $user ? $user->phone : null,
                'topic' => $topic,
                'messages_count' => 1,
                'user_message' => $userMessage,
                'bot_reply' => $replyText,
                'recommended_product_ids' => array_values($recommendedProductIds),
                'tokens_used' => $tokensUsed,
            ]);
        } catch (\Exception $e) {
            Log::error('AI Log creation failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'reply' => $replyText,
            'recommended_products' => $recommendedProducts,
        ], 200);
    }

    /**
     * Hàm tự động tạo câu trả lời dự phòng bằng thuật toán quy tắc khi chưa có AI Key hoặc chờ phản hồi
     */
    private function generateFallbackReply(string $msg, mixed $products, array &$recIds)
    {
        $lower = mb_strtolower($msg, 'UTF-8');

        // 1. Kiểm tra nếu khách hỏi về bảo vệ tốt nghiệp / trang phục công sở / sơ mi / quần tây / đi làm / lễ tốt nghiệp / phỏng vấn
        if (
            str_contains($lower, 'tốt nghiệp') || 
            str_contains($lower, 'bảo vệ') || 
            str_contains($lower, 'sơ mi') || 
            str_contains($lower, 'quần tây') || 
            str_contains($lower, 'quần âu') || 
            str_contains($lower, 'vest') || 
            str_contains($lower, 'công sở') || 
            str_contains($lower, 'phỏng vấn') || 
            str_contains($lower, 'lịch sự') ||
            str_contains($lower, 'trang trọng') ||
            (str_contains($lower, 'mặc') && (str_contains($lower, 'trắng') || str_contains($lower, 'đen')))
        ) {
            $recIds = $products->take(3)->pluck('id')->toArray();
            return "🎓 **Tư vấn phối giày đi Bảo vệ Tốt nghiệp / Trang phục Lịch sự (Sơ mi trắng + Quần tây đen):**\n\n" .
                   "• **Lựa chọn 1: Sneaker Trắng Tối Giản (Minimalist White Leather Sneaker)**\n" .
                   "  - Tạo phong cách trẻ trung, năng động nhưng vẫn cực kỳ chỉn chu, hiện đại. Thích hợp cho sinh viên vừa muốn thoải mái vừa ghi điểm lịch sự trước Hội đồng.\n\n" .
                   "• **Lựa chọn 2: Giày Tây Loafer / Derby Da Đen**\n" .
                   "  - Mang lại vẻ ngoài chững chạc, chuyên nghiệp và chuẩn quý ông 100% khi kết hợp với áo sơ mi trắng đóng thùng quần tây đen.\n\n" .
                   "• **Mẹo Stylist từ SaigonShoes:** Tránh chọn các đôi giày thể thao hầm hố có phối màu neon quá sặc sỡ để outfit bảo vệ tốt nghiệp chuẩn chỉnh nhất!\n\n" .
                   "Tham khảo ngay một số mẫu giày hợp outfit sơ mi & quần tây nhất bên dưới:";
        }

        // 2. Check if asking about explicit shoe sizes (size 36 -> 46, cỡ 43, chân 43, mua 44, v.v.)
        if (preg_match('/(size|cỡ|chân|mang|mua)\s*(\d{2})/i', $lower, $m) || preg_match('/(\d+(\.\d+)?)\s*(cm|mm)/i', $lower, $m)) {
            $recIds = $products->take(3)->pluck('id')->toArray();

            // Nếu người dùng nhắc đến tăng 1-2 size (ví dụ: cỡ 43 nhưng hay mua 44 hoặc 45)
            if (str_contains($lower, '44') || str_contains($lower, '45') || str_contains($lower, 'tăng') || str_contains($lower, 'rộng')) {
                return "👟 **Tư vấn chọn Size phù hợp cho phom chân của bạn:**\n\n" .
                       "• **Phân tích:** Bạn có cỡ chân chuẩn **Size 43**, nhưng thói quen hay mua **Size 44** hoặc **Size 45** cho thấy bạn có dáng bàn chân bè ngang, mu bàn chân dày hoặc thích cảm giác mang rộng rãi thoải mái.\n" .
                       "• **Lời khuyên từ SaigonShoes Stylist:**\n" .
                       "  - Với các mẫu Sneaker phom ôm (như Nike Air Max, Jordan, Puma): Chọn **Size 44** là vừa vặn êm chân nhất.\n" .
                       "  - Với các mẫu Chunky / Cổ cao (như MLB Chunky, High-top): Bạn có thể chọn **Size 44 hoặc 45** kèm lót giày êm để di chuyển thoải mái cả ngày!\n\n" .
                       "Gợi ý một số mẫu Sneaker phom rộng êm chân cực hot bên dưới:";
            }

            $size = 42;
            if (preg_match('/(\d{2})/', $lower, $sizeMatch)) {
                $size = (int)$sizeMatch[1];
            }

            return "👟 **Tư vấn chọn Size cho chân {$size}:**\n\n" .
                   "- **Size chuẩn khuyến nghị:** **Size {$size} (EU)**\n" .
                   "- **Mẹo chọn Size:** Nếu dáng bàn chân của bạn bè ngang hoặc mu dày, bạn nên nhích lên 0.5 - 1 Size (**Size " . ($size + 1) . "**) để di chuyển thoải mái nhất!\n\n" .
                   "Tham khảo ngay các mẫu giày hot đang có sẵn đủ Size tại SaigonShoes:";
        }

        // 3. Check if asking about Concept Styling (Kỷ yếu, Học sinh cá biệt, Streetwear, Vintage)
        if (str_contains($lower, 'kỷ yếu') || str_contains($lower, 'học đường') || str_contains($lower, 'cá biệt') || str_contains($lower, 'chụp ảnh') || str_contains($lower, 'streetwear') || str_contains($lower, 'phối đồ') || str_contains($lower, 'phong cách') || str_contains($lower, 'mặc') || str_contains($lower, 'mang')) {
            $recIds = $products->take(3)->pluck('id')->toArray();
            return "🔥 **Tư vấn Phong cách Thời trang & Phối đồ cực chất:**\n\n" .
                   "• **Phong cách Streetwear / Năng động:** Phối quần Jean/Cargo cùng **Sneaker Cổ cao (High-top)** hoặc **Chunky Sneaker** hầm hố để tạo phom dáng nổi bật và cực ngầu.\n" .
                   "• **Phong cách Smart Casual / Lịch sự:** Phối Quần Tây / Chino cùng **Sneaker Trắng cổ thấp** hoặc **Giày Loafer** thanh lịch.\n\n" .
                   "Tham khảo ngay các mẫu giày đang bán chạy nhất SaigonShoes bên dưới:";
        }

        // Default friendly response
        $recIds = $products->take(2)->pluck('id')->toArray();
        return "Chào bạn! SaigonShoes AI luôn sẵn sàng hỗ trợ bạn 👟✨\n" .
               "• Bạn có thể nhập chiều dài chân (ví dụ: *'chân 43 hay mua 44'*) để mình tư vấn **Size chuẩn xác nhất**.\n" .
               "• Hoặc yêu cầu tư vấn phối đồ phong cách (*'mặc sơ mi trắng mang giày gì'*, *'giày phong cách Streetwear'*...).";
    }
}
