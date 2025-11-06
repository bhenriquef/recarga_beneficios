@php
    // Mapear classes FIXAS para não serem removidas pelo Tailwind
    $palette = [
        'indigo'  => ['bar' => 'bg-indigo-500',  'text' => 'text-indigo-600'],
        'green'   => ['bar' => 'bg-green-500',   'text' => 'text-green-600'],
        'rose'    => ['bar' => 'bg-rose-500',    'text' => 'text-rose-600'],
        'emerald' => ['bar' => 'bg-emerald-500', 'text' => 'text-emerald-600'],
        'orange'  => ['bar' => 'bg-orange-500',  'text' => 'text-orange-600'],
        'blue'    => ['bar' => 'bg-blue-500',    'text' => 'text-blue-600'],
    ];

    $barClass  = $palette[$color]['bar']  ?? $palette['indigo']['bar'];
    $textClass = $palette[$color]['text'] ?? $palette['indigo']['text'];
@endphp

<div class="bg-white shadow rounded-lg border border-gray-200">
    {{-- Barra superior colorida (100% compatível com purge) --}}
    <div class="h-1 w-full rounded-t-lg {{ $barClass }}"></div>

    <div class="p-4 text-center">
        <h4 class="text-sm text-gray-500">{{ $label }}</h4>
        <p
            class="font-semibold mt-1 {{ $textClass }} {{ $valueSize }} {{ $truncate ? 'truncate' : '' }}"
            title="{{ $truncate ? strip_tags($value) : '' }}"
            style="max-width: 100%;"
        >
            {!! $value !!}
        </p>
    </div>
</div>
