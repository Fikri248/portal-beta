@props(['compact' => false, 'dark' => false])
<div {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    <img src="{{ asset('images/logo-bnn.png') }}" alt="Logo BNN" class="h-11 w-11 shrink-0 rounded-full object-cover shadow-sm ring-2 ring-white/70 {{ $compact ? 'h-10 w-10' : '' }}">
    <div class="min-w-0">
        <div class="truncate text-sm font-semibold tracking-tight {{ $dark ? 'text-white' : 'text-slate-900' }}">Portal TAT</div>
        @unless($compact)
            <div class="truncate text-xs {{ $dark ? 'text-bnn-200' : 'text-slate-500' }}">Register Pengelolaan Data</div>
        @endunless
    </div>
</div>
