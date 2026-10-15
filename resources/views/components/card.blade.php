@props(['title' => null, 'description' => null])
<section {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white shadow-sm']) }}>
    @if($title || $description)
        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
            @if($title)<h2 class="text-base font-semibold text-slate-900">{{ $title }}</h2>@endif
            @if($description)<p class="mt-1 text-sm text-slate-500">{{ $description }}</p>@endif
        </div>
        <div class="px-5 py-5 sm:px-6">{{ $slot }}</div>
    @else
        <div class="px-5 py-5 sm:px-6">{{ $slot }}</div>
    @endif
</section>
