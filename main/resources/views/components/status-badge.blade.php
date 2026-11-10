@props(['status', 'label' => null])
@php
    $tone = match ($status) {
        'eligible', 'satisfied', 'approved', 'completed', 'active', 'published' => 'positive',
        'not_eligible', 'rejected', 'expired' => 'negative',
        'pending', 'enrolled', 'expiring_soon', 'missing' => 'warning',
        default => 'neutral',
    };
    $text = $label ?? ucfirst(str_replace('_', ' ', $status));
@endphp
<span {{ $attributes->class(['status-badge', 'status-'.$tone]) }}><span class="status-dot" aria-hidden="true"></span>{{ $text }}</span>
