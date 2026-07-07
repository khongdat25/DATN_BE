<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;

class BrandController extends Controller
{
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
    function togglecate($id){
          $brand = Brand::findOrFail($id);
           $brand->update(['status' => !$brand->status]);
        return response()->json(['message' => 'Cập nhật trạng thái thành công!']);
    }

    function togglefeature($id){
          $brand = Brand::findOrFail($id);
           $brand->update(['is_featured' => !$brand->is_featured]);
        return response()->json(['message' => 'Cập nhật trạng thái thành công!']);
    }

    function add(Request $request){
        $request->validate([
        'name'        => 'required|string|unique:brands,name',
        'description' => 'nullable|string',
        'status'        =>  'required'
        ],
        [
        'name.unique' => 'thương hiệu này đã tồn tại trong hệ thống.',
        ]);

     $brand = Brand::create([
            'name'        => $request->name,
            'description'        => $request->description,
            'status'      => $request->status,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'success',
            'data'    => $brand,
        ], 201);     
    }

    function edit(Request $request, $id){
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
        ]);
        return response()->json([
            'success' => true,
            'message' => 'success',
            'data'    => $brand,
        ], 200);    
    }

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
