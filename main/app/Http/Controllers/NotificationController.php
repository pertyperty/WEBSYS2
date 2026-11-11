<?php

namespace App\Http\Controllers;

use App\Actions\AssessEmployeeCompliance;
use App\Actions\ReconcileExpiryAlerts;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request, ReconcileExpiryAlerts $alerts): View
    {
        $request->validate(['unread' => ['nullable', 'in:1']]);
        $notifications = $this->visibleNotifications($request)->when($request->boolean('unread'), fn ($query) => $query->whereNull('read_at'))
            ->latest()->orderByDesc('id')->paginate(15)->withQueryString();
        $employees = User::visibleTo($request->user())->whereKey($notifications->getCollection()->map(fn ($notification) => $notification->data['employee_id']))
            ->with(AssessEmployeeCompliance::RELATIONS)->get()->keyBy('id');
        $events = $employees->map(fn (User $employee): array => $alerts->currentEvents($employee));
        $current = [];
        foreach ($notifications as $notification) {
            $data = $notification->data;
            $current[$notification->id] = collect($events->get($data['employee_id'], []))->contains(fn (array $event): bool => $event['document_id'] === $data['document_id'] && $event['event'] === $data['event']);
        }

        return view('notifications.index', compact('notifications', 'current'));
    }

    public function update(Request $request, string $notification): RedirectResponse
    {
        $this->visibleNotifications($request)->findOrFail($notification)->markAsRead();

        return back()->with('status', 'Notification marked as read.');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->visibleNotifications($request)->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('status', 'All visible notifications marked as read.');
    }

    private function visibleNotifications(Request $request): Builder
    {
        return $request->user()->notifications()->getQuery()->where('type', 'qualification_expiry')
            ->whereIn('data->employee_id', User::visibleTo($request->user())->select('id'));
    }
}
