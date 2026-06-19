<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\ProductModel;
use App\Models\Variant;
use App\Models\Category;
use App\Models\Brand;
use Carbon\Carbon;
class CategoryApi extends Controller
{
    function admin_category(Request $request){
         $category = Category::query()
        ->withCount(['variants as total']);

        if($request->filled('q')) {
                $category->where('name', 'like', '%' . $request->q . '%');
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
            
        ],200);
    }
    function togglecate($id){
          $category = Category::findOrFail($id);
           $category->update(['status' => !$category->status]);
        return response()->json(['message' => 'Cập nhật trạng thái thành công!']);
    }

    function add(Request $request){
        $request->validate([
        'name'        => 'required|string',
        'slug'    => 'required|string',
        'description'    => 'required|string',
        ]);
     $category = Category::create([
            'name'        => $request->name,
            'slug'        => $request->slug,
            'description' =>$request->description,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'success',
            'data'    => $category,
        ], 201);    
    }

    function edit(Request $request, $id){
        $request->validate([
        'name'          => 'required|string',
        'slug'          => 'required|string',
        'description'   => 'required|string',
        'status'        =>  'required'
        ]);
        $category = Category::findOrFail($id);

        $category->update([
            'name'        => $request->name,
            'slug'        => $request->slug,
            'description' =>$request->description,
            'status'      =>$request->status  
        ]);
        return response()->json([
            'success' => true,
            'message' => 'success',
            'data'    => $category,
        ], 200);    
    }

     function destroy(Category $category){
        if ($category->products()->exists()) {
        return response()->json([
            'success' => false,
            'message' => 'Danh mục này hiện đang chứa sản phẩm.'
        ], 400); 
        }
        $category->delete();
         return response()->json([
            'success' => true,
            'message' => 'deleted',
        ],200);
    }

}
