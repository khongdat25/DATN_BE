<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\AiLog;
use App\Models\AiSuggestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAIController extends Controller
{
    /**
     * Lấy các số liệu thống kê hoạt động AI
     */
    public function getStats(Request $request)
    {
        $totalConversations = AiLog::count('*');
        $totalMessages = (int)AiLog::sum('messages_count');

        $recommendedCount = AiLog::whereNotNull('recommended_product_ids', 'and')
            ->where('recommended_product_ids', '!=', '[]', 'and')
            ->count('*');

        $positiveFeedbackCount = AiLog::where('feedback', '=', 'positive', 'and')->count('*');
        $feedbackTotal = AiLog::where('feedback', '!=', 'none', 'and')->count('*');
        $positiveRate = $feedbackTotal > 0 ? round(($positiveFeedbackCount / $feedbackTotal) * 100, 1) : 0;

        $totalTokens = (int)AiLog::sum('tokens_used');
        $estimatedCost = number_format(($totalTokens / 1000000) * 0.075, 4) . '$';

        // Breakdown categories / topics
        $categoryBreakdown = AiLog::select(['topic', DB::raw('count(*) as count')])
            ->groupBy('topic')
            ->get();

        $totalCat = $categoryBreakdown->sum('count');
        $categoryBreakdown = $categoryBreakdown->map(function ($item) use ($totalCat) {
            return [
                'label' => $item->topic,
                'count' => $item->count,
                'percentage' => $totalCat > 0 ? round(($item->count / $totalCat) * 100, 1) : 0,
            ];
        });

        $setting = AiSetting::first(['*']);

        return response()->json([
            'success' => true,
            'data' => [
                'total_conversations' => $totalConversations,
                'total_messages' => $totalMessages,
                'recommended_products_clicked' => $recommendedCount,
                'positive_feedback_rate' => $positiveRate,
                'token_usage_this_month' => $totalTokens,
                'estimated_cost_usd' => $estimatedCost,
                'active_status' => $setting ? (bool)$setting->is_enabled : true,
                'category_breakdown' => $categoryBreakdown,
            ]
        ]);
    }

    /**
     * Lấy cấu hình AI hiện tại
     */
    public function getSettings()
    {
        $setting = AiSetting::first(['*']);
        if (!$setting) {
            $setting = AiSetting::create([
                'assistant_name' => 'SaigonShoes AI Stylist',
                'ai_model' => 'Gemini 1.5 Flash',
                'temperature' => 0.7,
                'is_enabled' => true,
                'persona_style' => 'friendly',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $setting
        ]);
    }

    /**
     * Lưu/Cập nhật cấu hình AI
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'assistant_name' => 'required|string|max:255',
            'ai_model' => 'nullable|string',
            'temperature' => 'nullable|numeric|min:0.1|max:1.0',
            'is_enabled' => 'nullable|boolean',
            'persona_style' => 'nullable|string',
            'store_address' => 'nullable|string',
            'hotline' => 'nullable|string',
            'shipping_policy' => 'nullable|string',
            'system_prompt' => 'nullable|string',
            'size_chart_guide' => 'nullable|string',
        ]);

        $setting = AiSetting::first(['*']);
        if (!$setting) {
            $setting = new AiSetting();
        }

        $setting->fill($request->all());
        $setting->save();

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật cấu hình AI Assistant thành công!',
            'data' => $setting
        ]);
    }

    /**
     * Lấy danh sách nhật ký hội thoại (Chat Logs)
     */
    public function getLogs(Request $request)
    {
        $query = AiLog::query()->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('user_name', 'like', "%{$s}%")
                  ->orWhere('user_email', 'like', "%{$s}%")
                  ->orWhere('user_message', 'like', "%{$s}%")
                  ->orWhere('topic', 'like', "%{$s}%");
            });
        }

        if ($request->filled('topic') && $request->topic !== 'all') {
            $query->where('topic', $request->topic);
        }

        $perPage = $request->input('limit', 15);
        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Lấy danh sách nút gợi ý nhanh
     */
    public function getSuggestions()
    {
        $suggestions = AiSuggestion::orderBy('sort_order', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $suggestions
        ]);
    }

    /**
     * Cập nhật danh sách nút gợi ý nhanh
     */
    public function updateSuggestions(Request $request)
    {
        $request->validate([
            'suggestions' => 'required|array',
            'suggestions.*.text' => 'required|string',
        ]);

        AiSuggestion::truncate();

        foreach ($request->suggestions as $idx => $item) {
            AiSuggestion::create([
                'text' => $item['text'],
                'action' => $item['action'] ?? 'prompt',
                'active' => isset($item['active']) ? (bool)$item['active'] : true,
                'sort_order' => $idx + 1,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật danh sách nút gợi ý nhanh thành công!'
        ]);
    }
}
