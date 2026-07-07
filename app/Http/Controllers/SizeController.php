<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Size;

class SizeController extends Controller
{
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
    function togglecate($id){
          $size = Size::findOrFail($id);
           $size->update(['status' => !$size->status]);
        return response()->json(['message' => 'Cập nhật trạng thái thành công!']);
    }

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

    function edit(Request $request, $id){
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

     function destroy(Size $size){
        if ($size->variants()->exists()) {
        return response()->json([
            'success' => false,
            'message' => 'hiện đang có sản phẩm sử dụng kích thước này, không thể xóa.',
        ], 400); 
        }
        $size->delete();
         return response()->json([
            'success' => true,
            'message' => 'deleted',
        ],200);
    }
}
