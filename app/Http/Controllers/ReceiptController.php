<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Models\Variant;
use App\Models\Receipt;
use App\Models\ReceiptDetail;
use app\Models\Document_type;
use app\Models\Supplier;


class ReceiptController extends Controller
{
public function updateStock(Request $request) {
    $request->validate([
            'type' => 'required|integer', 
            'supplier_id' => 'required|integer',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer',
            'items.*.doc_quantity' => 'required|integer|min:1',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

     foreach ($request->items as $item) {
                $variant = Variant::findOrFail($item['variant_id']);
                if ($request->type == 2 && $variant->stock < $item['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Sản phẩm SKU [{$variant->sku}] không đủ số lượng để xuất. Tồn kho hiện tại: {$variant->stock}."
                    ], 400);
                }
                
    DB::beginTransaction();
    try {
        $receipt = Receipt::create([
            'type_id'     => $request->type,
            'total'       => 0,
            'supplier_id' => $request->supplier_id,
            'update_at'   => now(), 
        ]);
    foreach ($request->items as $item) {
                $variant = Variant::findOrFail($item['variant_id']);

                if ($request->type == 2 && $variant->stock < $item['quantity']) {
                    DB::rollBack(); 
                    return response()->json([
                        'success' => false,
                        'message' => "Sản phẩm SKU [{$variant->sku}] không đủ số lượng để xuất. Tồn kho hiện tại: {$variant->stock}."
                    ], 400);
                }


                if ($request->type == 1) {
                    $variant->stock += $item['quantity'];
                } elseif ($request->type == 2) {
                    $variant->stock -= $item['quantity'];
                }
                $variant->save(); 

                $totalDetailPrice = $item['quantity'] * $item['price'];

                ReceiptDetail::create([
                    'receipt_id'   => $receipt->id,
                    'variant_id'   => $variant->id,
                    'sku'          => $variant->sku,
                    'doc_quantity' => $item['doc_quantity'], 
                    'quantity'     => $item['quantity'],
                    'price'        => $item['price'],
                    'total_price'  => $totalDetailPrice,
                ]);
            }

            $totalReceiptPrice = ReceiptDetail::where('receipt_id', $receipt->id)->sum('total_price');
            
            $receipt->update([
                'total' => $totalReceiptPrice
            ]);

            DB::commit();


           return response()->json([
                'success' => true,
                'message' => $request->type == 1 ? 'Nhập kho hàng loạt thành công!' : 'Xuất kho hàng loạt thành công!',
                'data' => [
                    'receipt_id' => $receipt->id,
                    'total_amount' => $totalReceiptPrice
                ]
            ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Lỗi: không thể cập nhật số lượng hàng',
            'error' => $e->getMessage()
        ], 500);
    }
}
} 

    function checkReceipts($id)
    {   
            return response()->json([
                'success' => true,
                'message' => 'thông tin xuất nhập kho của sản phẩm',
                'data' => ReceiptDetail::where('variant_id', $id)->with(['receipt', 'variant'])->orderBy('id', 'desc')->get()
            ], 200);
    }
}