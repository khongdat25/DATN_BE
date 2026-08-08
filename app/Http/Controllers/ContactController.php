<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/contacts",
     *     summary="Gửi thông tin liên hệ (Công khai)",
     *     tags={"Liên hệ (Contact)"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","message"},
     *             @OA\Property(property="name", type="string", example="Nguyễn Văn A"),
     *             @OA\Property(property="email", type="string", example="nguyenvana@gmail.com"),
     *             @OA\Property(property="phone", type="string", example="0987654321"),
     *             @OA\Property(property="subject", type="string", example="Cần hỗ trợ đơn hàng"),
     *             @OA\Property(property="message", type="string", example="Tôi cần tư vấn thêm về đổi trả...")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Gửi thành công")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ], [
            'name.required' => 'Họ và tên là bắt buộc.',
            'email.required' => 'Email liên hệ là bắt buộc.',
            'email.email' => 'Địa chỉ email không đúng định dạng.',
            'message.required' => 'Nội dung tin nhắn không được để trống.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu gửi lên không hợp lệ!',
                'errors' => $validator->errors(),
            ], 422);
        }

        $contact = Contact::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gửi thông tin liên hệ thành công! Chúng tôi sẽ phản hồi sớm nhất.',
            'data' => $contact,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/contacts",
     *     summary="[Admin] Danh sách tin nhắn liên hệ",
     *     tags={"Liên hệ (Contact)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="status", in="query", required=false, description="Trạng thái (pending/processed)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="search", in="query", required=false, description="Tìm theo tên/email/chủ đề", @OA\Schema(type="string")),
     *     @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function index(Request $request)
    {
        $query = Contact::query();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $query->orderBy('created_at', 'desc');

        $limit = $request->input('limit', 15);
        $contacts = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách liên hệ thành công.',
            'data' => $contacts,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/contacts/{id}",
     *     summary="[Admin] Chi tiết tin nhắn liên hệ",
     *     tags={"Liên hệ (Contact)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function show(int $id)
    {
        $contact = Contact::find($id, ['*']);

        if (! $contact) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thông tin liên hệ!',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Chi tiết liên hệ.',
            'data' => $contact,
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/contacts/{id}",
     *     summary="[Admin] Phản hồi & Cập nhật trạng thái liên hệ",
     *     tags={"Liên hệ (Contact)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", example="processed"),
     *             @OA\Property(property="reply_content", type="string", example="Chào bạn, SaigonShoes đã xử lý...")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cập nhật thành công")
     * )
     */
    public function update(Request $request, int $id)
    {
        $contact = Contact::find($id, ['*']);

        if (! $contact) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thông tin liên hệ!',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,processed',
            'reply_content' => 'nullable|string',
        ], [
            'status.required' => 'Trạng thái xử lý là bắt buộc.',
            'status.in' => 'Trạng thái không hợp lệ (chỉ chấp nhận pending hoặc processed).',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ!',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->filled('reply_content')) {
            try {
                \Illuminate\Support\Facades\Mail::to($contact->email)->send(
                    new \App\Mail\ContactReplyMail($contact->name, $contact->message, $request->input('reply_content'))
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Contact reply email failed: ' . $e->getMessage());
            }
        }

        $contact->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái liên hệ thành công!',
            'data' => $contact,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/contacts/{id}",
     *     summary="[Admin] Xóa tin nhắn liên hệ",
     *     tags={"Liên hệ (Contact)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xóa thành công")
     * )
     */
    public function destroy(int $id)
    {
        $contact = Contact::find($id, ['*']);

        if (! $contact) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy liên hệ!',
            ], 404);
        }

        $contact->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa liên hệ thành công!',
        ], 200);
    }
}
