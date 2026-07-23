<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryApi extends Controller
{
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

    public function togglecate(string|int $id)
    {
        $category = Category::findOrFail($id);
        $category->update(['status' => ! $category->status]);

        return response()->json(['message' => 'Cập nhật trạng thái thành công!']);
    }

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
