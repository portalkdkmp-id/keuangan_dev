<?php

namespace App\Http\Controllers;

use App\Services\Audit\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('notifications.view');

        return Inertia::render('Notifications/Index', [
            'notifications' => $request->user()->notifications()->latest()->paginate(20),
        ]);
    }

    public function markAsRead(Request $request, DatabaseNotification $notification, AuditLogService $auditLog): RedirectResponse
    {
        Gate::authorize('notifications.mark-read');
        abort_unless($notification->notifiable_id === $request->user()->id, 403);
        $notification->markAsRead();
        $auditLog->record('notification.marked_read', 'Notification ditandai sudah dibaca.');

        return redirect($notification->data['url'] ?? route('notifications.index'));
    }

    public function markAllAsRead(Request $request, AuditLogService $auditLog): RedirectResponse
    {
        Gate::authorize('notifications.mark-read');
        $request->user()->unreadNotifications->markAsRead();
        $auditLog->record('notification.marked_all_read', 'Semua notification ditandai sudah dibaca.');

        return back()->with('success', 'Semua notification ditandai sudah dibaca.');
    }
}
