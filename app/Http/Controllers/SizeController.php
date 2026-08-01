<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Size;

class SizeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/size",
     *     summary="Lấy danh sách kích thước (Size)",
     *     tags={"Quản lý Size (Kích thước)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="q", in="query", required=false, description="Từ khóa tìm kiếm theo tên", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, description="Trạng thái", @OA\Schema(type="string")),
     *     @OA\Parameter(name="sort", in="query", required=false, description="Sắp xếp (asc/desc)", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
     function index(Request $request){
          $size = Size::query();

        if($request->filled('q')) {
                $size->where('name', 'like', '%' . $request->q . '%');
        }
         if ($request->filled('status')) {
                $size->where('status', $request->status);
            }
        if ($request->filled('sort')) {
                if ($request->sort == 'asc') {
                    $size->orderBy('name', 'asc');
                }
                if ($request->sort == 'desc') {
                    $size->orderBy('name', 'desc');
                }
            }
        return response()->json(
        [
            'success' => true,
            'message' => 'hiển bị và lọc size',
            'data' => $size->get(),
            
        ],200);
    }

    /**
     * @OA\Patch(
     *     path="/api/size/toggle-cate/{id}",
     *     summary="Bật/tắt trạng thái kích thước",
     *     tags={"Quản lý Size (Kích thước)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    function togglecate(int $id){
          $size = Size::findOrFail($id);
           $size->update(['status' => !$size->status]);
        return response()->json(['message' => 'Cập nhật trạng thái thành công!']);
    }

    /**
     * @OA\Post(
     *     path="/api/size/add",
     *     summary="Thêm mới kích thước",
     *     tags={"Quản lý Size (Kích thước)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="42"),
     *             @OA\Property(property="description", type="string", example="Chân dài 25.5cm")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Tạo thành công")
     * )
     */
    function add(Request $request){
        $request->validate([
        'name'        => 'required|string|unique:sizes,name',
        'description' => 'nullable|string',
        ],
        [
        'name.unique' => 'Kích thước này đã tồn tại trong hệ thống.',
        ]);

     $size = Size::create([
            'name'        => $request->name,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'success',
            'data'    => $size,
        ], 201);    
    }

    /**
     * @OA\Put(
     *     path="/api/size/edit/{id}",
     *     summary="Cập nhật thông tin kích thước",
     *     tags={"Quản lý Size (Kích thước)"},
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
        'name'          => 'required|string|unique:sizes,name,' . $id,
        'description' => 'nullable|string',
        ],[
        'name.unique' => 'Kích thước này đã tồn tại trong hệ thống.',
        ]);

        $size = Size::findOrFail($id);

        $size->update([
            'name'        => $request->name,
            'description' => $request->description,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'success',
            'data'    => $size,
        ], 200);    
    }

    /**
     * @OA\Delete(
     *     path="/api/size/delete/{size}",
     *     summary="Xóa kích thước",
     *     tags={"Quản lý Size (Kích thước)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="size", in="path", required=true, description="ID Kích thước", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xóa thành công"),
     *     @OA\Response(response=400, description="Không thể xóa do kích thước đang được sử dụng")
     * )
     */
     function destroy(Size $size){
        if ($size->variants()->exists()) {
        return response()->json([
            'success' => false,
            'message' => 'hiện đang có sản phẩm sử dụng kích thước này, không thể xóa.',
        ], 400); 
        }
        Size::destroy($size->id);
         return response()->json([
            'success' => true,
            'message' => 'deleted',
        ],200);
    }
}
