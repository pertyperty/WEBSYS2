@props(['name' => 'overview'])
@php
    $paths = [
        'overview' => 'M3 10 12 3l9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z',
        'people' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M16 3a4 4 0 0 1 0 8M22 21v-2a4 4 0 0 0-3-3.87M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z',
        'matrix' => 'M3 3h18v18H3ZM3 9h18M3 15h18M9 3v18M15 3v18',
        'training' => 'm2 8 10-5 10 5-10 5ZM6 10v7c4 3 8 3 12 0v-7M22 8v7',
        'document' => 'M14 2H5a1 1 0 0 0-1 1v18a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V8ZM14 2v6h6M8 13h8M8 17h5',
        'settings' => 'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8ZM9 3h6l1 3 3 1 2 5-2 5-3 1-1 3H9l-1-3-3-1-2-5 2-5 3-1Z',
        'menu' => 'M4 6h16M4 12h16M4 18h16',
        'close' => 'm6 6 12 12M6 18 18 6',
        'arrow' => 'M5 12h14m-6-6 6 6-6 6',
        'check' => 'm5 12 4 4L19 6',
        'chevron' => 'm9 5 7 7-7 7',
        'search' => 'M21 21l-5-5M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z',
    ];
@endphp
<svg {{ $attributes->class(['size-5 shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] ?? $paths['overview'] }}" /></svg>
