<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Color;

class ColorController extends Controller
{
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
    function togglecate($id){
          $color = Color::findOrFail($id);
           $color->update(['status' => !$color->status]);
        return response()->json(['message' => 'Cập nhật trạng thái thành công!']);
    }

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

    function edit(Request $request, $id){
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
