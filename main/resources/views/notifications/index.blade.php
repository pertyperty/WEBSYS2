<x-layout title="Notifications">
    <div class="page-heading">
        <div>
            <h1 class="text-3xl font-bold">Notifications</h1>
            <p class="page-intro">Keep required qualifications up to date.</p>
        </div>
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button class="button-secondary" type="submit">Mark all as read</button>
        </form>
    </div>
    <div class="mb-5 flex gap-4 text-sm">
        <a class="text-link" href="{{ route('notifications.index') }}">All notifications</a>
        <a class="text-link" href="{{ route('notifications.index', ['unread' => 1]) }}">Unread</a>
    </div>
    <div class="record-list">
        @forelse($notifications as $notification)
            @php($data = $notification->data)
            <article class="record-row flex flex-wrap items-center justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-status-badge :status="$current[$notification->id] ? $data['event'] : 'superseded'" :label="$current[$notification->id] ? null : 'No longer current'" />
                        @if(!$notification->read_at)
                            <span class="text-xs font-semibold text-teal-800">Unread</span>
                        @endif
                    </div>
                    <h2 class="mt-2 font-semibold">{{ $data['qualification_name'] }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $data['employee_name'] }} &middot; {{ $data['event'] === 'expired' ? 'Expired' : 'Expires' }} {{ $data['expires_at'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $notification->created_at->timezone('Asia/Manila')->format('M j, Y') }}</p>
                    <a class="text-link mt-2 inline-block text-sm" href="{{ route('employees.show', $data['employee_id']) }}">View current readiness</a>
                </div>
                @if(!$notification->read_at)
                    <form method="POST" action="{{ route('notifications.update', $notification->id) }}">
                        @csrf
                        @method('PATCH')<button class="button-secondary" type="submit">Mark read</button>
                    </form>
                @endif
            </article>
        @empty
            <x-empty-state title="You're up to date" message="Expiry notifications will appear here when action is needed." />
        @endforelse
    </div>
    <div class="mt-6">{{ $notifications->links() }}</div>
</x-layout>
