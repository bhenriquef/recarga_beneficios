@props(['title' => '', 'value' => '', 'prefix' => null])

<div class="bg-white overflow-hidden shadow-sm rounded-xl p-5">
    <div class="text-sm text-gray-500">{{ $title }}</div>
    <div class="mt-2 text-2xl font-semibold text-gray-800">
        @if($prefix)
            <span class="text-gray-500 mr-1">{{ $prefix }}</span>
        @endif
        {{ $value }}
    </div>
</div>
