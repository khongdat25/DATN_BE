<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * Gửi liên hệ mới (Public).
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
            'status' => 'pending', // mặc định là chờ xử lý
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gửi thông tin liên hệ thành công! Chúng tôi sẽ phản hồi sớm nhất.',
            'data' => $contact,
        ], 201);
    }

    /**
     * Admin: Lấy danh sách liên hệ.
     */
    public function index(Request $request)
    {
        $query = Contact::query();

        // Lọc theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Tìm kiếm theo tên hoặc email
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
     * Admin: Chi tiết một liên hệ.
     */
    public function show($id)
    {
        $contact = Contact::find($id);

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
     * Admin: Cập nhật trạng thái xử lý liên hệ.
     */
    public function update(Request $request, $id)
    {
        $contact = Contact::find($id);

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
     * Admin: Xóa thông tin liên hệ.
     */
    public function destroy($id)
    {
        $contact = Contact::find($id);

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
