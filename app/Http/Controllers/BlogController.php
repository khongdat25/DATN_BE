<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Blogs;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    /**
     * Lấy danh sách tin tức.
     */
    public function index(Request $request)
    {
        $query = Blogs::query();

        // Lọc theo tin nổi bật
        if ($request->has('featuring')) {
            $query->where('featuring', filter_var($request->input('featuring'), FILTER_VALIDATE_BOOLEAN));
        }

        // Tìm kiếm theo tiêu đề
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        // Sắp xếp bài viết mới nhất lên đầu
        $query->orderBy('created_at', 'desc');

        // Phân trang mặc định là 10 bài viết
        $limit = $request->input('limit', 10);
        $blogs = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách tin tức thành công.',
            'data' => $blogs
        ], 200);
    }

    /**
     * Xem chi tiết một bài viết bằng ID hoặc Slug.
     */
    public function show($slugOrId)
    {
        $blog = Blogs::where('slug', $slugOrId)
            ->orWhere('id', $slugOrId)
            ->first();

        if (!$blog) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bài viết!'
            ], 404);
        }

        // Tăng lượt xem
        $blog->increment('views');

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết bài viết thành công.',
            'data' => $blog
        ], 200);
    }

    /**
     * Admin: Thêm mới tin tức.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'avatar' => 'nullable|string|max:255',
            'comment' => 'nullable|string',
            'content' => 'required|string',
            'featuring' => 'sometimes|boolean',
        ], [
            'name.required' => 'Tiêu đề bài viết là bắt buộc.',
            'content.required' => 'Nội dung bài viết là bắt buộc.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ!',
                'errors' => $validator->errors()
            ], 422);
        }

        $blog = Blogs::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'avatar' => $request->avatar,
            'comment' => $request->comment,
            'content' => $request->content,
            'featuring' => $request->input('featuring', false),
            'views' => 0
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo bài viết tin tức thành công!',
            'data' => $blog
        ], 201);
    }

    /**
     * Admin: Cập nhật tin tức.
     */
    public function update(Request $request, $id)
    {
        $blog = Blogs::find($id);

        if (!$blog) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bài viết cần cập nhật!'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'avatar' => 'nullable|string|max:255',
            'comment' => 'nullable|string',
            'content' => 'sometimes|required|string',
            'featuring' => 'sometimes|boolean',
        ], [
            'name.required' => 'Tiêu đề bài viết không được để trống.',
            'content.required' => 'Nội dung bài viết không được để trống.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ!',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['name', 'avatar', 'comment', 'content', 'featuring']);
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $blog->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật bài viết tin tức thành công!',
            'data' => $blog
        ], 200);
    }

    /**
     * Admin: Xóa tin tức (Soft Delete).
     */
    public function destroy($id)
    {
        $blog = Blogs::find($id);

        if (!$blog) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bài viết!'
            ], 404);
        }

        $blog->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa bài viết tin tức thành công!'
        ], 200);
    }
}
