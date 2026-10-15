@props(['type' => 'info'])
@php($styles=['success'=>'border-emerald-200 bg-emerald-50 text-emerald-800','error'=>'border-red-200 bg-red-50 text-red-800','warning'=>'border-amber-200 bg-amber-50 text-amber-800','info'=>'border-bnn-200 bg-bnn-50 text-bnn-800'][$type] ?? 'border-slate-200 bg-slate-50 text-slate-800')
<div {{ $attributes->merge(['class' => 'rounded-xl border px-4 py-3 text-sm '.$styles]) }} role="alert">{{ $slot }}</div>
