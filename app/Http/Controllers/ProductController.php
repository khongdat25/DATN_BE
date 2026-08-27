<?php

namespace App\Http\Controllers;

use App\Models\Banners;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Flashsale;
use App\Models\Product;
use App\Models\Variant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/products",
     *     summary="Danh sách tất cả sản phẩm (Công khai)",
     *     tags={"Sản phẩm (Product)"},
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function index()
    {
        $products = Product::query()
            ->where(function($q) {
                $q->where('status', 1)->orWhereNull('status');
            })
            ->with([
                'variants:id,product_id,size_id,color_id,sku,stock,price,sale_price,image',
                'brand:id,name',
                'category:id,name',
            ])
            ->withAvg('rating as avg_rating', 'rating')
            ->get(['id', 'slug', 'name', 'sold', 'brand_id', 'category_id', 'images', 'status']);

        return response()->json(
            [
                'success' => true,
                'message' => 'test all datas',
                'data' => $products,

            ], 200);
    }

    public function Banner()
    {
        $banners = Banners::take(3)->get(['id', 'name', 'image']);

        return response()->json(
            [
                'success' => true,
                'message' => 'Hero Banner',
                'data' => $banners,
            ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/getcategories",
     *     summary="Lấy danh sách rút gọn các danh mục",
     *     tags={"Sản phẩm (Product)"},
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function getCategories()
    {
        $category = Category::get(['id', 'name']);

        return response()->json(
            [
                'success' => true,
                'message' => 'lấy categories',
                'data' => $category,
            ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/getbrands",
     *     summary="Lấy danh sách rút gọn các thương hiệu",
     *     tags={"Sản phẩm (Product)"},
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function getBrands()
    {
        $brand = Brand::get(['id', 'name']);

        return response()->json(
            [
                'success' => true,
                'message' => 'lấy brands',
                'data' => $brand,
            ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/categories",
     *     summary="Lấy danh sách danh mục nổi bật",
     *     tags={"Sản phẩm (Product)"},
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function HotCategories()
    {
        $categories = Category::withCount('products')->take(6)->get(['name', 'img']);

        return response()->json(
            [
                'success' => true,
                'message' => 'Danh Mục Nổi Bật',
                'data' => $categories,
            ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/flashsales",
     *     summary="Danh sách sản phẩm Flash Sale đang diễn ra",
     *     tags={"Sản phẩm (Product)"},
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function FlashSale()
    {
        $now = Carbon::now();

        Flashsale::query()
            ->where('status', '!=', 3, 'and')
            ->where('end_time', '<', $now, 'and')
            ->update(['status' => 3]);

        $flashSales = Flashsale::query()
            ->with([
                'variants:id,product_id,size_id,color_id,sku,stock,price,sale_price,image,flash_sale_id',
                'variants.product:id,name,slug,category_id,brand_id,images',
                'variants.size:id,name',
                'variants.color:id,name',
            ])
            ->where('status', 1)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Flash Sale',
            'data' => $flashSales,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/bestsellings",
     *     summary="Danh sách sản phẩm bán chạy nhất",
     *     tags={"Sản phẩm (Product)"},
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function BestSelling()
    {
        $products = Product::query()->where(['status' => 1])
            ->with([
                'variants:product_id,image,price,sale_price',
                'category:id,name',
            ])
            ->withAvg('rating as avg_rating', 'rating')
            ->orderByRaw('sold desc')
            ->take(5)
            ->get(['id', 'name', 'slug', 'sold', 'category_id', 'images']);

        return response()->json(
            [
                'success' => true,
                'message' => 'Sản phẩm bán chạy',
                'data' => $products,
            ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/hotproducts",
     *     summary="Danh sách sản phẩm nổi bật (Hot Products)",
     *     tags={"Sản phẩm (Product)"},
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function HotProduct()
    {
        $products = Product::query()
            ->where('is_featured', 1)
            ->where('status', 1)
            ->with(['brand:id,name',
                'variants:product_id,size_id,price,sale_price',
                'variants.size:id,name',
                'category:id,name',
            ])
            ->withAvg('rating as avg_rating', 'rating')
            ->orderByRaw('sold desc')
            ->get(['id', 'name', 'slug', 'sold', 'category_id', 'brand_id', 'images', 'is_featured']);

        return response()->json(
            [
                'success' => true,
                'message' => 'Sản Phẩm Nổi Bật',
                'data' => $products,
            ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/product/{slug}",
     *     summary="Chi tiết sản phẩm theo Slug hoặc ID",
     *     tags={"Sản phẩm (Product)"},
     *     @OA\Parameter(name="slug", in="path", required=true, description="ID hoặc Slug sản phẩm", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Thành công"),
     *     @OA\Response(response=404, description="Không tìm thấy sản phẩm")
     * )
     */
    public function Detail(string $slug)
    {
        // Nếu tham số là số nguyên → tìm theo id, ngược lại tìm theo slug
        $query = Product::query()->with([
            'brand:id,name',
            'variants:id,product_id,image,price,sale_price,stock,color_id,size_id',
            'category:id,name',
            'variants.color:id,name',
            'variants.size:id,name',
            'rating' => function ($rq) {
                $rq->where('status', '!=', 'hidden')
                   ->select('id', 'product_id', 'rating', 'comment', 'reply', 'images', 'created_at', 'user_id');
            },
            'rating.user:id,name,avatar',
        ]);

        if (ctype_digit($slug)) {
            $product = $query->find((int) $slug);
        } else {
            $product = $query->where('slug', $slug)->first();
        }

        if (! $product) {
            return response()->json(['success' => false, 'message' => 'no product found'], 404);
        }

        $related = Product::query()->where([['id', '!=', $product->id]])
            ->where(['category_id' => $product->category_id])
            ->limit(4)
            ->with([
                'variants:product_id,size_id,color_id,sku,stock,price,sale_price,image',
                'brand:id,name',
                'category:id,name',
            ])
            ->withAvg('rating as avg_rating', 'rating')
            ->get(['id', 'slug', 'name', 'sold', 'brand_id', 'category_id', 'images']);
        $related = $related->sortBy(fn ($item) => $item->min_price)->values();
        $related->each(fn ($item) => $item->setRelation('variants', $item->variants->take(1)));

        if ($product->relationLoaded('variants')) {
            $product->setRelation('variants', $product->variants->sortBy(function ($v) {
                $sizeName = $v->size ? $v->size->name : ($v->size_id ?? '');
                $num = is_numeric($sizeName) ? (float) $sizeName : 999;
                return sprintf('%08.2f_%s', $num, $sizeName);
            })->values());
        }

        return response()->json(
            [
                'success' => true,
                'message' => 'chi tiết sp',
                'data' => ['product' => $product, 'related' => $related],
            ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/search",
     *     summary="Tìm kiếm & Lọc sản phẩm",
     *     tags={"Sản phẩm (Product)"},
     *     @OA\Parameter(name="q", in="query", required=false, description="Từ khóa tìm kiếm tên sản phẩm", @OA\Schema(type="string")),
     *     @OA\Parameter(name="category_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="brand_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="min_price", in="query", required=false, @OA\Schema(type="number")),
     *     @OA\Parameter(name="max_price", in="query", required=false, @OA\Schema(type="number")),
     *     @OA\Parameter(name="size", in="query", required=false, description="Kích thước (ví dụ 42)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="sort", in="query", required=false, description="Lọc: price_asc, price_desc, sold_desc, sold_asc, newest, oldest", @OA\Schema(type="string")),
     *     @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function Search(Request $request)
    {
        $products = Product::query()
            ->where(function($q) {
                $q->where('status', 1)->orWhereNull('status');
            })
            ->select(['id', 'name', 'slug', 'sold', 'category_id', 'brand_id', 'images', 'status'])
            ->with([
                'variants:id,product_id,size_id,color_id,sku,stock,price,sale_price,image',
                'variants.color:id,name',
                'variants.size:id,name',
                'brand:id,name',
                'category:id,name',
            ])
            ->withAvg('rating as avg_rating', 'rating')
            ->withMin('variants as min_price', 'price');
        if ($request->filled('q')) {
            $products->where([['name', 'like', '%'.$request->q.'%']]);
        }
        if ($request->filled('category_id')) {
            $products->where(['category_id' => $request->category_id]);
        }
        if ($request->filled('brand_id')) {
            $products->where(['brand_id' => $request->brand_id]);
        }
        if ($request->filled('min_price') && $request->filled('max_price')) {

            $products->havingBetween('min_price', [$request->min_price, $request->max_price]);
        } else {
            if ($request->filled('min_price')) {
                $products->having('min_price', '>=', $request->min_price);
            }
            if ($request->filled('max_price')) {
                $products->having('min_price', '<=', $request->max_price);
            }
        }

        if ($request->filled('size')) {
            $products->wherehas('variants.size',
                function ($query) use ($request) {
                    $query->where('name', $request->size);
                });
        }

        if ($request->filled('sort')) {
            if ($request->sort == 'price_asc') {
                $products->orderByRaw('min_price asc');
            }
            if ($request->sort == 'price_desc') {
                $products->orderByRaw('min_price desc');
            }
            if ($request->sort == 'sold_desc') {
                $products->orderByRaw('sold desc');
            }
            if ($request->sort == 'sold_asc') {
                $products->orderByRaw('sold asc');
            }
            if ($request->sort == 'newest') {
                $products->orderByRaw('id desc');
            }if ($request->sort == 'oldest') {
                $products->orderByRaw('id asc');
            }
        }
        if ($request->filled('limit')) {
            $products->limit((int) $request->limit);
        }

        return response()->json(
            [
                'success' => true,
                'message' => 'tìm kiếm sp thành công',
                'data' => $products->get(),
            ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/adminproduct",
     *     summary="[Admin] Danh sách tất cả sản phẩm kèm biến thể",
     *     tags={"Quản lý Sản phẩm (Admin Product)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="q", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="category_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="brand_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function admin_product(Request $request)
    {
        $products = Product::query()
            ->with([
                'variants:id,product_id,size_id,color_id,sku,stock,price,sale_price,image',
                'variants.color:id,name',
                'variants.size:id,name',
                'brand:id,name',
                'category:id,name',
            ])
            ->withCount('variants')
            ->withSum('variants as total_stock', 'stock')
            ->withMin('variants as min_price', 'price')
            ->withMax('variants as max_price', 'price');

        if ($request->filled('q')) {
            $products->where([['name', 'like', '%'.$request->q.'%']]);
        }
        if ($request->filled('category_id')) {
            $products->where(['category_id' => $request->category_id]);
        }
        if ($request->filled('brand_id')) {
            $products->where(['brand_id' => $request->brand_id]);
        }
        if ($request->filled('status')) {
            $products->where(['status' => $request->status]);
        }

        $data = $products->get();
        $data->each(function ($p) {
            if ($p->relationLoaded('variants')) {
                $p->setRelation('variants', $p->variants->sortBy(function ($v) {
                    $sizeName = $v->size ? $v->size->name : ($v->size_id ?? '');
                    $num = is_numeric($sizeName) ? (float) $sizeName : 999;
                    return sprintf('%08.2f_%s', $num, $sizeName);
                })->values());
            }
        });

        return response()->json(
            [
                'success' => true,
                'message' => 'all product for admin product page',
                'data' => $data,

            ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/product/{id}",
     *     summary="[Admin] Xóa sản phẩm",
     *     tags={"Quản lý Sản phẩm (Admin Product)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xóa thành công"),
     *     @OA\Response(response=400, description="Sản phẩm đang có đơn hàng xử lý")
     * )
     */
    public function product_delete(int $id)
    {
        $product = Product::find($id, ['*']);
        if (! $product) {
            return response()->json(['success' => false, 'message' => 'no product found'], 404);
        }

        $variantIds = $product->variants()->pluck('id');
        $hasActiveOrders = DB::table('order_item')
            ->join('orders', 'order_item.order_id', '=', 'orders.id')
            ->whereIn('order_item.variant_id', $variantIds)
            ->whereIn('orders.status', ['new', 'pending', 'shipping'])
            ->exists();

        if ($hasActiveOrders) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa sản phẩm này vì đang có đơn hàng chờ duyệt, chờ xử lý hoặc đang giao hàng chứa sản phẩm này!',
            ], 400);
        }

        $product->variants()->delete();
        $product->delete();

        return response()->json(
            [
                'success' => true,
                'message' => 'product deleted',
            ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/variant/{v}",
     *     summary="[Admin] Xóa biến thể sản phẩm",
     *     tags={"Quản lý Sản phẩm (Admin Product)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="v", in="path", required=true, description="Variant ID", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xóa thành công")
     * )
     */
    public function variant_delete(Variant $v)
    {
        // Kiểm tra xem biến thể có nằm trong đơn hàng đang xử lý/giao hàng không
        $hasActiveOrders = DB::table('order_item')
            ->join('orders', 'order_item.order_id', '=', 'orders.id')
            ->where('order_item.variant_id', '=', $v->id)
            ->whereIn('orders.status', ['new', 'pending', 'shipping'])
            ->exists();

        if ($hasActiveOrders) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa biến thể này vì đang có đơn hàng chờ duyệt, chờ xử lý hoặc đang giao hàng chứa nó!',
            ], 400);
        }

        Variant::destroy($v->id);

        return response()->json([
            'success' => true,
            'message' => 'variant deleted',
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/product_add",
     *     summary="[Admin] Thêm sản phẩm mới kèm danh sách biến thể",
     *     tags={"Quản lý Sản phẩm (Admin Product)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","category_id","brand_id","variants"},
     *             @OA\Property(property="name", type="string", example="Giày Nike Air Force 1"),
     *             @OA\Property(property="category_id", type="integer", example=1),
     *             @OA\Property(property="brand_id", type="integer", example=1),
     *             @OA\Property(property="description", type="string", example="Mô tả sản phẩm..."),
     *             @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="is_featured", type="boolean", example=true),
     *             @OA\Property(property="variants", type="array", @OA\Items(
     *                 @OA\Property(property="size_id", type="integer", example=1),
     *                 @OA\Property(property="color_id", type="integer", example=1),
     *                 @OA\Property(property="stock", type="integer", example=50),
     *                 @OA\Property(property="price", type="number", example=2500000),
     *                 @OA\Property(property="image", type="string", example=null)
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Tạo thành công")
     * )
     */
    public function product_add(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'category_id' => 'required|integer',
            'brand_id' => 'required|integer',
            'variants' => 'required|array',
            'images' => 'nullable|array',
        ]);

        $seenCombinations = [];
        foreach ($request->variants as $variant) {
            $sizeId = $variant['size_id'] ?? null;
            $colorId = $variant['color_id'] ?? null;
            if ($sizeId && $colorId) {
                $key = "{$sizeId}-{$colorId}";
                if (in_array($key, $seenCombinations)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Lỗi: Tồn tại các biến thể trùng lặp kích cỡ và màu sắc!',
                    ], 422);
                }
                $seenCombinations[] = $key;
            }
        }

        DB::beginTransaction();

        try {
            $product = Product::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'category_id' => $request->category_id,
                'brand_id' => $request->brand_id,
                'description' => $request->description,
                'images' => $request->images,
                'is_featured' => $request->is_featured ? 1 : 0,
                'status' => 1,
                'sold' => 0,
            ]);

            $word = Str::slug($request->name, ' ');
            $words = explode(' ', $word);
            $productCode = '';
            foreach ($words as $w) {
                if (! empty($w)) {
                    $productCode .= mb_substr($w, 0, 1, 'UTF-8');
                }
            }

            foreach ($request->variants as $variant) {
                $colorCode = $variant['color_code'] ?? 'CLR'.$variant['color_id'];
                $sizeCode = $variant['size_code'] ?? 'SZ'.$variant['size_id'];
                $autoSku = strtoupper($productCode.'-'.$colorCode.'-'.$sizeCode.Str::random(4));

                Variant::create([
                    'product_id' => $product->id,
                    'size_id' => $variant['size_id'],
                    'color_id' => $variant['color_id'],
                    'stock' => $variant['stock'] ?? 0,
                    'sku' => $autoSku,
                    'price' => $variant['price'],
                    'image' => $variant['image'] ?? null,
                    'status' => $variant['status'] ?? null,
                ]);
            }

            DB::commit();

            // Send new product notification to all users
            try {
                \App\Http\Controllers\NotificationController::sendToAllUsers(
                    "🔥 Siêu phẩm mới vừa cập bến: {$product->name}!",
                    "Bộ sưu tập SaigonShoes vừa ra mắt mẫu giày {$product->name} cực hot. Khám phá ngay!",
                    "system",
                    "/products"
                );
            } catch (\Exception $e) {
                // Ignore notification error if any
            }

            return response()->json([
                'success' => true,
                'message' => 'success',
                'data' => $product->load('variants'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'failed :(',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/product_edit/{id}",
     *     summary="[Admin] Cập nhật thông tin sản phẩm & biến thể",
     *     tags={"Quản lý Sản phẩm (Admin Product)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","category_id","brand_id","variants"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="category_id", type="integer"),
     *             @OA\Property(property="brand_id", type="integer"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="is_featured", type="boolean"),
     *             @OA\Property(property="variants", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cập nhật thành công")
     * )
     */
    public function product_edit(Request $request, int $id)
    {
        $request->validate([
            'name' => 'required|string',
            'category_id' => 'required|integer',
            'brand_id' => 'required|integer',
            'variants' => 'required|array',
            'images' => 'nullable|array',
        ]);
        $product = Product::find($id, ['*']);
        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy sản phẩm!'], 404);
        }

        $seenCombinations = [];
        foreach ($request->variants as $variant) {
            $sizeId = $variant['size_id'] ?? null;
            $colorId = $variant['color_id'] ?? null;
            if ($sizeId && $colorId) {
                $key = "{$sizeId}-{$colorId}";
                if (in_array($key, $seenCombinations)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Lỗi: Tồn tại các biến thể trùng lặp kích cỡ và màu sắc!',
                    ], 422);
                }
                $seenCombinations[] = $key;
            }
        }

        DB::beginTransaction();

        try {
            $product->update([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'category_id' => $request->category_id,
                'brand_id' => $request->brand_id,
                'description' => $request->description,
                'images' => $request->images,
                'is_featured' => $request->has('is_featured') ? ($request->is_featured ? 1 : 0) : $product->is_featured,
            ]);
            $word = Str::slug($request->name, ' ');
            $words = explode(' ', $word);
            $productCode = '';
            foreach ($words as $w) {
                if (! empty($w)) {
                    $productCode .= strtoupper(substr($w, 0, 1));
                }
            }
            $keepVariantIds = [];

            foreach ($request->variants as $variant) {
                $colorCode = $variant['color_code'] ?? 'CLR'.$variant['color_id'];
                $sizeCode = $variant['size_code'] ?? 'SZ'.$variant['size_id'];

                if (isset($variant['id']) && ! empty($variant['id'])) {
                    $existingVariant = Variant::find($variant['id'], ['*']);
                    if ($existingVariant) {
                        $existingVariant->update([
                            'size_id' => $variant['size_id'],
                            'color_id' => $variant['color_id'],
                            'stock' => $variant['stock'] ?? 0,
                            'price' => $variant['price'],
                            'sale_price' => $variant['sale_price'] ?? null,
                            'image' => $variant['image'] ?? $existingVariant->image,
                            'status' => $variant['status'] ?? $existingVariant->status,
                        ]);
                        $keepVariantIds[] = $existingVariant->id;
                    }
                } else {
                    do {
                        $autoSku = strtoupper($productCode.'-'.$colorCode.'-'.$sizeCode.'-'.Str::random(4));
                        $skuExists = Variant::query()->where('sku', '=', $autoSku, 'and')->exists();
                    } while ($skuExists);

                    $newVariant = Variant::create([
                        'product_id' => $product->id,
                        'size_id' => $variant['size_id'],
                        'color_id' => $variant['color_id'],
                        'stock' => $variant['stock'] ?? 0,
                        'sku' => $autoSku,
                        'price' => $variant['price'],
                        'sale_price' => $variant['sale_price'] ?? null,
                        'image' => $variant['image'] ?? null,
                        'status' => $variant['status'] ?? null,
                    ]);
                    $keepVariantIds[] = $newVariant->id;
                }
            }
            $variantsToDelete = Variant::query()
                ->where('product_id', '=', $product->id, 'and')
                ->whereNotIn('id', $keepVariantIds)
                ->get();

            foreach ($variantsToDelete as $vToDelete) {
                $hasActiveOrders = DB::table('order_item')
                    ->join('orders', 'order_item.order_id', '=', 'orders.id')
                    ->where('order_item.variant_id', '=', $vToDelete->id)
                    ->whereIn('orders.status', ['new', 'pending', 'shipping'])
                    ->exists();

                if ($hasActiveOrders) {
                    $sizeName = $vToDelete->size ? $vToDelete->size->name : $vToDelete->size_id;
                    $colorName = $vToDelete->color ? $vToDelete->color->name : $vToDelete->color_id;

                    return response()->json([
                        'success' => false,
                        'message' => "Không thể xóa biến thể (Size {$sizeName} - Màu {$colorName}) vì đang có đơn hàng chờ duyệt, chờ xử lý hoặc đang giao hàng chứa nó!",
                    ], 400);
                }
            }

            foreach ($variantsToDelete as $vToDelete) {
                $vToDelete->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật sản phẩm, danh mục và thương hiệu thành công!',
                'data' => $product->load('variants'),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Cập nhật thất bại!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/upload",
     *     summary="Tải ảnh sản phẩm lên server",
     *     tags={"Quản lý Sản phẩm (Admin Product)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="image", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Tải lên thành công")
     * )
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ], [
            'image.required' => 'Vui lòng chọn một file ảnh.',
            'image.image' => 'File phải là định dạng ảnh.',
            'image.mimes' => 'Định dạng ảnh không hợp lệ (hỗ trợ jpeg, png, jpg, gif, svg, webp).',
            'image.max' => 'Kích thước ảnh tối đa là 2MB.',
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time().'_'.Str::random(10).'.'.$file->getClientOriginalExtension();

            if (! file_exists(public_path('images'))) {
                mkdir(public_path('images'), 0755, true);
            }

            $file->move(public_path('images'), $filename);

            return response()->json([
                'success' => true,
                'message' => 'Tải ảnh lên thành công!',
                'filename' => $filename,
                'url' => url('images/'.$filename),
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Không tìm thấy file ảnh tải lên!',
        ], 400);
    }

    /**
     * @OA\Patch(
     *     path="/api/product/toggle-featured/{id}",
     *     summary="[Admin] Bật/tắt trạng thái nổi bật của sản phẩm",
     *     tags={"Quản lý Sản phẩm (Admin Product)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Cập nhật thành công")
     * )
     */
    public function toggleFeatured(int $id)
    {
        $product = Product::find($id, ['*']);
        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy sản phẩm!'], 404);
        }

        $product->update([
            'is_featured' => ! $product->is_featured,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái nổi bật thành công!',
            'is_featured' => $product->is_featured,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/product_import_excel",
     *     summary="[Admin] Nhập hàng loạt sản phẩm từ Excel",
     *     tags={"Quản lý Sản phẩm (Admin Product)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"products"},
     *             @OA\Property(property="products", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Nhập file Excel thành công")
     * )
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'products' => 'required|array|min:1',
            'products.*.name' => 'required|string',
            'products.*.category_id' => 'required|integer',
            'products.*.brand_id' => 'required|integer',
            'products.*.variants' => 'required|array|min:1',
        ]);

        $createdCount = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($request->products as $index => $prodData) {
                $rowNum = $index + 1;
                $name = trim($prodData['name']);
                if (empty($name)) {
                    $errors[] = "Dòng {$rowNum}: Tên sản phẩm không được để trống";
                    continue;
                }

                $catExists = Category::query()->where('id', '=', $prodData['category_id'], 'and')->exists();
                $brandExists = Brand::query()->where('id', '=', $prodData['brand_id'], 'and')->exists();

                if (! $catExists) {
                    $errors[] = "Dòng {$rowNum} ('{$name}'): Danh mục ID {$prodData['category_id']} không tồn tại";
                    continue;
                }
                if (! $brandExists) {
                    $errors[] = "Dòng {$rowNum} ('{$name}'): Thương hiệu ID {$prodData['brand_id']} không tồn tại";
                    continue;
                }

                $images = isset($prodData['images']) && is_array($prodData['images']) ? $prodData['images'] : [];

                $product = Product::create([
                    'name' => $name,
                    'slug' => Str::slug($name) . '-' . Str::random(4),
                    'category_id' => $prodData['category_id'],
                    'brand_id' => $prodData['brand_id'],
                    'description' => $prodData['description'] ?? '',
                    'images' => $images,
                    'is_featured' => ! empty($prodData['is_featured']) ? 1 : 0,
                    'status' => 1,
                    'sold' => 0,
                ]);

                $word = Str::slug($name, ' ');
                $words = explode(' ', $word);
                $productCode = '';
                foreach ($words as $w) {
                    if (! empty($w)) {
                        $productCode .= strtoupper(substr($w, 0, 1));
                    }
                }

                foreach ($prodData['variants'] as $vData) {
                    $sizeId = $vData['size_id'] ?? null;
                    $colorId = $vData['color_id'] ?? null;
                    $price = $vData['price'] ?? 0;
                    $stock = $vData['stock'] ?? 0;

                    if (! $sizeId || ! $colorId) continue;

                    $colorCode = 'CLR' . $colorId;
                    $sizeCode = 'SZ' . $sizeId;

                    do {
                        $autoSku = strtoupper($productCode . '-' . $colorCode . '-' . $sizeCode . '-' . Str::random(4));
                        $skuExists = Variant::query()->where('sku', '=', $autoSku, 'and')->exists();
                    } while ($skuExists);

                    Variant::create([
                        'product_id' => $product->id,
                        'size_id' => $sizeId,
                        'color_id' => $colorId,
                        'stock' => $stock,
                        'sku' => $autoSku,
                        'price' => $price,
                        'sale_price' => $vData['sale_price'] ?? null,
                        'image' => $vData['image'] ?? null,
                        'status' => 1,
                    ]);
                }

                $createdCount++;
            }

            if (count($errors) > 0 && $createdCount === 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi nhập dữ liệu từ Excel!',
                    'errors' => $errors,
                ], 422);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Đã nhập thành công {$createdCount} sản phẩm từ file Excel!",
                'created_count' => $createdCount,
                'warnings' => $errors,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Nhập file Excel thất bại!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function toggleStatus(int $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm!',
            ], 404);
        }

        $newStatus = ((int)$product->status === 1) ? 0 : 1;
        $product->status = $newStatus;
        $product->save();

        return response()->json([
            'success' => true,
            'message' => $newStatus === 1 ? 'Đã kích hoạt sản phẩm!' : 'Đã tạm khóa sản phẩm!',
            'status' => $newStatus,
        ], 200);
    }

    public function togglecate(int $id)
    {
        return $this->toggleStatus($id);
    }

}