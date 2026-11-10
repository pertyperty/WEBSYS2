@props(['name', 'label', 'type' => 'text', 'value' => null, 'bag' => 'default'])
@php($error = $errors->getBag($bag)->first($name))
<div>
    <label for="{{ $name }}" class="block text-sm font-medium">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" @if($type !== 'password') value="{{ old($name, $value) }}" @endif
        @if($error) aria-invalid="true" aria-describedby="{{ $name }}-error {{ $attributes->get('aria-describedby') }}" @endif
        {{ $attributes->except($error ? ['aria-describedby'] : [])->class(['field']) }}>
    @if($error)<p id="{{ $name }}-error" class="field-error">{{ $error }}</p>@endif
</div>
