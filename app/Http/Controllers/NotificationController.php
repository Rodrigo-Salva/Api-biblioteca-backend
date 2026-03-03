<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Service\NotificationService;
use App\Models\Notification;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        return response()->json($this->notificationService->getNotificationsForUser($request->user()));
    }

    public function markRead(Notification $notification)
    {
        return response()->json($this->notificationService->markAsRead($notification));
    }
}
