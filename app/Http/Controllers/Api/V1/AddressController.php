<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    /**
     * Lấy danh sách địa chỉ của người dùng.
     */
    public function index(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa đăng nhập!'
            ], 401);
        }

        // Ưu tiên hiển thị địa chỉ mặc định lên đầu
        $addresses = $user->addresses()
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $addresses
        ], 200);
    }

    /**
     * Thêm mới địa chỉ nhận hàng.
     */
    public function store(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa đăng nhập!'
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
                'errors' => $validator->errors()
            ], 422);
        }

        $hasNoAddress = $user->addresses()->count() === 0;
        $isDefault = $hasNoAddress ? true : ((bool)$request->input('is_default', false) || $request->input('badge') === 'Mặc định');

        // Nếu đánh dấu mặc định, gỡ mặc định các địa chỉ khác
        if ($isDefault) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = $user->addresses()->create([
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
            'badge' => $request->badge,
            'is_default' => $isDefault
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thêm địa chỉ thành công!',
            'data' => $address
        ], 201);
    }

    /**
     * Cập nhật địa chỉ nhận hàng.
     */
    public function update(Request $request, $id)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa đăng nhập!'
            ], 401);
        }

        $address = $user->addresses()->find($id);
        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy địa chỉ!'
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
                'errors' => $validator->errors()
            ], 422);
        }

        $isDefault = (bool)$request->input('is_default', false) || $request->input('badge') === 'Mặc định';

        // Xử lý đổi trạng thái mặc định của địa chỉ này
        if ($isDefault) {
            $user->addresses()->where('id', '!=', $id)->update(['is_default' => false]);
        } else {
            // Nếu địa chỉ này đang là mặc định nhưng bị sửa thành không mặc định
            if ($address->is_default) {
                // Thử tìm địa chỉ khác thay thế làm mặc định
                $anotherAddress = $user->addresses()->where('id', '!=', $id)->first();
                if ($anotherAddress) {
                    $anotherAddress->update(['is_default' => true]);
                } else {
                    // Nếu là địa chỉ duy nhất, bắt buộc phải là mặc định
                    $isDefault = true;
                }
            }
        }

        $address->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
            'badge' => $request->badge,
            'is_default' => $isDefault
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật địa chỉ thành công!',
            'data' => $address
        ], 200);
    }

    /**
     * Xóa địa chỉ nhận hàng.
     */
    public function destroy($id)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa đăng nhập!'
            ], 401);
        }

        $address = $user->addresses()->find($id);
        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy địa chỉ!'
            ], 404);
        }

        $wasDefault = $address->is_default;
        $address->delete();

        // Nếu địa chỉ bị xóa đang là mặc định, tự động đặt địa chỉ còn lại làm mặc định
        if ($wasDefault) {
            $nextDefault = $user->addresses()->first();
            if ($nextDefault) {
                $nextDefault->update(['is_default' => true]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Xóa địa chỉ thành công!'
        ], 200);
    }
}
