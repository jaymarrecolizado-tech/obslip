<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDeviceTokenRequest;
use App\Http\Resources\Api\V1\NotificationCollection;
use App\Http\Resources\Api\V1\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = Auth::user()
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(min($request->get('per_page', 15), 100));

        return $this->successResponse(
            new NotificationCollection($notifications)
        );
    }

    public function show($id): JsonResponse
    {
        $notification = Auth::user()
            ->notifications()
            ->findOrFail($id);

        // Mark as read
        $notification->markAsRead();

        return $this->successResponse(
            new NotificationResource($notification)
        );
    }

    public function markAsRead($id): JsonResponse
    {
        $notification = Auth::user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return $this->successResponse(
            new NotificationResource($notification),
            'Notification marked as read.'
        );
    }

    public function markAllAsRead(): JsonResponse
    {
        Auth::user()
            ->unreadNotifications
            ->markAsRead();

        return $this->successResponse(
            null,
            'All notifications marked as read.'
        );
    }

    public function unreadCount(): JsonResponse
    {
        return $this->successResponse([
            'count' => Auth::user()->unreadNotifications()->count(),
        ]);
    }

    public function storeDeviceToken(StoreDeviceTokenRequest $request): JsonResponse
    {
        $user = Auth::user();

        // Upsert token
        $token = \App\Models\DeviceToken::updateOrCreate(
            [
                'user_id' => $user->id,
                'token' => $request->token,
            ],
            [
                'platform' => $request->platform,
                'last_used_at' => now(),
            ]
        );

        return $this->successResponse([
            'id' => $token->id,
            'token' => $token->token,
            'platform' => $token->platform->value,
        ]);
    }

    public function deleteDeviceToken($id): JsonResponse
    {
        $token = \App\Models\DeviceToken::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $token->delete();

        return $this->successResponse(
            null,
            'Device token removed.'
        );
    }
}