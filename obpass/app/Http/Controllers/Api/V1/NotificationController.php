<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\DeviceToken;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->latest()->paginate(15);

        return $this->paginatedResponse(NotificationResource::collection($notifications));
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();

        if (! $notification) {
            return $this->errorResponse('Notification not found.', 404);
        }

        $notification->markAsRead();

        return $this->successResponse(null, 'Notification marked as read.');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return $this->successResponse(null, 'All notifications marked as read.');
    }

    public function storeDeviceToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'platform' => ['required', 'string', 'in:android,ios'],
        ]);

        DeviceToken::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'token' => $request->token,
            ],
            [
                'platform' => $request->platform,
            ]
        );

        return $this->successResponse(null, 'Device token registered.', 201);
    }
}
