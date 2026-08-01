<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;

class BrandController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/brand",
     *     summary="Lấy danh sách thương hiệu",
     *     tags={"Quản lý Thương hiệu (Brand)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="q", in="query", required=false, description="Từ khóa tìm kiếm theo tên", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, description="Trạng thái", @OA\Schema(type="string")),
     *     @OA\Parameter(name="sort", in="query", required=false, description="Sắp xếp (asc/desc)", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
     function index(Request $request){
         $brand = Brand::query();

        if($request->filled('q')) {
                $brand->where('name', 'like', '%' . $request->q . '%');
        }
         if ($request->filled('status')) {
                $brand->where('status', $request->status);
            }
        if ($request->filled('sort')) {
                if ($request->sort == 'asc') {
                    $brand->orderBy('name', 'asc');
                }
                if ($request->sort == 'desc') {
                    $brand->orderBy('name', 'desc');
                }
            }
        return response()->json(
        [
            'success' => true,
            'message' => 'hiển bị và lọc thương hiệu',
            'data' => $brand->get(),
            
        ],200);
    }

    /**
     * @OA\Patch(
     *     path="/api/brand/toggle-cate/{id}",
     *     summary="Bật/tắt trạng thái thương hiệu",
     *     tags={"Quản lý Thương hiệu (Brand)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    function togglecate(int $id){
          $brand = Brand::findOrFail($id);
           $brand->update(['status' => !$brand->status]);
        return response()->json(['message' => 'Cập nhật trạng thái thành công!']);
    }

    /**
     * @OA\Patch(
     *     path="/api/brand/toggle-feature/{id}",
     *     summary="Bật/tắt trạng thái nổi bật của thương hiệu",
     *     tags={"Quản lý Thương hiệu (Brand)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    function togglefeature(int $id){
          $brand = Brand::findOrFail($id);
           $brand->update(['is_featured' => !$brand->is_featured]);
        return response()->json(['message' => 'Cập nhật trạng thái thành công!']);
    }

    /**
     * @OA\Post(
     *     path="/api/brand/add",
     *     summary="Thêm mới thương hiệu",
     *     tags={"Quản lý Thương hiệu (Brand)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Nike"),
     *             @OA\Property(property="description", type="string", example="Mô tả thương hiệu...")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Tạo thành công")
     * )
     */
    function add(Request $request){
        $request->validate([
        'name'        => 'required|string|unique:brands,name',
        'description' => 'nullable|string',
        ],
        [
        'name.unique' => 'Thương hiệu này đã tồn tại trong hệ thống.',
        ]);

     $brand = Brand::create([
            'name'        => $request->name,
            'description'        => $request->description,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'success',
            'data'    => $brand,
        ], 201);     
    }

    /**
     * @OA\Put(
     *     path="/api/brand/edit/{id}",
     *     summary="Cập nhật thông tin thương hiệu",
     *     tags={"Quản lý Thương hiệu (Brand)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cập nhật thành công")
     * )
     */
    function edit(Request $request, int $id){
        $request->validate([
        'name'          => 'required|string|unique:brands,name,' . $id,
        'description' => 'nullable|string',
        'status'        =>  'required'
        ],[
        'name.unique' => 'thương hiệu này đã tồn tại trong hệ thống.',
        ]);

        $brand = Brand::findOrFail($id);

        $brand->update([
            'name'        => $request->name,
            'status'      => $request->status,
            'description'        => $request->description,
            'is_featured' => $request->is_featured ?? 0,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'success',
            'data'    => $brand,
        ], 200);    
    }

    /**
     * @OA\Delete(
     *     path="/api/brand/delete/{brand}",
     *     summary="Xóa thương hiệu",
     *     tags={"Quản lý Thương hiệu (Brand)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="brand", in="path", required=true, description="ID Thương hiệu", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xóa thành công"),
     *     @OA\Response(response=400, description="Không thể xóa do thương hiệu có sản phẩm")
     * )
     */
     function destroy(Brand $brand){
        if ($brand->products()->exists()) {
        return response()->json([
            'success' => false,
            'message' => 'hiện đang có sản phẩm sthuộc thương hiệu này, không thể xóa.',
        ], 400); 
        }
        $brand->delete();
         return response()->json([
            'success' => true,
            'message' => 'deleted',
        ],200);
    }
}
