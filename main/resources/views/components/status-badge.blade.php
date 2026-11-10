@props(['status', 'label' => null])
@php
    $tone = match ($status) {
        'eligible', 'satisfied', 'approved', 'completed', 'active', 'published', 'resolved' => 'positive',
        'not_eligible', 'rejected', 'expired' => 'negative',
        'pending', 'enrolled', 'expiring_soon', 'missing', 'open', 'in_progress', 'pending_verification' => 'warning',
        default => 'neutral',
    };
    $text = $label ?? ($status === 'pending_verification' ? 'Awaiting verification' : ucfirst(str_replace('_', ' ', $status)));
@endphp
<span {{ $attributes->class(['status-badge', 'status-'.$tone]) }}><span class="status-dot" aria-hidden="true"></span>{{ $text }}</span>
