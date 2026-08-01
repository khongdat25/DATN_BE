<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Ánh xạ vai trò từ FE sang DB
     *
     * @param string $role
     * @return string
     */
    private function mapRoleToDb(string $role): string
    {
        if ($role === 'Quản trị viên' || $role === 'admin') {
            return 'admin';
        }
        return 'user'; // Mặc định là 'user' (Khách hàng)
    }

    /**
     * Ánh xạ vai trò từ DB sang FE
     *
     * @param string $role
     * @return string
     */
    private function mapRoleToFe(?string $role): string
    {
        if ($role === 'admin') {
            return 'Quản trị viên';
        }
        return 'Khách hàng';
    }

    /**
     * Ánh xạ trạng thái từ FE sang DB
     *
     * @param string $status
     * @return string
     */
    private function mapStatusToDb(string $status): string
    {
        if ($status === 'blocked' || $status === 'locked' || $status === 'inactive') {
            return 'locked';
        }
        return 'active';
    }

    /**
     * Ánh xạ trạng thái từ DB sang FE
     *
     * @param string $status
     * @return string
     */
    private function mapStatusToFe(?string $status): string
    {
        if ($status === 'locked' || $status === 'inactive' || $status === 'blocked') {
            return 'blocked';
        }
        return 'active';
    }

    /**
     * @OA\Get(
     *     path="/api/admin/users",
     *     summary="[Admin] Danh sách người dùng (User Management)",
     *     tags={"Quản lý Người dùng (User)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="search", in="query", required=false, description="Tìm kiếm tên, email, sđt", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, description="Trạng thái (active/blocked/locked/all)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="role", in="query", required=false, description="Vai trò (admin/user/all)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=10)),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $status = $request->input('status');
            $dbStatus = $this->mapStatusToDb($status);
            $query->where('status', '=', $dbStatus);
        }

        if ($request->filled('role') && $request->input('role') !== 'all') {
            $role = $request->input('role');
            $dbRole = $this->mapRoleToDb($role);
            $query->where('role', '=', $dbRole);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 10);
        $paginator = $query->paginate($perPage);

        $mappedUsers = collect($paginator->items())->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $this->mapRoleToFe($user->role),
                'registeredDate' => $user->created_at ? $user->created_at->format('d/m/Y') : '',
                'status' => $this->mapStatusToFe($user->status),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'current_page' => $paginator->currentPage(),
                'data' => $mappedUsers,
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/users",
     *     summary="[Admin] Tạo mới người dùng",
     *     tags={"Quản lý Người dùng (User)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","phone","role","password"},
     *             @OA\Property(property="name", type="string", example="Trần Văn B"),
     *             @OA\Property(property="email", type="string", example="tranvanb@gmail.com"),
     *             @OA\Property(property="phone", type="string", example="0912345678"),
     *             @OA\Property(property="role", type="string", example="Khách hàng"),
     *             @OA\Property(property="password", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Tạo thành công")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'required|string|max:20',
            'role' => 'required|string|in:Quản trị viên,Khách hàng,admin,user',
            'password' => 'required|string|min:6',
        ], [
            'name.required' => 'Họ và tên là bắt buộc.',
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã tồn tại trên hệ thống.',
            'phone.required' => 'Số điện thoại là bắt buộc.',
            'role.required' => 'Vai trò là bắt buộc.',
            'role.in' => 'Vai trò chọn không hợp lệ.',
            'password.required' => 'Mật khẩu là bắt buộc.',
            'password.min' => 'Mật khẩu phải có tối thiểu 6 ký tự.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ!',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $this->mapRoleToDb($request->role),
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo người dùng mới thành công!',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $this->mapRoleToFe($user->role),
                'registeredDate' => $user->created_at ? $user->created_at->format('d/m/Y') : '',
                'status' => $this->mapStatusToFe($user->status),
            ]
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/users/{id}",
     *     summary="[Admin] Cập nhật vai trò người dùng",
     *     tags={"Quản lý Người dùng (User)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"role"},
     *             @OA\Property(property="role", type="string", example="Quản trị viên")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cập nhật thành công")
     * )
     */
    public function update(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|string|in:Quản trị viên,Khách hàng,admin,user',
        ], [
            'role.required' => 'Vai trò là bắt buộc.',
            'role.in' => 'Vai trò chọn không hợp lệ.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ!',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::findOrFail($id);
        $user->role = $this->mapRoleToDb($request->role);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật vai trò người dùng thành công!',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $this->mapRoleToFe($user->role),
                'status' => $this->mapStatusToFe($user->status),
            ]
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/users/{id}/status",
     *     summary="[Admin] Khóa / Mở khóa tài khoản người dùng",
     *     tags={"Quản lý Người dùng (User)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"active","blocked","locked"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cập nhật thành công")
     * )
     */
    public function updateStatus(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:active,blocked,locked',
        ], [
            'status.required' => 'Trạng thái là bắt buộc.',
            'status.in' => 'Trạng thái chọn không hợp lệ.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ!',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::findOrFail($id);
        
        if (auth()->id() == $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thể tự khóa tài khoản của chính mình!',
            ], 400);
        }

        $user->status = $this->mapStatusToDb($request->status);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái người dùng thành công!',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'status' => $this->mapStatusToFe($user->status),
            ]
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/users/{id}",
     *     summary="[Admin] Xóa người dùng",
     *     tags={"Quản lý Người dùng (User)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xóa thành công")
     * )
     */
    public function destroy(int $id)
    {
        $user = User::findOrFail($id);

        if (auth()->id() == $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thể tự xóa tài khoản của chính mình!',
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa tài khoản thành công!',
        ], 200);
    }
}
