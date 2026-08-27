<?php

namespace App\Http\Controllers;

use App\Models\Flashsale as flash;
use App\Models\Variant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class flashsale extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/flash-sale",
     *     summary="[Admin] Danh sách và lọc các chiến dịch Flash Sale",
     *     tags={"Quản lý Flash Sale"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="status", in="query", required=false, description="Trạng thái (1: Đang chạy, 2: Tạm dừng, 3: Kết thúc)", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function show(Request $request)
    {
        $now = Carbon::now();
        flash::query()
            ->where('status', '!=', 3, 'and')
            ->where('end_time', '<', $now, 'and')
            ->update(['status' => 3]);

        $data = flash::query()
            ->withCount(['variants as total']);

        if ($request->filled('status')) {
            $data->where('status', '=', $request->status, 'and');
        }

        $sale = $data->with([
            'variants',
            'variants.product',
            'variants.size',
            'variants.color'
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
     * @OA\Delete(
     *     path="/api/flash-sale/delete/{id}",
     *     summary="[Admin] Xóa chiến dịch Flash Sale",
     *     tags={"Quản lý Flash Sale"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xóa thành công")
     * )
     */
    public function destroy(int $id)
    {
        $flash = flash::findOrFail($id);
        Variant::where('flash_sale_id', '=', $flash->id, 'and')->update(['flash_sale_id' => null, 'sale_price' => null]);
        $flash->delete();

        return response()->json([
            'success' => true,
            'message' => 'deleted',
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/flash-sale/add",
     *     summary="[Admin] Tạo mới chiến dịch Flash Sale",
     *     tags={"Quản lý Flash Sale"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","discount_value","quantity_limit"},
     *             @OA\Property(property="name", type="string", example="Flash Sale Giờ Vàng"),
     *             @OA\Property(property="start_time", type="string", example="2026-08-01 10:00"),
     *             @OA\Property(property="end_time", type="string", example="2026-08-01 22:00"),
     *             @OA\Property(property="discount_value", type="number", example=20),
     *             @OA\Property(property="quantity_limit", type="integer", example=50),
     *             @OA\Property(property="product_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Tạo thành công")
     * )
     */
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
                    if (!empty($item['variant_id'])) {
                        $v = Variant::where('id', '=', $item['variant_id'], 'and')->first();
                        if ($v) {
                            $calcSale = $request->discount_value < 1 ? $v->price * (1 - $request->discount_value) : $v->price * (1 - $request->discount_value / 100);
                            $v->update([
                                'flash_sale_id' => $create->id,
                                'sale_price' => $calcSale
                            ]);
                        }
                    }
                }
            } elseif ($request->filled('product_ids') && is_array($request->product_ids)) {
                foreach ($request->product_ids as $pId) {
                    $variants = Variant::where('product_id', '=', $pId, 'and')->get();
                    foreach ($variants as $v) {
                        $calcSale = $request->discount_value < 1 ? $v->price * (1 - $request->discount_value) : $v->price * (1 - $request->discount_value / 100);
                        $v->update([
                            'flash_sale_id' => $create->id,
                            'sale_price' => $calcSale
                        ]);
                    }
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
     * @OA\Put(
     *     path="/api/flash-sale/edit/{id}",
     *     summary="[Admin] Cập nhật chiến dịch Flash Sale",
     *     tags={"Quản lý Flash Sale"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","discount_value","quantity_limit"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="start_time", type="string"),
     *             @OA\Property(property="end_time", type="string"),
     *             @OA\Property(property="discount_value", type="number"),
     *             @OA\Property(property="quantity_limit", type="integer"),
     *             @OA\Property(property="product_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cập nhật thành công")
     * )
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

            Variant::where('flash_sale_id', '=', $flashSale->id, 'and')->update(['flash_sale_id' => null, 'sale_price' => null]);

            if ($request->filled('items') && is_array($request->items)) {
                foreach ($request->items as $item) {
                    if (!empty($item['variant_id'])) {
                        $v = Variant::where('id', '=', $item['variant_id'], 'and')->first();
                        if ($v) {
                            $calcSale = $request->discount_value < 1 ? $v->price * (1 - $request->discount_value) : $v->price * (1 - $request->discount_value / 100);
                            $v->update([
                                'flash_sale_id' => $flashSale->id,
                                'sale_price' => $calcSale
                            ]);
                        }
                    }
                }
            } elseif ($request->filled('product_ids') && is_array($request->product_ids)) {
                foreach ($request->product_ids as $pId) {
                    $variants = Variant::where('product_id', '=', $pId, 'and')->get();
                    foreach ($variants as $v) {
                        $calcSale = $request->discount_value < 1 ? $v->price * (1 - $request->discount_value) : $v->price * (1 - $request->discount_value / 100);
                        $v->update([
                            'flash_sale_id' => $flashSale->id,
                            'sale_price' => $calcSale
                        ]);
                    }
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
     * @OA\Patch(
     *     path="/api/flash-sale/toggle-cate/{id}",
     *     summary="[Admin] Bật/Tắt trạng thái chiến dịch Flash Sale",
     *     tags={"Quản lý Flash Sale"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Cập nhật thành công")
     * )
     */
    public function togglecate(int $id)
    {
        $flash = flash::findOrFail($id);

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
     * @OA\Patch(
     *     path="/api/flash-sale/end-camp/{id}",
     *     summary="[Admin] Kết thúc chiến dịch Flash Sale",
     *     tags={"Quản lý Flash Sale"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Đã kết thúc chiến dịch")
     * )
     */
    public function endcamp(int $id)
    {
        $flash = flash::findOrFail($id);
        $flash->update(['status' => 3]);

        return response()->json(['message' => 'Chiến dịch đã kết thúc, không thể cập nhật nữa']);
    }
}
