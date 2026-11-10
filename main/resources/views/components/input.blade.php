@props(['name', 'label', 'type' => 'text', 'value' => null])
<div>
    <label for="{{ $name }}" class="block text-sm font-medium">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" @if($type !== 'password') value="{{ old($name, $value) }}" @endif {{ $attributes->class(['field']) }}>
</div>
