@props(['id', 'title', 'description' => null, 'type' => 'bar', 'labels' => [], 'datasets' => [], 'height' => 'h-72'])

@php
    $payload = ['type' => $type, 'labels' => array_values($labels), 'datasets' => array_values($datasets)];
@endphp

<section class="rounded-2xl border border-slate-200 bg-white shadow-sm" data-dashboard-chart>
    <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
        <h2 class="text-base font-semibold text-slate-900">{{ $title }}</h2>
        @if($description)<p class="mt-1 text-sm text-slate-500">{{ $description }}</p>@endif
    </div>
    <div class="px-5 py-5 sm:px-6">
        @if(collect($datasets)->flatMap(fn ($dataset) => $dataset['data'] ?? [])->sum() > 0)
            <div class="{{ $height }} relative">
                <canvas id="{{ $id }}" data-chart-payload='@json($payload)' class="h-full w-full" role="img" aria-label="{{ $title }}"></canvas>
            </div>
            <div data-chart-legend class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-600"></div>
        @else
            <div class="flex {{ $height }} items-center justify-center rounded-xl bg-slate-50 px-4 text-center">
                <p class="max-w-sm text-sm text-slate-500">Belum ada data Final untuk visual ini. Data akan muncul setelah register dilengkapi dan difinalisasi.</p>
            </div>
        @endif
    </div>
</section>
