@props(['active' => false, 'href' => '#', 'disabled' => false])
<a href="{{ $disabled ? '#' : $href }}" @if($disabled) aria-disabled="true" tabindex="-1" @endif class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $disabled ? 'cursor-not-allowed text-slate-400' : ($active ? 'bg-bnn-600 text-white shadow-sm' : 'text-slate-600 hover:bg-bnn-50 hover:text-bnn-800') }}">
    <span class="flex h-5 w-5 items-center justify-center {{ $active ? 'text-bnn-100' : 'text-slate-400 group-hover:text-bnn-600' }}">{{ $icon }}</span>
    <span>{{ $slot }}</span>
</a>
