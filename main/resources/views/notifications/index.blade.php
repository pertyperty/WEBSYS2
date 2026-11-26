<x-layout title="Notifications">
    @php($unreadOnly = request()->boolean('unread'))
    <div class="page-heading">
        <div><h1 class="text-2xl font-bold sm:text-3xl">Notifications</h1></div>
        @if($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button class="button-secondary" type="submit">Mark all read</button>
            </form>
        @endif
    </div>
    <div class="mb-5">
        <div class="tab-group" role="navigation" aria-label="Filter notifications">
            <a class="tab-link {{ ! $unreadOnly ? 'tab-link-active' : '' }}" href="{{ route('notifications.index') }}" @if(! $unreadOnly) aria-current="page" @endif>All</a>
            <a class="tab-link {{ $unreadOnly ? 'tab-link-active' : '' }}" href="{{ route('notifications.index', ['unread' => 1]) }}" @if($unreadOnly) aria-current="page" @endif>
                Unread @if($unreadCount > 0)<span class="rounded-full bg-teal-100 px-2 py-0.5 text-xs font-semibold text-teal-900">{{ $unreadCount }}</span>@endif
            </a>
        </div>
    </div>
    <div class="record-list">
        @forelse($notifications as $notification)
            @php($data = $notification->data)
            <article class="record-row flex flex-wrap items-center justify-between gap-4 {{ $notification->read_at ? '' : 'bg-teal-50/30' }}">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-status-badge :status="$current[$notification->id] ? $data['event'] : 'superseded'" :label="$current[$notification->id] ? null : 'No longer current'" />
                        @if(! $notification->read_at)
                            <span class="text-xs font-semibold text-teal-800">Unread</span>
                        @endif
                        <span class="text-xs text-slate-400">{{ $notification->created_at->timezone('Asia/Manila')->format('M j, Y') }}</span>
                    </div>
                    <h2 class="mt-1.5 font-semibold text-slate-900">{{ $data['qualification_name'] }}</h2>
                    <p class="mt-0.5 text-sm text-slate-500">{{ $data['employee_name'] }} &middot; {{ $data['event'] === 'expired' ? 'Expired' : 'Expires' }} {{ $data['expires_at'] }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a class="text-link" href="{{ route('employees.show', $data['employee_id']) }}">View readiness</a>
                    @if(! $notification->read_at)
                        <form method="POST" action="{{ route('notifications.update', $notification->id) }}">
                            @csrf
                            @method('PATCH')
                            <button class="button-secondary" type="submit">Mark read</button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <x-empty-state :title="$unreadOnly ? 'No unread notifications' : 'You are all caught up'" message="Qualification expiry alerts will appear here." icon="bell" />
        @endforelse
    </div>
    <div class="mt-6">{{ $notifications->links() }}</div>
</x-layout>
