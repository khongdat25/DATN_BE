<?php

namespace App\Http\Controllers;

use App\Models\Banners;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BannerController extends Controller
{
    /**
     * Lấy danh sách banner đang hiển thị (Public).
     */
    public function index(Request $request)
    {
        $query = Banners::where('is_active', true);

        // Sắp xếp theo thứ tự hiển thị (position) tăng dần, sau đó đến tin mới nhất
        $banners = $query->orderBy('position', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách banner thành công.',
            'data' => $banners
        ], 200);
    }

    /**
     * Lấy chi tiết một banner theo ID (Public / Admin).
     */
    public function show($id)
    {
        $banner = Banners::find($id);

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy banner!'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết banner thành công.',
            'data' => $banner
        ], 200);
    }

    /**
     * Lấy tất cả banner cho trang Admin (Phân trang, tìm kiếm, lọc trạng thái).
     */
    public function adminIndex(Request $request)
    {
        $query = Banners::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $query->orderBy('position', 'asc')->orderBy('created_at', 'desc');

        $limit = $request->input('limit', 10);
        $banners = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách banner quản trị thành công.',
            'data' => $banners
        ], 200);
    }

    /**
     * Tạo mới Banner (Admin).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'image'       => 'required', // Có thể là chuỗi URL hoặc file upload
            'link'        => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'position'    => 'nullable|integer|min:0',
            'is_active'   => 'nullable|boolean',
        ], [
            'name.required'  => 'Tên banner là bắt buộc.',
            'image.required' => 'Hình ảnh banner là bắt buộc.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ!',
                'errors'  => $validator->errors()
            ], 422);
        }

        $imagePath = $request->input('image');

        // Xử lý upload file nếu gửi dữ liệu dạng multipart file
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('images/banners'), $fileName);
            $imagePath = '/images/banners/' . $fileName;
        }

        $banner = Banners::create([
            'name'        => $request->input('name'),
            'image'       => $imagePath,
            'link'        => $request->input('link'),
            'description' => $request->input('description'),
            'position'    => $request->input('position', 0),
            'is_active'   => filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thêm banner mới thành công!',
            'data'    => $banner
        ], 201);
    }

    /**
     * Cập nhật Banner (Admin).
     */
    public function update(Request $request, $id)
    {
        $banner = Banners::find($id);

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy banner cần cập nhật!'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'        => 'sometimes|required|string|max:255',
            'image'       => 'nullable',
            'link'        => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'position'    => 'nullable|integer|min:0',
            'is_active'   => 'nullable|boolean',
        ], [
            'name.required' => 'Tên banner không được để trống.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ!',
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $request->only(['name', 'link', 'description', 'position']);

        if ($request->has('is_active')) {
            $data['is_active'] = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
        }

        // Xử lý upload file nếu có
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('images/banners'), $fileName);
            $data['image'] = '/images/banners/' . $fileName;
        } elseif ($request->filled('image')) {
            $data['image'] = $request->input('image');
        }

        $banner->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật banner thành công!',
            'data'    => $banner
        ], 200);
    }

    /**
     * Bật / tắt trạng thái hoạt động banner (Admin).
     */
    public function toggleActive($id)
    {
        $banner = Banners::find($id);

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy banner!'
            ], 404);
        }

        $banner->is_active = !$banner->is_active;
        $banner->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái banner thành công!',
            'data'    => $banner
        ], 200);
    }

    /**
     * Xóa banner (Admin).
     */
    public function destroy($id)
    {
        $banner = Banners::find($id);

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy banner!'
            ], 404);
        }

        $banner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa banner thành công!'
        ], 200);
    }
}
