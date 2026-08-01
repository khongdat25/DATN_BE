<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/addresses",
     *     summary="Danh sách địa chỉ giao hàng của tôi",
     *     tags={"Địa chỉ (Address)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function index(Request $request)
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa đăng nhập!',
            ], 401);
        }

        $addresses = $user->addresses()
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $addresses,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/addresses",
     *     summary="Thêm mới địa chỉ giao hàng",
     *     tags={"Địa chỉ (Address)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","phone","address","badge"},
     *             @OA\Property(property="name", type="string", example="Nguyễn Văn A"),
     *             @OA\Property(property="phone", type="string", example="0987654321"),
     *             @OA\Property(property="address", type="string", example="123 Lê Lợi, Q.1, TP.HCM"),
     *             @OA\Property(property="badge", type="string", example="Nhà riêng"),
     *             @OA\Property(property="is_default", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Thêm thành công")
     * )
     */
    public function store(Request $request)
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa đăng nhập!',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'badge' => 'required|string|max:50',
            'is_default' => 'sometimes|boolean',
        ], [
            'name.required' => 'Họ tên người nhận là bắt buộc.',
            'phone.required' => 'Số điện thoại nhận hàng là bắt buộc.',
            'address.required' => 'Địa chỉ giao hàng là bắt buộc.',
            'badge.required' => 'Nhãn địa chỉ là bắt buộc.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ!',
                'errors' => $validator->errors(),
            ], 422);
        }

        $hasNoAddress = $user->addresses()->count() === 0;
        $isDefault = $hasNoAddress ? true : ((bool) $request->input('is_default', false) || $request->input('badge') === 'Mặc định');

        if ($isDefault) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = $user->addresses()->create([
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
            'badge' => $request->badge,
            'is_default' => $isDefault,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thêm địa chỉ thành công!',
            'data' => $address,
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/addresses/{id}",
     *     summary="Cập nhật địa chỉ giao hàng",
     *     tags={"Địa chỉ (Address)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","phone","address","badge"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="badge", type="string"),
     *             @OA\Property(property="is_default", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cập nhật thành công")
     * )
     */
    public function update(Request $request, int $id)
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa đăng nhập!',
            ], 401);
        }

        $address = $user->addresses()->find($id);
        if (! $address) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy địa chỉ!',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'badge' => 'required|string|max:50',
            'is_default' => 'sometimes|boolean',
        ], [
            'name.required' => 'Họ tên người nhận là bắt buộc.',
            'phone.required' => 'Số điện thoại nhận hàng là bắt buộc.',
            'address.required' => 'Địa chỉ giao hàng là bắt buộc.',
            'badge.required' => 'Nhãn địa chỉ là bắt buộc.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ!',
                'errors' => $validator->errors(),
            ], 422);
        }

        $isDefault = (bool) $request->input('is_default', false) || $request->input('badge') === 'Mặc định';

        if ($isDefault) {
            $user->addresses()->where('id', '!=', $id)->update(['is_default' => false]);
        } else {
            if ($address->is_default) {
                $anotherAddress = $user->addresses()->where('id', '!=', $id)->first();
                if ($anotherAddress) {
                    $anotherAddress->update(['is_default' => true]);
                } else {
                    $isDefault = true;
                }
            }
        }

        $address->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
            'badge' => $request->badge,
            'is_default' => $isDefault,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật địa chỉ thành công!',
            'data' => $address,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/addresses/{id}",
     *     summary="Xóa địa chỉ giao hàng",
     *     tags={"Địa chỉ (Address)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xóa thành công")
     * )
     */
    public function destroy(int $id)
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa đăng nhập!',
            ], 401);
        }

        $address = $user->addresses()->find($id);
        if (! $address) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy địa chỉ!',
            ], 404);
        }

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $nextDefault = $user->addresses()->first();
            if ($nextDefault) {
                $nextDefault->update(['is_default' => true]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Xóa địa chỉ thành công!',
        ], 200);
    }
}
