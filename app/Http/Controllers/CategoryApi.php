<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryApi extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admincategory",
     *     summary="[Admin] Danh sách danh mục sản phẩm",
     *     tags={"Quản lý Danh mục (Category)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="q", in="query", required=false, description="Từ khóa tìm kiếm theo tên", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, description="Trạng thái", @OA\Schema(type="string")),
     *     @OA\Parameter(name="sort", in="query", required=false, description="Sắp xếp (byname/byslug/bynumber)", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function admin_category(Request $request)
    {
        $category = Category::query()
            ->withCount(['variants as total']);

        if ($request->filled('q')) {
            $category->where('name', 'like', '%'.$request->q.'%');
        }
        if ($request->filled('status')) {
            $category->where('status', $request->status);
        }
        if ($request->filled('sort')) {
            if ($request->sort == 'byslug') {
                $category->orderBy('slug', 'asc');
            }
            if ($request->sort == 'bynumber') {
                $category->orderBy('total', 'asc');
            }
            if ($request->sort == 'byname') {
                $category->orderBy('name', 'asc');
            }
        }

        return response()->json(
            [
                'success' => true,
                'message' => 'hiển bị và lọc category',
                'data' => $category->get(),

            ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/toggle/{id}",
     *     summary="Bật/tắt trạng thái danh mục",
     *     tags={"Quản lý Danh mục (Category)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function togglecate(string|int $id)
    {
        $category = Category::findOrFail($id);
        $category->update(['status' => ! $category->status]);

        return response()->json(['message' => 'Cập nhật trạng thái thành công!']);
    }

    /**
     * @OA\Post(
     *     path="/api/category_add",
     *     summary="Thêm mới danh mục sản phẩm",
     *     tags={"Quản lý Danh mục (Category)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","slug"},
     *             @OA\Property(property="name", type="string", example="Giày Sneaker Nam"),
     *             @OA\Property(property="slug", type="string", example="giay-sneaker-nam"),
     *             @OA\Property(property="description", type="string", example="Các mẫu giày sneaker nam hot nhất")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Tạo thành công")
     * )
     */
    public function add(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|unique:categories,name',
            'slug'        => 'required|string|unique:categories,slug',
            'description' => 'nullable|string',
        ], [   
            'name.unique' => 'Tên danh mục này đã tồn tại.',
            'slug.unique' => 'Slug danh mục này đã tồn tại.',
        ]);
        $category = Category::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'success',
            'data' => $category,
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/category_edit/{id}",
     *     summary="Cập nhật thông tin danh mục",
     *     tags={"Quản lý Danh mục (Category)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","slug","status"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="slug", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="status", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cập nhật thành công")
     * )
     */
    public function edit(Request $request, string|int $id)
    {
        $request->validate([
            'name'        => 'required|string|unique:categories,name,' . $id,
            'slug'        => 'required|string|unique:categories,slug,' . $id,
            'description' => 'nullable|string',
            'status'      => 'required'
        ], [
            'name.unique' => 'Tên danh mục này đã tồn tại ở một danh mục khác.',
            'slug.unique' => 'Slug danh mục này đã tồn tại ở một danh mục khác.',
        ]);
        $category = Category::findOrFail($id);

        $category->update([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'success',
            'data' => $category,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/category/{category}",
     *     summary="Xóa danh mục sản phẩm",
     *     tags={"Quản lý Danh mục (Category)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="category", in="path", required=true, description="ID danh mục", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xóa thành công"),
     *     @OA\Response(response=400, description="Danh mục đang chứa sản phẩm")
     * )
     */
    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Danh mục này hiện đang chứa sản phẩm.',
            ], 400);
        }
        Category::destroy($category->id);

        return response()->json([
            'success' => true,
            'message' => 'deleted',
        ], 200);
    }
}
