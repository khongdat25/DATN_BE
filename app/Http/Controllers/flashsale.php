<?php

namespace App\Http\Controllers;

use App\Models\Flashsale as flash;
use App\Models\Flashsaleitem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class flashsale extends Controller
{
    /**
     * Hiển thị và lọc danh sách flashsale.
     */
    public function show(Request $request)
    {
        $now = Carbon::now();
        flash::query()
            ->where('status', '!=', 3)
            ->where('end_time', '<', $now)
            ->update(['status' => 3]);

        $data = flash::query()
            ->withCount(['items as total']);

        if ($request->filled('status')) {
            $data->where('status', $request->status);
        }

        $sale = $data->with([
            'items' => function($q) {
                $q->select('id', 'flash_sale_id', 'product_id', 'variant_id', 'discount_value', 'quantity_limit', 'sold');
            },
            'items.product',
            'items.product.variants',
            'items.variant',
            'items.variant.size',
            'items.variant.color'
        ])
        ->orderBy('id', 'desc')
        ->get(['id', 'name', 'start_time', 'end_time', 'status']); 

        return response()->json([
            'success' => true,
            'message' => 'Hiển thị và lọc flashsale',
            'data' => $sale,
        ], 200);
    }

    /**
     * Xóa chiến dịch flashsale (Soft Delete).
     */
    public function destroy(int $id)
    {
        $flash = flash::findOrFail($id);
        $flash->items()->delete();
        $flash->delete();

        return response()->json([
            'success' => true,
            'message' => 'deleted',
        ], 200);
    }

    function add(Request $request){
        $request->validate([
            'name'            => 'required|string|max:255',
            'start_time'      => 'nullable|string',
            'end_time'        => 'nullable|string',
            'discount_value'  => 'required|numeric|min:0|max:100',
            'quantity_limit'  => 'required|integer|min:1',
            'items'           => 'nullable|array',
            'product_ids'     => 'nullable|array',
        ]);
        DB::beginTransaction();

        if ($request->filled('start_time') && $request->filled('end_time')) {
            $startedTime = Carbon::parse($request->start_time);
            $endTime     = Carbon::parse($request->end_time);
        } elseif ($request->has('golden_hour') && $request->filled('date')) {
            $times = explode(' - ', $request->golden_hour);
            $startedTime = Carbon::createFromFormat('Y-m-d H:i', $request->date . ' ' . ($times[0] ?? '00:00'));
            $endTime     = Carbon::createFromFormat('Y-m-d H:i', $request->date . ' ' . ($times[1] ?? '23:59'));
        } else {
            $startedTime = Carbon::parse(($request->date ?? date('Y-m-d')) . ' ' . ($request->start_hour ?? '00:00'));
            $endTime     = Carbon::parse(($request->date ?? date('Y-m-d')) . ' ' . ($request->end_hour ?? '23:59'));
        }

        try {
            $create = flash::create([
                'name'       => $request->name,
                'start_time' => $startedTime,
                'end_time'   => $endTime,
                'status'     => 1,
            ]);

            if ($request->filled('items') && is_array($request->items)) {
                foreach ($request->items as $item) {
                    Flashsaleitem::create([
                        'flash_sale_id'  => $create->id,
                        'product_id'     => $item['product_id'],
                        'variant_id'     => $item['variant_id'] ?? null,
                        'discount_value' => $request->discount_value,
                        'quantity_limit' => $request->quantity_limit,
                    ]);
                }
            } elseif ($request->filled('product_ids') && is_array($request->product_ids)) {
                foreach ($request->product_ids as $pId) {
                    Flashsaleitem::create([
                        'flash_sale_id'  => $create->id,
                        'product_id'     => $pId,
                        'variant_id'     => null,
                        'discount_value' => $request->discount_value,
                        'quantity_limit' => $request->quantity_limit,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'success',
                'data' => $create,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'failed :(',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cập nhật thông tin chiến dịch flashsale.
     */
    public function edit(Request $request, int $id)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'start_time'      => 'nullable|string',
            'end_time'        => 'nullable|string',
            'discount_value'  => 'required|numeric|min:0|max:100',
            'quantity_limit'  => 'required|integer|min:1',
            'items'           => 'nullable|array',
            'product_ids'     => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            $flashSale = flash::findOrFail($id);

            if ($request->filled('start_time') && $request->filled('end_time')) {
                $startedTime = Carbon::parse($request->start_time);
                $endTime     = Carbon::parse($request->end_time);
            } elseif ($request->has('golden_hour') && $request->filled('date')) {
                $times = explode(' - ', $request->golden_hour);
                $startedTime = Carbon::createFromFormat('Y-m-d H:i', $request->date . ' ' . ($times[0] ?? '00:00'));
                $endTime     = Carbon::createFromFormat('Y-m-d H:i', $request->date . ' ' . ($times[1] ?? '23:59'));
            } else {
                $startedTime = Carbon::parse(($request->date ?? date('Y-m-d')) . ' ' . ($request->start_hour ?? '00:00'));
                $endTime     = Carbon::parse(($request->date ?? date('Y-m-d')) . ' ' . ($request->end_hour ?? '23:59'));
            }

            $flashSale->update([
                'name'       => $request->name,
                'start_time' => $startedTime,
                'end_time'   => $endTime,
            ]);
            Flashsaleitem::query()->where('flash_sale_id', $flashSale->id)->delete();

            if ($request->filled('items') && is_array($request->items)) {
                foreach ($request->items as $item) {
                    Flashsaleitem::create([
                        'flash_sale_id'  => $flashSale->id,
                        'product_id'     => $item['product_id'],
                        'variant_id'     => $item['variant_id'] ?? null,
                        'discount_value' => $request->discount_value,
                        'quantity_limit' => $request->quantity_limit,
                    ]);
                }
            } elseif ($request->filled('product_ids') && is_array($request->product_ids)) {
                foreach ($request->product_ids as $pId) {
                    Flashsaleitem::create([
                        'flash_sale_id'  => $flashSale->id,
                        'product_id'     => $pId,
                        'variant_id'     => null,
                        'discount_value' => $request->discount_value,
                        'quantity_limit' => $request->quantity_limit,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'updated successfully',
                'data' => $flashSale,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'failed to update :(',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bật/tắt trạng thái hoạt động của flashsale.
     */
    public function togglecate(int $id)
    {
        $flash = flash::findOrFail($id);

        // Không cho phép thay đổi trạng thái nếu chiến dịch đã kết thúc
        if ($flash->status == 3) {
            return response()->json([
                'success' => false,
                'message' => 'Chiến dịch đã kết thúc, không thể cập nhật nữa!',
            ], 400);
        }

        $flash->update(['status' => $flash->status == 1 ? 2 : 1]);

        return response()->json(['message' => 'Cập nhật trạng thái thành công!']);
    }

    /**
     * Kết thúc chiến dịch flashsale.
     */
    public function endcamp(int $id)
    {
        $flash = flash::findOrFail($id);
        $flash->update(['status' => 3]);

        return response()->json(['message' => 'Chiến dịch đã kết thúc, không thể cập nhật nữa']);
    }
}
