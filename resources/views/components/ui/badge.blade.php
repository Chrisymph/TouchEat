@props(['variant' => 'default'])

@php
    $baseClasses = 'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors';
    
    $variants = [
        'default' => 'border-transparent bg-blue-600 text-white',
        'secondary' => 'border-transparent bg-gray-600 text-white',
        'destructive' => 'border-transparent bg-red-600 text-white',
        'outline' => 'text-gray-800 border-gray-300',
    ];
    
    $classes = $baseClasses . ' ' . $variants[$variant];
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>