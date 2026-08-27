<?php

namespace App\Http\Controllers;

use App\Models\Banners;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/banners",
     *     summary="[Admin] Danh sách tất cả Banners",
     *     description="Lấy danh sách Banners phục vụ quản trị, hỗ trợ lọc theo trạng thái và tìm kiếm",
     *     tags={"Quản lý Banner (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="search", in="query", required=false, description="Từ khóa tìm kiếm tiêu đề, vị trí, liên kết", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, description="Trạng thái (active/expired/all)", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Thành công", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="data", type="array", @OA\Items(type="object"))))
     * )
     */
    public function adminIndex(Request $request)
    {
        $query = Banners::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereNested(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%")
                  ->orWhere('link', 'like', "%{$search}%");
            }, 'and');
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', '=', $request->status, 'and');
        }

        $banners = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $banners
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/banner",
     *     summary="Lấy danh sách Banner đang hiển thị (Công khai)",
     *     description="Trả về danh sách Banner có trạng thái active để hiển thị trên trang chủ",
     *     tags={"Quản lý Banner (Admin)"},
     *     @OA\Response(response=200, description="Thành công", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="data", type="array", @OA\Items(type="object"))))
     * )
     */
    public function publicIndex()
    {
        $banners = Banners::query()
            ->where('status', '=', 'active', 'and')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $banners
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/banners",
     *     summary="[Admin] Tạo Banner mới",
     *     tags={"Quản lý Banner (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","image"},
     *             @OA\Property(property="title", type="string", example="Khuyến mãi Mùa Hè"),
     *             @OA\Property(property="image", type="string", example="banner_summer.jpg"),
     *             @OA\Property(property="position", type="string", example="Trang chủ - Slider chính (Hero)"),
     *             @OA\Property(property="link", type="string", example="/products"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2026-08-01"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2026-09-01"),
     *             @OA\Property(property="status", type="string", example="active")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Tạo thành công", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Tạo banner mới thành công!")))
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/api/admin/banners/{id}",
     *     summary="[Admin] Cập nhật thông tin Banner",
     *     tags={"Quản lý Banner (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="image", type="string"),
     *             @OA\Property(property="position", type="string"),
     *             @OA\Property(property="link", type="string"),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date"),
     *             @OA\Property(property="status", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cập nhật thành công")
     * )
     */
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

    /**
     * @OA\Patch(
     *     path="/api/admin/banners/{id}/toggle",
     *     summary="[Admin] Bật/Tắt trạng thái Banner",
     *     tags={"Quản lý Banner (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Cập nhật trạng thái thành công")
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/api/admin/banners/{id}",
     *     summary="[Admin] Xóa Banner",
     *     tags={"Quản lý Banner (Admin)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xóa thành công")
     * )
     */
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
