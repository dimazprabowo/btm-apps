@props([
    'text' => '?',
    'size' => 'md',
    'gradient' => 'from-blue-500 to-blue-600',
])

@php
    $sizes = [
        'xs' => 'w-4 h-4 text-[9px]',
        'sm' => 'w-6 h-6 text-[10px]',
        'md' => 'w-8 h-8 text-xs',
        'lg' => 'w-11 h-11 text-sm',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $initials = strtoupper(substr($text, 0, 1));
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center justify-center rounded-full bg-gradient-to-br {$gradient} text-white font-semibold flex-shrink-0 {$sizeClass}"]) }}>
    {{ $initials }}
</span>
