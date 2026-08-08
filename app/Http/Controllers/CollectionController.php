<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CollectionController extends Controller
{
    /**
     * Get active collections for public storefront
     */
    public function publicIndex(Request $request)
    {
        $query = Collection::where('status', '=', 'published', 'and')
            ->with(['products' => function ($q) {
                $q->with('variants');
            }]);

        if ($request->has('featured')) {
            $query->where('is_featured', '=', true, 'and');
        }

        $collections = $query->orderBy('is_featured', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $collections
        ]);
    }

    /**
     * Get single collection details with products for storefront
     */
    public function publicShow(string|int $slugOrId)
    {
        $collection = Collection::where('status', '=', 'published', 'and')
            ->where(function ($q) use ($slugOrId) {
                $q->where('slug', '=', $slugOrId, 'and')
                  ->orWhere('id', '=', $slugOrId);
            })
            ->with(['products' => function ($q) {
                $q->with(['variants', 'category', 'brand']);
            }])
            ->first();

        if (! $collection) {
            return response()->json([
                'success' => false,
                'message' => 'Bộ sưu tập không tồn tại hoặc đã bị ẩn'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $collection
        ]);
    }

    /**
     * Admin index with search & product count
     */
    public function adminIndex(Request $request)
    {
        $query = Collection::with('products');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%", 'and')
                  ->orWhere('excerpt', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', '=', $request->status, 'and');
        }

        $collections = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $collections
        ]);
    }

    /**
     * Store new collection (Admin)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'banner' => 'nullable|string',
            'excerpt' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|in:published,draft',
            'is_featured' => 'nullable|boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer'
        ]);

        $slug = Str::slug($request->name);
        $count = Collection::where('slug', 'LIKE', "{$slug}%", 'and')->count();
        if ($count > 0) {
            $slug = "{$slug}-" . ($count + 1);
        }

        $collection = Collection::create([
            'name' => $request->name,
            'slug' => $slug,
            'banner' => $request->banner ?? 'placeholder.png',
            'excerpt' => $request->excerpt,
            'description' => $request->description,
            'meta_title' => $request->meta_title ?? $request->name,
            'meta_description' => $request->meta_description ?? $request->excerpt,
            'focus_keyword' => $request->focus_keyword,
            'status' => $request->status ?? 'published',
            'is_featured' => filter_var($request->is_featured, FILTER_VALIDATE_BOOLEAN)
        ]);

        if ($request->has('product_ids') && is_array($request->product_ids)) {
            $collection->products()->sync($request->product_ids);
        }

        $collection->load('products');

        return response()->json([
            'success' => true,
            'message' => 'Tạo bộ sưu tập mới thành công!',
            'data' => $collection
        ], 201);
    }

    /**
     * Update existing collection (Admin)
     */
    public function update(Request $request, int|string $id)
    {
        $collection = Collection::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'banner' => 'nullable|string',
            'excerpt' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|in:published,draft',
            'is_featured' => 'nullable|boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer'
        ]);

        if ($collection->name !== $request->name) {
            $slug = Str::slug($request->name);
            $count = Collection::where('slug', 'LIKE', "{$slug}%", 'and')->where('id', '!=', $id, 'and')->count();
            if ($count > 0) {
                $slug = "{$slug}-" . ($count + 1);
            }
            $collection->slug = $slug;
        }

        $collection->name = $request->name;
        if ($request->filled('banner')) {
            $collection->banner = $request->banner;
        }
        $collection->excerpt = $request->excerpt;
        $collection->description = $request->description;
        $collection->meta_title = $request->meta_title ?? $request->name;
        $collection->meta_description = $request->meta_description ?? $request->excerpt;
        $collection->focus_keyword = $request->focus_keyword;
        $collection->status = $request->status ?? $collection->status;
        $collection->is_featured = filter_var($request->is_featured, FILTER_VALIDATE_BOOLEAN);
        $collection->save();

        if ($request->has('product_ids')) {
            $productIds = is_array($request->product_ids) ? $request->product_ids : [];
            $collection->products()->sync($productIds);
        }

        $collection->load('products');

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật bộ sưu tập thành công!',
            'data' => $collection
        ]);
    }

    /**
     * Toggle status between published & draft
     */
    public function toggleStatus(int|string $id)
    {
        $collection = Collection::findOrFail($id);
        $collection->status = $collection->status === 'published' ? 'draft' : 'published';
        $collection->save();

        return response()->json([
            'success' => true,
            'message' => 'Đã thay đổi trạng thái bộ sưu tập!',
            'data' => $collection
        ]);
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(int|string $id)
    {
        $collection = Collection::findOrFail($id);
        $collection->is_featured = ! $collection->is_featured;
        $collection->save();

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật trạng thái nổi bật!',
            'data' => $collection
        ]);
    }

    /**
     * Soft delete collection (Admin)
     */
    public function destroy(int|string $id)
    {
        $collection = Collection::findOrFail($id);
        $collection->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa bộ sưu tập thành công!'
        ]);
    }
}
