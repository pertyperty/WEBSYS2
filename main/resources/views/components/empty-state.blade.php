@props(['title', 'message' => null, 'icon' => 'document'])
<div {{ $attributes->class(['empty-state']) }}>
    <span class="mb-4 inline-flex size-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-800"><x-icon :name="$icon" class="size-6" /></span>
    <h2 class="text-base font-semibold">{{ $title }}</h2>
    @if($message)<p class="mx-auto mt-2 max-w-md text-sm text-slate-600">{{ $message }}</p>@endif
    @if($slot->isNotEmpty())<div class="mt-5">{{ $slot }}</div>@endif
</div>
