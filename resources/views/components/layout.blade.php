@props(['title' => 'CheckPoint'])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} | CheckPoint</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
<a href="#main" class="sr-only focus:not-sr-only focus:block focus:p-3">Skip to content</a>
<header class="border-b border-slate-800 bg-slate-950 text-white">
    <nav aria-label="Main navigation" class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-5 px-6 py-5">
        <a href="{{ url('/') }}" class="text-xl font-bold tracking-tight">CheckPoint<span class="ml-2 text-xs font-normal text-teal-300">SAFETY COMPLIANCE</span></a>
        <div class="flex flex-wrap items-center gap-5 text-sm">
            @auth
                <a href="{{ route('dashboard') }}">Dashboard</a>
                @can('manage-users')<a href="{{ route('admin.users') }}">Accounts</a>@endcan
                <a href="{{ route('profile') }}">Profile</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="cursor-pointer text-teal-300">Sign out</button></form>
            @else
                <a href="{{ route('login') }}">Sign in</a>
                <a href="{{ route('register') }}">Create account</a>
            @endauth
        </div>
    </nav>
</header>
<main id="main" class="mx-auto max-w-6xl px-6 py-10">
    @if(session('status'))<div role="status" class="mb-6 rounded-lg border border-teal-200 bg-teal-50 p-4 text-sm text-teal-900">{{ session('status') }}</div>@endif
    @if($errors->any() || $errors->getBags())
        @php($messages = collect($errors->getBags())->flatMap(fn ($bag) => $bag->all())->unique())
        @if($messages->isNotEmpty())
            <div role="alert" class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900">
                <p class="font-semibold">Please check the following:</p>
                <ul class="mt-2 list-inside list-disc">@foreach($messages as $message)<li>{{ $message }}</li>@endforeach</ul>
            </div>
        @endif
    @endif
    {{ $slot }}
</main>
<footer class="mx-auto max-w-6xl px-6 pb-8 text-xs text-slate-500">CheckPoint · Local classroom demonstration · Use synthetic records only</footer>
</body>
</html>
