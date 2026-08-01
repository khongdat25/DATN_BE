<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Color;

class ColorController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/color",
     *     summary="Lấy danh sách màu sắc",
     *     tags={"Quản lý Màu sắc (Color)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="q", in="query", required=false, description="Từ khóa tìm kiếm", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, description="Trạng thái", @OA\Schema(type="string")),
     *     @OA\Parameter(name="sort", in="query", required=false, description="Sắp xếp", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    function index(Request $request){
         $color = Color::query();

        if($request->filled('q')) {
                $color->where('name', 'like', '%' . $request->q . '%');
        }
         if ($request->filled('status')) {
                $color->where('status', $request->status);
            }
        if ($request->filled('sort')) {
                if ($request->sort == 'asc') {
                    $color->orderBy('name', 'asc');
                }
                if ($request->sort == 'desc') {
                    $color->orderBy('name', 'desc');
                }
            }
        return response()->json(
        [
            'success' => true,
            'message' => 'hiển bị và lọc color',
            'data' => $color->get(),
            
        ],200);
    }

    /**
     * @OA\Patch(
     *     path="/api/color/toggle-cate/{id}",
     *     summary="Bật/tắt trạng thái màu sắc",
     *     tags={"Quản lý Màu sắc (Color)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    function togglecate(int $id){
          $color = Color::findOrFail($id);
           $color->update(['status' => !$color->status]);
        return response()->json(['message' => 'Cập nhật trạng thái thành công!']);
    }

    /**
     * @OA\Post(
     *     path="/api/color/add",
     *     summary="Thêm mới màu sắc",
     *     tags={"Quản lý Màu sắc (Color)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","color_code","status"},
     *             @OA\Property(property="name", type="string", example="Đen"),
     *             @OA\Property(property="color_code", type="string", example="#000000"),
     *             @OA\Property(property="description", type="string", example="Màu đen huyền bí"),
     *             @OA\Property(property="status", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Tạo thành công")
     * )
     */
    function add(Request $request){
        $request->validate([
        'name'        => 'required|string|unique:colors,name',
        'color_code' => 'required|string|unique:colors,color_code',
        'description' => 'nullable|string',
        'status'        =>  'required'
        ],
        [
        'name.unique' => 'màu sắc này này đã tồn tại trong hệ thống.',
        'color_code.unique' => 'mã này này đã tồn tại trong hệ thống.',
        ]);

     $color = Color::create([
            'name'        => $request->name,
            'color_code'        => $request->color_code,
            'description'        => $request->description,
            'status'      => $request->status,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'success',
            'data'    => $color,
        ], 201);     
    }

    /**
     * @OA\Put(
     *     path="/api/color/edit/{id}",
     *     summary="Cập nhật thông tin màu sắc",
     *     tags={"Quản lý Màu sắc (Color)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","color_code","status"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="color_code", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="status", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cập nhật thành công")
     * )
     */
    function edit(Request $request, int $id){
        $request->validate([
        'name'          => 'required|string|unique:colors,name,' . $id,
        'color_code'    => 'required|string|unique:colors,color_code,' . $id,
        'description' => 'nullable|string',
        'status'        =>  'required'
        ],[
        'name.unique' => 'màu sắc này này đã tồn tại trong hệ thống.',
        'color_code.unique' => 'mã này này đã tồn tại trong hệ thống.',
        ]);

        $color = Color::findOrFail($id);

        $color->update([
            'name'        => $request->name,
            'color_code'        => $request->color_code,
            'status'      => $request->status,
            'description'        => $request->description,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'success',
            'data'    => $color,
        ], 200);    
    }

    /**
     * @OA\Delete(
     *     path="/api/color/delete/{color}",
     *     summary="Xóa màu sắc",
     *     tags={"Quản lý Màu sắc (Color)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="color", in="path", required=true, description="ID Màu sắc", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xóa thành công"),
     *     @OA\Response(response=400, description="Không thể xóa do màu sắc đang được sử dụng")
     * )
     */
     function destroy(Color $color){
        if ($color->variants()->exists()) {
        return response()->json([
            'success' => false,
            'message' => 'hiện đang có sản phẩm sử dụng màu sắc này, không thể xóa.',
        ], 400); 
        }
        $color->delete();
         return response()->json([
            'success' => true,
            'message' => 'deleted',
        ],200);
    }
}
