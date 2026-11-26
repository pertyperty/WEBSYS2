@props(['title' => 'CheckPoint'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | CheckPoint</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
<a href="#main" class="skip-link">Skip to content</a>
@auth
    <aside class="app-sidebar hidden lg:flex">
        <a href="{{ route('dashboard') }}" class="brand"><span class="brand-mark"><x-icon name="check" class="size-5" /></span><span>CheckPoint<span class="brand-caption">Workforce readiness</span></span></a>
        <x-navigation />
    </aside>
    <div class="lg:pl-64" x-data @resize.window="if (window.innerWidth >= 1024) $refs.mobileNavigation.close()">
        <header class="app-topbar">
            <div class="flex min-w-0 items-center gap-3">
                <button type="button" class="icon-button lg:hidden" aria-label="Open navigation" aria-haspopup="dialog" @click="$refs.mobileNavigation.showModal()"><x-icon name="menu" /></button>
                <span class="truncate text-sm font-medium text-slate-500">{{ $title }}</span>
            </div>
            <div class="flex items-center gap-2">
                @can('view-notifications')
                    <a class="button-secondary relative" href="{{ route('notifications.index') }}" aria-label="Notifications, {{ $unreadAlertCount }} unread">
                        <x-icon name="bell" class="size-4" />
                        <span class="hidden sm:inline">Alerts</span>
                        @if($unreadAlertCount)
                            <span class="rounded-full bg-rose-600 px-1.5 py-0.5 text-[11px] font-bold leading-none text-white">{{ $unreadAlertCount }}</span>
                        @endif
                    </a>
                @endcan
                <details class="account-menu relative shrink-0" @keydown.escape="$el.open = false" @click.outside="$el.open = false">
                    <summary class="flex min-h-11 cursor-pointer list-none items-center gap-2.5 rounded-xl px-2 transition-colors hover:bg-slate-200/50 focus-visible:outline-2 focus-visible:outline-teal-700">
                        <span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="hidden text-left sm:block"><span class="block max-w-44 truncate text-sm font-semibold leading-tight">{{ auth()->user()->name }}</span><span class="block text-xs text-slate-500">{{ auth()->user()->role->label() }}</span></span>
                        <span class="sr-only">Account menu</span><x-icon name="chevron" class="size-3.5 rotate-90 text-slate-400" />
                    </summary>
                    <div class="absolute right-0 z-30 mt-2 w-60 rounded-xl bg-white p-2 shadow-lg ring-1 ring-slate-200">
                        <div class="border-b border-slate-100 px-3 py-2 sm:hidden">
                            <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-500">{{ auth()->user()->role->label() }}</p>
                        </div>
                        <a href="{{ route('profile') }}" class="nav-sub-link"><x-icon name="user" class="size-4 text-slate-400" />My profile</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="nav-sub-link w-full text-left"><x-icon name="logout" class="size-4 text-slate-400" />Sign out</button></form>
                    </div>
                </details>
            </div>
        </header>
        <dialog x-ref="mobileNavigation" class="mobile-navigation lg:hidden" aria-label="Navigation" @click="if ($event.target === $el) $el.close()">
            <div class="flex h-full w-72 max-w-[85vw] flex-col bg-white shadow-xl">
                <div class="flex h-16 items-center justify-between border-b border-slate-100 px-5"><a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 text-lg font-bold"><span class="brand-mark size-8"><x-icon name="check" class="size-4" /></span>CheckPoint</a><button type="button" class="icon-button" aria-label="Close navigation" @click="$refs.mobileNavigation.close()" autofocus><x-icon name="close" /></button></div>
                <div class="min-h-0 flex-1 overflow-y-auto"><x-navigation /></div>
            </div>
        </dialog>
@else
    <header class="mx-auto flex max-w-7xl items-center justify-between gap-2 px-4 py-5 sm:px-8">
        <a href="{{ route('login') }}" class="flex items-center gap-2.5 text-lg font-bold tracking-tight sm:text-xl"><span class="brand-mark size-9"><x-icon name="check" class="size-5" /></span>CheckPoint</a>
        <a href="{{ request()->routeIs('register') ? route('login') : route('register') }}" class="button-secondary">{{ request()->routeIs('register') ? 'Sign in' : 'Create account' }}</a>
    </header>
@endauth
<main id="main" tabindex="-1" class="app-content {{ auth()->guest() ? 'guest-content' : '' }}">
    @if(session('status'))
        <div role="status" class="notice notice-success mb-5 items-center justify-between" x-data="{ open: true }" x-show="open">
            <div class="flex items-center gap-3"><x-icon name="check" class="size-5 text-emerald-700" /><p>{{ session('status') }}</p></div>
            <button type="button" class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg text-emerald-800/70 hover:bg-emerald-100 hover:text-emerald-900" aria-label="Dismiss message" @click="open = false"><x-icon name="close" class="size-4" /></button>
        </div>
    @endif
    @php($messages = collect($errors->getBags())->flatMap(fn ($bag) => $bag->all())->unique())
    @if($messages->isNotEmpty())
        <div role="alert" tabindex="-1" class="notice notice-error mb-5">
            <div><p class="font-semibold">Please check the form</p><ul class="mt-1.5 list-inside list-disc space-y-1">@foreach($messages as $message)<li>{{ $message }}</li>@endforeach</ul></div>
        </div>
    @endif
    {{ $slot }}
</main>
@auth</div>@endauth
</body>
</html>
