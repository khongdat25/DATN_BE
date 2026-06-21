<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="Đăng ký tài khoản người dùng mới",
     *     description="Tạo một tài khoản user mới sử dụng Email, Số điện thoại, Mật khẩu từ Frontend và tự động đăng nhập bằng phiên làm việc.",
     *     tags={"Xác thực (Authentication)"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Dữ liệu đăng ký tài khoản",
     *         @OA\JsonContent(
     *             required={"email","phone","password"},
     *             @OA\Property(property="email", type="string", format="email", example="username@gmail.com"),
     *             @OA\Property(property="phone", type="string", example="0123456789"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Đăng ký thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Đăng ký tài khoản thành công!"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="username"),
     *                     @OA\Property(property="email", type="string", example="username@gmail.com"),
     *                     @OA\Property(property="phone", type="string", example="0123456789")
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dữ liệu đầu vào không hợp lệ",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Dữ liệu đầu vào không hợp lệ!"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="Email này đã tồn tại trên hệ thống."))
     *             )
     *         )
     *     )
     * )
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255|unique:users', // Kiểm tra email trùng trong bảng users
            'phone' => 'required|string|max:20', // Đã loại bỏ unique theo yêu cầu của bạn
            'password' => 'required|string|min:6', // 6 ký tự tối thiểu cho tiện sử dụng
        ], [
            'email.required' => 'Email là bắt buộc.',
            'email.string' => 'Email phải là chuỗi ký tự.',
            'email.email' => 'Email không đúng định dạng.',
            'email.max' => 'Email không được vượt quá 255 ký tự.',
            'email.unique' => 'Email này đã tồn tại trên hệ thống.',
            'phone.required' => 'Số điện thoại là bắt buộc.',
            'phone.string' => 'Số điện thoại phải là chuỗi ký tự.',
            'phone.max' => 'Số điện thoại không được vượt quá 20 ký tự.',
            'password.required' => 'Mật khẩu là bắt buộc.',
            'password.string' => 'Mật khẩu phải là chuỗi ký tự.',
            'password.min' => 'Mật khẩu phải có tối thiểu 6 ký tự.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu đầu vào không hợp lệ!',
                'errors' => $validator->errors()
            ], 422);
        }

        // Tự động tách phần đầu Email để gán làm Tên hiển thị (name) mặc định
        $name = explode('@', $request->email)[0];

        // Tạo người dùng mới
        $user = User::create([
            'name' => $name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'status' => 'active',
        ]);

        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $token = $guard->login($user);

        return $this->respondWithToken($token, $user);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Đăng nhập người dùng",
     *     description="Xác thực thông tin đăng nhập (Email & Mật khẩu) và tạo phiên đăng nhập cho người dùng.",
     *     tags={"Xác thực (Authentication)"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Dữ liệu đăng nhập",
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="username@gmail.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Đăng nhập thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Đăng nhập thành công!"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="username"),
     *                     @OA\Property(property="email", type="string", example="username@gmail.com"),
     *                     @OA\Property(property="phone", type="string", example="0123456789")
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Thông tin đăng nhập sai",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Tài khoản hoặc mật khẩu không chính xác!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dữ liệu đầu vào không hợp lệ",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Dữ liệu đầu vào không hợp lệ!"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="Email là bắt buộc."))
     *             )
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không đúng định dạng.',
            'password.required' => 'Mật khẩu là bắt buộc.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu đầu vào không hợp lệ!',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::query()->where(['email' => $request->email])->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản hoặc mật khẩu không chính xác!'
            ], 401);
        }

        // Chặn login với tài khoản inactive/locked
        if (in_array($user->status, ['locked', 'inactive'])) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động!'
            ], 403);
        }

        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $token = $guard->login($user);
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo mã xác thực!'
            ], 500);
        }

        return $this->respondWithToken($token, $user);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Đăng xuất người dùng",
     *     description="Đăng xuất và hủy phiên làm việc hiện tại của tài khoản đang đăng nhập.",
     *     tags={"Xác thực (Authentication)"},
     *     @OA\Response(
     *         response=200,
     *         description="Đăng xuất thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Đăng xuất thành công!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Chưa đăng nhập / Phiên không hợp lệ",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        auth('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Đăng xuất thành công!'
        ], 200);
    }

    /**
     * Làm mới token (Refresh token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $token = $guard->refresh();
        return $this->respondWithToken($token, $user);
    }

    /**
     * Định dạng kết quả trả về khi tạo token thành công.
     *
     * @param  string $token
     * @param  \App\Models\User $user
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Cập nhật thông tin hồ sơ người dùng.
     *
     * @param  Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'birthday' => 'nullable|date',
            'gender' => 'nullable|string|max:20',
        ], [
            'name.required' => 'Họ và tên là bắt buộc.',
            'phone.required' => 'Số điện thoại là bắt buộc.',
            'birthday.date' => 'Ngày sinh không đúng định dạng ngày.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ!',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'birthday' => $request->birthday,
            'gender' => $request->gender,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật hồ sơ cá nhân thành công!',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'status' => $user->status,
                    'birthday' => $user->birthday,
                    'gender' => $user->gender,
                ]
            ]
        ], 200);
    }

    /**
     * Đăng nhập bằng tài khoản Google.
     *
     * @param  Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginWithGoogle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ], [
            'token.required' => 'Mã xác thực Google là bắt buộc.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ!',
                'errors' => $validator->errors()
            ], 422);
        }

        $idToken = $request->token;
        $clientId = config('services.google.client_id');

        try {
            $client = new \Google_Client(['client_id' => $clientId]);
            $payload = $client->verifyIdToken($idToken);

            if (!$payload) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã xác thực Google không hợp lệ hoặc đã hết hạn!'
                ], 401);
            }

            $googleId = $payload['sub'];
            $email = $payload['email'];
            $name = $payload['name'] ?? explode('@', $email)[0];

            // Tìm user theo google_id hoặc email
            $user = User::query()->where(['google_id' => $googleId])
                ->orWhere(['email' => $email])
                ->first();

            if ($user) {
                // Nếu khớp email nhưng chưa lưu google_id thì cập nhật thêm
                if (empty($user->google_id)) {
                    $user->google_id = $googleId;
                    $user->save();
                }
            } else {
                // Tạo user mới nếu chưa tồn tại
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'google_id' => $googleId,
                    'password' => Hash::make(Str::random(16)),
                    'role' => 'user',
                    'status' => 'active',
                ]);
            }

            // Chặn login với tài khoản inactive/locked
            if (in_array($user->status, ['locked', 'inactive'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động!'
                ], 403);
            }

            /** @var \Tymon\JWTAuth\JWTGuard $guard */
            $guard = auth('api');
            $token = $guard->login($user);
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể tạo mã xác thực hệ thống!'
                ], 500);
            }

            return $this->respondWithToken($token, $user);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi kết nối xác thực Google!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Định dạng kết quả trả về khi tạo token thành công.
     *
     * @param  string $token
     * @param  User $user
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token, $user)
    {
        return response()->json([
            'success' => true,
            'message' => 'Thao tác thành công!',
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'status' => $user->status,
                    'birthday' => $user->birthday,
                    'gender' => $user->gender,
                ]
            ]
        ], 200);
    }

    /**
     * Gửi email khôi phục mật khẩu.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|exists:users,email',
        ], [
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không đúng định dạng.',
            'email.exists' => 'Email không tồn tại trong hệ thống.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu đầu vào không hợp lệ!',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->email;
        $token = Str::random(60);

        // Lưu hoặc cập nhật token trong database
        $table = 'password_reset_tokens';
        DB::table($table)->updateOrInsert(
            ['email' => $email],
            [
                'token' => $token,
                'created_at' => Carbon::now()
            ]
        );

        // Gửi Mail
        try {
            Mail::to($email)->send(new \App\Mail\ResetPasswordMail($token, $email));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi email khôi phục mật khẩu. Vui lòng kiểm tra cấu hình mail!',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Liên kết khôi phục mật khẩu đã được gửi đến email của bạn!'
        ], 200);
    }

    /**
     * Đặt lại mật khẩu mới.
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|string|email|exists:users,email',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'token.required' => 'Mã xác thực (token) là bắt buộc.',
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không đúng định dạng.',
            'email.exists' => 'Email không tồn tại trong hệ thống.',
            'password.required' => 'Mật khẩu là bắt buộc.',
            'password.min' => 'Mật khẩu phải có tối thiểu 6 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu đầu vào không hợp lệ!',
                'errors' => $validator->errors()
            ], 422);
        }

        $table = 'password_reset_tokens';
        $resetRecord = DB::table($table)
            ->where([
                'email' => $request->email,
                'token' => $request->token,
            ])
            ->first();

        if (!$resetRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Mã xác nhận hoặc email không hợp lệ!'
            ], 400);
        }

        // Kiểm tra thời gian hết hạn (60 phút)
        if (Carbon::parse($resetRecord->created_at)->addMinutes(60)->isPast()) {
            // Xóa token đã hết hạn
            $table = 'password_reset_tokens';
            DB::table($table)->where(['email' => $request->email])->delete();

            return response()->json([
                'success' => false,
                'message' => 'Liên kết khôi phục mật khẩu đã hết hạn!'
            ], 400);
        }

        // Cập nhật mật khẩu user
        $user = User::query()->where(['email' => $request->email])->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Xóa token sau khi reset thành công
        $table = 'password_reset_tokens';
        DB::table($table)->where(['email' => $request->email])->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đặt lại mật khẩu thành công!'
        ], 200);
    }
}
