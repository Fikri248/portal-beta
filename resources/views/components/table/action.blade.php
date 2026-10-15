@props(['variant' => 'neutral', 'href' => null, 'type' => 'button'])

@php($styles = [
    'neutral' => 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 focus:ring-bnn-400',
    'danger' => 'border-red-200 bg-red-50 text-red-700 hover:border-red-300 hover:bg-red-100 hover:text-red-800 focus:ring-red-400',
    'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:border-emerald-300 hover:bg-emerald-100 hover:text-emerald-800 focus:ring-emerald-400',
    'primary' => 'border-transparent bg-slate-900 text-white hover:bg-slate-700 focus:ring-slate-400',
][$variant] ?? 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 focus:ring-bnn-400')

@php($classes = 'inline-flex h-8 shrink-0 items-center justify-center whitespace-nowrap rounded-lg border px-3 text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-offset-1 '.$styles)

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
