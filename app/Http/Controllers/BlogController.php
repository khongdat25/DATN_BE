<?php

namespace App\Http\Controllers;

use App\Models\Blogs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/blogs",
     *     summary="Danh sách bài viết tin tức (Công khai)",
     *     tags={"Tin tức (Blogs)"},
     *     @OA\Parameter(name="featuring", in="query", required=false, description="Lọc bài viết nổi bật (true/false)", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="search", in="query", required=false, description="Tìm kiếm theo tiêu đề", @OA\Schema(type="string")),
     *     @OA\Parameter(name="limit", in="query", required=false, description="Số lượng bài viết trên 1 trang", @OA\Schema(type="integer", default=10)),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function index(Request $request)
    {
        $query = Blogs::query();

        if ($request->has('featuring')) {
            $query->where('featuring', '=', filter_var($request->input('featuring'), FILTER_VALIDATE_BOOLEAN), 'and');
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%', 'and');
        }

        $query->orderBy('created_at', 'desc');

        $limit = (int) $request->input('limit', 10);
        $page = (int) $request->input('page', 1);
        $blogs = $query->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách tin tức thành công.',
            'data' => $blogs,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/blogs",
     *     summary="[Admin] Danh sách toàn bộ bài viết tin tức",
     *     tags={"Tin tức (Blogs)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function adminIndex()
    {
        $blogs = Blogs::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách tin tức admin thành công.',
            'data' => [
                'data' => $blogs,
            ],
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/blogs/{slugOrId}",
     *     summary="Chi tiết bài viết tin tức",
     *     tags={"Tin tức (Blogs)"},
     *     @OA\Parameter(name="slugOrId", in="path", required=true, description="ID hoặc Slug bài viết", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Thành công"),
     *     @OA\Response(response=404, description="Không tìm thấy")
     * )
     */
    public function show(string|int $slugOrId)
    {
        $blog = Blogs::query()
            ->where('slug', '=', $slugOrId)
            ->orWhere('id', '=', $slugOrId)
            ->first();

        if (! $blog) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bài viết!',
            ], 404);
        }

        $blog->increment('views');

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết bài viết thành công.',
            'data' => $blog,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/blogs",
     *     summary="[Admin] Thêm bài viết tin tức mới",
     *     tags={"Tin tức (Blogs)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","content"},
     *             @OA\Property(property="name", type="string", example="Xu hướng Sneaker 2026"),
     *             @OA\Property(property="avatar", type="string", example="blog1.jpg"),
     *             @OA\Property(property="comment", type="string", example="Mô tả ngắn"),
     *             @OA\Property(property="content", type="string", example="Nội dung bài viết chi tiết..."),
     *             @OA\Property(property="featuring", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Tạo thành công")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'avatar' => 'nullable|string',
            'comment' => 'nullable|string',
            'content' => 'required|string',
            'featuring' => 'sometimes|boolean',
        ], [
            'name.required' => 'Tiêu đề bài viết là bắt buộc.',
            'content.required' => 'Nội dung bài viết là bắt buộc.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ!',
                'errors' => $validator->errors(),
            ], 422);
        }

        $blog = Blogs::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'avatar' => $request->avatar,
            'comment' => $request->comment,
            'content' => $request->input('content'),
            'featuring' => $request->input('featuring', false),
            'views' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo bài viết tin tức thành công!',
            'data' => $blog,
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/blogs/{id}",
     *     summary="[Admin] Cập nhật bài viết tin tức",
     *     tags={"Tin tức (Blogs)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="avatar", type="string"),
     *             @OA\Property(property="comment", type="string"),
     *             @OA\Property(property="content", type="string"),
     *             @OA\Property(property="featuring", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cập nhật thành công")
     * )
     */
    public function update(Request $request, string|int $id)
    {
        $blog = Blogs::find($id, ['*']);

        if (! $blog) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bài viết cần cập nhật!',
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
            'content.required' => 'Nội dung bài viết không được để trống.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ!',
                'errors' => $validator->errors(),
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
            'data' => $blog,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/blogs/{id}",
     *     summary="[Admin] Xóa bài viết tin tức",
     *     tags={"Tin tức (Blogs)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xóa thành công")
     * )
     */
    public function destroy(string|int $id)
    {
        $blog = Blogs::find($id, ['*']);

        if (! $blog) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bài viết!',
            ], 404);
        }

        $blog->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa bài viết tin tức thành công!',
        ], 200);
    }
}
