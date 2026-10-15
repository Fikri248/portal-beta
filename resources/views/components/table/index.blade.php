@props(['minWidth' => '720px', 'stickyFirst' => false, 'stickyLast' => true, 'adaptive' => false])

@php($pinClasses = ($stickyFirst ? ' table-pin-first' : '').($stickyLast ? ' table-pin-last' : ''))
@php($tableClasses = $adaptive ? ' adaptive-table' : '')
<div @class(['table-container', 'overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm'])><div @class(['overflow-x-auto', 'table-scroll-adaptive' => $adaptive]) tabindex="0" role="region" aria-label="Tabel data (geser horizontal untuk melihat seluruh kolom)"><table class="responsive-table w-full divide-y divide-slate-200{{ $pinClasses }}{{ $tableClasses }}" @if($minWidth !== '0') style="--table-min-width: {{ $minWidth }}" @endif>{{ $colgroup ?? '' }}<thead class="bg-slate-50">{{ $head ?? '' }}</thead><tbody class="divide-y divide-slate-100 bg-white">{{ $slot }}</tbody></table></div>@isset($footer)<div class="border-t border-slate-100 bg-white px-5 py-4 sm:px-6">{{ $footer }}</div>@endisset</div>
