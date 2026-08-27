<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class NotificationController extends Controller
{
    /**
     * Get user notifications list & unread count
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated'
            ], 401);
        }

        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->take(30)
            ->get();

        $unreadCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'status' => 'success',
            'success' => true,
            'data' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Mark single notification as read
     */
    public function markAsRead(int $id, Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated'], 401);
        }

        $notif = Notification::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if ($notif) {
            $notif->is_read = true;
            $notif->save();
        }

        $unreadCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'status' => 'success',
            'success' => true,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Mark all notifications as read for logged in user
     */
    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated'], 401);
        }

        Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'status' => 'success',
            'success' => true,
            'unread_count' => 0
        ]);
    }

    /**
     * Helper method to send notification to a single user (DB + Email)
     */
    public static function sendToUser(int $userId, string $title, string $body, string $type = 'system', ?string $link = null)
    {
        $notif = Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'link' => $link,
            'is_read' => false,
        ]);

        // Send Email via Gmail SMTP
        try {
            $user = User::find($userId, ['*']);
            if ($user && !empty($user->email)) {
                Mail::raw("Xin chào {$user->name},\n\n{$body}\n\nTrải nghiệm dịch vụ ngay tại SaigonShoes: " . url('/'), function ($message) use ($user, $title) {
                    $message->to($user->email)
                            ->subject("[SaigonShoes] {$title}");
                });
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Send Email Error: ' . $e->getMessage());
        }

        return $notif;
    }

    /**
     * Helper method to send notification to ALL active users (DB + Email)
     */
    public static function sendToAllUsers(string $title, string $body, string $type = 'voucher', ?string $link = null)
    {
        $users = User::query()->whereNotNull('email', 'and')->get(['id', 'name', 'email']);
        $notifications = [];

        foreach ($users as $user) {
            $notifications[] = [
                'user_id' => $user->id,
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'link' => $link,
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Send Email via Gmail SMTP
            try {
                Mail::raw("Xin chào {$user->name},\n\n{$body}\n\nTrải nghiệm dịch vụ ngay tại SaigonShoes: " . url('/'), function ($message) use ($user, $title) {
                    $message->to($user->email)
                            ->subject("[SaigonShoes] {$title}");
                });
            } catch (\Exception $e) {
                // Ignore single email error during bulk broadcast
            }
        }

        if (!empty($notifications)) {
            Notification::insert($notifications);
        }
    }
}
