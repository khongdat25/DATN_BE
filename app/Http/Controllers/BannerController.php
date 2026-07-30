<?php

namespace App\Http\Controllers;

use App\Models\Banners;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    // API cho Admin: Lấy tất cả Banner kèm bộ lọc
    public function adminIndex(Request $request)
    {
        $query = Banners::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%")
                  ->orWhere('link', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where(['status' => $request->status]);
        }

        $banners = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $banners
        ], 200);
    }

    // API cho FE Công khai: Lấy Banner đang hiển thị
    public function publicIndex()
    {
        $banners = Banners::query()
            ->where(['status' => 'active'])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $banners
        ], 200);
    }

    // API Admin: Thêm Banner mới
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|string',
            'position' => 'nullable|string',
            'link' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|string',
        ]);

        $banner = Banners::create([
            'title' => $request->title,
            'name' => $request->title,
            'image' => $request->image,
            'position' => $request->position ?? 'Trang chủ - Slider chính (Hero)',
            'link' => $request->link ?? '/products',
            'start_date' => $request->start_date ?? now()->toDateString(),
            'end_date' => $request->end_date ?? now()->addMonth()->toDateString(),
            'status' => $request->status ?? 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo banner mới thành công!',
            'data' => $banner
        ], 201);
    }

    // API Admin: Cập nhật Banner
    public function update(Request $request, int $id)
    {
        $banner = Banners::find($id, ['*']);
        if (! $banner) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy banner!'], 404);
        }

        $banner->update([
            'title' => $request->title ?? $banner->title,
            'name' => $request->title ?? $banner->name,
            'image' => $request->image ?? $banner->image,
            'position' => $request->position ?? $banner->position,
            'link' => $request->link ?? $banner->link,
            'start_date' => $request->start_date ?? $banner->start_date,
            'end_date' => $request->end_date ?? $banner->end_date,
            'status' => $request->status ?? $banner->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật banner thành công!',
            'data' => $banner
        ], 200);
    }

    // API Admin: Bật/Tắt Trạng thái
    public function toggleStatus(int $id)
    {
        $banner = Banners::find($id, ['*']);
        if (! $banner) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy banner!'], 404);
        }

        $newStatus = $banner->status === 'active' ? 'expired' : 'active';
        $banner->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công!',
            'status' => $newStatus,
            'data' => $banner
        ], 200);
    }

    // API Admin: Xóa Banner
    public function destroy(int $id)
    {
        $banner = Banners::find($id, ['*']);
        if (! $banner) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy banner!'], 404);
        }

        $banner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa banner thành công!'
        ], 200);
    }
}
