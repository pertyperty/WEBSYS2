@props(['name', 'label', 'type' => 'text', 'value' => null, 'bag' => 'default'])
@php($error = $errors->getBag($bag)->first($name))
<div>
    <label for="{{ $name }}" class="block text-sm font-medium">{{ $label }}</label>
    @if($type === 'password')
        <div class="relative" x-data="{ show: false }">
            <input id="{{ $name }}" name="{{ $name }}" type="password" :type="show ? 'text' : 'password'"
                @if($error) aria-invalid="true" aria-describedby="{{ $name }}-error {{ $attributes->get('aria-describedby') }}" @endif
                {{ $attributes->except($error ? ['aria-describedby'] : [])->class(['field pr-11']) }}>
            <button type="button" class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-lg text-slate-400 transition-colors hover:text-slate-700"
                @click="show = !show" :aria-label="show ? 'Hide password' : 'Show password'" :aria-pressed="show">
                <x-icon name="eye" class="size-4" x-show="!show" />
                <x-icon name="eye-off" class="size-4" x-show="show" x-cloak />
            </button>
        </div>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}"
            @if($error) aria-invalid="true" aria-describedby="{{ $name }}-error {{ $attributes->get('aria-describedby') }}" @endif
            {{ $attributes->except($error ? ['aria-describedby'] : [])->class(['field']) }}>
    @endif
    @if($error)<p id="{{ $name }}-error" class="field-error">{{ $error }}</p>@endif
</div>
