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
        <a href="{{ route('dashboard') }}" class="brand"><span class="brand-mark"><x-icon name="check" class="size-6" /></span><span>CheckPoint<span class="brand-caption">Workforce readiness</span></span></a>
        <x-navigation />
    </aside>
    <div class="lg:pl-64" x-data @resize.window="if (window.innerWidth >= 1024) $refs.mobileNavigation.close()">
        <header class="app-topbar">
            <div class="flex min-w-0 items-center gap-3">
                <button type="button" class="icon-button lg:hidden" aria-label="Open navigation" aria-haspopup="dialog" @click="$refs.mobileNavigation.showModal()"><x-icon name="menu" /></button>
                <span class="truncate text-sm font-medium text-slate-500">{{ $title }}</span>
            </div>
            <details class="account-menu relative shrink-0" @keydown.escape="$el.open = false" @click.outside="$el.open = false">
                <summary class="flex min-h-11 cursor-pointer list-none items-center gap-3 rounded-xl px-2 focus-visible:outline-2 focus-visible:outline-teal-700">
                    <span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="hidden text-left sm:block"><span class="block max-w-48 truncate text-sm font-semibold">{{ auth()->user()->name }}</span><span class="block text-xs text-slate-500">{{ auth()->user()->role->label() }}</span></span>
                    <span class="sr-only">Account menu</span><x-icon name="chevron" class="size-3 rotate-90" />
                </summary>
                <div class="absolute right-0 z-30 mt-2 w-56 rounded-xl bg-white p-2 shadow-lg ring-1 ring-slate-200">
                    <a href="{{ route('profile') }}" class="nav-sub-link">My profile</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="nav-sub-link w-full text-left">Sign out</button></form>
                </div>
            </details>
        </header>
        <dialog x-ref="mobileNavigation" class="mobile-navigation lg:hidden" aria-label="Navigation" @click="if ($event.target === $el) $el.close()">
            <div class="flex h-full w-72 max-w-[85vw] flex-col bg-white">
                <div class="flex items-center justify-between px-5 py-5"><a href="{{ route('dashboard') }}" class="text-lg font-bold">CheckPoint</a><button type="button" class="icon-button" aria-label="Close navigation" @click="$refs.mobileNavigation.close()" autofocus><x-icon name="close" /></button></div>
                <div class="min-h-0 flex-1 overflow-y-auto"><x-navigation /></div>
            </div>
        </dialog>
@else
    <header class="mx-auto flex max-w-7xl items-center justify-between gap-2 px-4 py-6 sm:px-8">
        <a href="{{ route('login') }}" class="flex items-center gap-2 text-lg font-bold tracking-tight sm:gap-3 sm:text-xl"><span class="brand-mark size-8 sm:size-10"><x-icon name="check" class="size-6" /></span>CheckPoint</a>
        <a href="{{ request()->routeIs('register') ? route('login') : route('register') }}" class="button-secondary">{{ request()->routeIs('register') ? 'Sign in' : 'Create account' }}</a>
    </header>
@endauth
<main id="main" tabindex="-1" class="app-content {{ auth()->guest() ? 'guest-content' : '' }}">
    @if(session('status'))<div role="status" class="notice notice-success mb-6"><x-icon name="check" /><p>{{ session('status') }}</p></div>@endif
    @php($messages = collect($errors->getBags())->flatMap(fn ($bag) => $bag->all())->unique())
    @if($messages->isNotEmpty())
        <div role="alert" tabindex="-1" class="notice notice-error mb-6">
            <div><p class="font-semibold">Please check your information</p><ul class="mt-2 list-inside list-disc space-y-1">@foreach($messages as $message)<li>{{ $message }}</li>@endforeach</ul></div>
        </div>
    @endif
    {{ $slot }}
</main>
@auth</div>@endauth
</body>
</html>
