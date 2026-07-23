<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductModel;
use App\Models\Flashsale as flash;
use App\Models\Flashsaleitem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class flashsale extends Controller
{
    function show(Request $request){
        $data = flash::query()
        ->withCount(['items as total']);

        if ($request->filled('status')){
            $data->where('status', $request->status); 
            }
              $sale = $data
        ->with([

        'items' => function($q) {
            $q->select('id', 'flash_sale_id', 'product_variant_id', 'discount_value', 'quantity_limit', 'sold');
            },
        'items.productVariant',
        'items.productVariant.product' => function($q) {
            $q->select('id', 'name', 'slug', 'category_id', 'brand_id');
        }
        ])->get(['id', 'name', 'start_time', 'end_time', 'status']); 


     $sale->each(function ($flashSale) {
        $flashSale->items->each(function ($item) {
            $product = $item->productVariant?->product;
            if ($product) {
                $product->unsetRelation('variants');
                $product->makeHidden(['variants']);
            }
        });
    });
            return response()->json(
        [
            'success' => true,
            'message' => 'hiển bị và lọc flashsale',
            'data' => $sale,
            
        ],200);
    }

    function destroy($id){
         $flash = flash::findOrFail($id);
         $flash->items()->delete(); 
         $flash->delete();
         return response()->json([
            'success' => true,
            'message' => 'deleted',
        ],200);
    }

   function add(Request $request){
       if ($request->has('golden_hour')) {
        $times = explode(' - ', $request->golden_hour);
        $request->merge([
            'start_hour' => $times[0] ?? null,
            'end_hour'   => $times[1] ?? null,
        ]);
    }
    $request->validate([
         'name'            => 'required|string|max:255',
        'date'            => 'required|date_format:Y-m-d', 
        'start_hour'      => 'required|date_format:H:i',  
        'end_hour'        => 'required|date_format:H:i',    
        'discount_value'  => 'required|numeric|min:0|max:100',
        'quantity_limit'  => 'required|integer|min:1',
        'product_variant_ids'     => 'required|array',
        'product_variant_ids.*'   => 'required|integer'
    ]);
    DB::beginTransaction();

        $startedTime = Carbon::createFromFormat('Y-m-d H:i', $request->date . ' ' . $request->start_hour);
        $endTime     = Carbon::createFromFormat('Y-m-d H:i', $request->date . ' ' . $request->end_hour);

    try {
        $create = flash::create([
            'name'         => $request->name,
            'start_time' => $startedTime,
            'end_time'     => $endTime,
        ]);
        foreach($request->product_variant_ids as $item) {
            Flashsaleitem::create([
                'flash_sale_id' => $create->id,
                'product_variant_id'    => $item,
                'discount_value' => $request->discount_value,
                'quantity_limit' => $request->quantity_limit,
                ]);
        }
      
        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'success',
            'data'    => $create
        ], 201);
        
    }
    catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'failed :(',
            'error'   => $e->getMessage()
        ], 500);
    }
}

   function edit(Request $request, $id){

     if ($request->has('golden_hour')) {
        $times = explode(' - ', $request->golden_hour);
        $request->merge([
            'start_hour' => $times[0] ?? null,
            'end_hour'   => $times[1] ?? null,
        ]);
    }

    $request->validate([
        'name'            => 'required|string|max:255',
        'date'            => 'required|date_format:Y-m-d', 
        'start_hour'      => 'required|date_format:H:i',  
        'end_hour'        => 'required|date_format:H:i',    
        'discount_value'  => 'required|numeric|min:0|max:100',
        'quantity_limit'  => 'required|integer|min:1',
        'product_variant_ids'     => 'required|array',
        'product_variant_ids.*'   => 'required|integer'
    ]);

    DB::beginTransaction();

    try {
        $flashSale = flash::findOrFail($id);

        $startedTime = Carbon::createFromFormat('Y-m-d H:i', $request->date . ' ' . $request->start_hour);
        $endTime     = Carbon::createFromFormat('Y-m-d H:i', $request->date . ' ' . $request->end_hour);
        $flashSale->update([
            'name'       => $request->name,
            'start_time' => $startedTime,
            'end_time'   => $endTime,
        ]);
        Flashsaleitem::where('flash_sale_id', $flashSale->id)->delete();
        foreach($request->product_variant_ids as $item) {
            Flashsaleitem::create([
                'flash_sale_id'  => $flashSale->id,
                'product_variant_id'     => $item, 
                'discount_value' => $request->discount_value,
                'quantity_limit' => $request->quantity_limit,
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'updated successfully',
            'data'    => $flashSale
        ], 200); 
        
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'failed to update :(',
            'error'   => $e->getMessage()
        ], 500);}
    }
   function togglecate($id){
    $flash = flash::findOrFail($id);
    $flash->update(['status' => $flash->status == 1 ? 2 : 1]);
    
    return response()->json(['message' => $flash . 'đã cập nhật thành công']);
    }

   function endcamp($id){
    $flash = flash::findOrFail($id);
    $flash->update(['status' => 3]);
    return response()->json(['message' => 'Chiến dịch đã kết thúc, không thể cập nhật nữa']);
    }
}
