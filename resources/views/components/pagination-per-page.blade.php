@props(['options' => \App\Support\Pagination::options(), 'value' => \App\Support\Pagination::default()])

<form method="GET" action="{{ url()->current() }}" class="flex flex-wrap items-center gap-2 text-sm text-slate-600" data-per-page-form>
    @foreach(request()->except(['per_page']) as $key => $param)
        @if($key === 'page')
            @continue
        @endif
        @if(is_array($param))
            @foreach($param as $subKey => $subValue)
                <input type="hidden" name="{{ $key }}[{{ $subKey }}]" value="{{ $subValue }}">
            @endforeach
        @else
            <input type="hidden" name="{{ $key }}" value="{{ $param }}">
        @endif
    @endforeach
    <label id="per-page-label" for="per-page-select-trigger" class="font-medium">Tampilkan</label>
    <div data-custom-select data-select-name="per_page" class="relative inline-block">
        <button
            id="per-page-select-trigger"
            type="button"
            data-select-trigger
            role="combobox"
            aria-haspopup="listbox"
            aria-expanded="false"
            aria-controls="per-page-select-listbox"
            aria-labelledby="per-page-label"
            class="inline-flex h-9 min-w-[5.25rem] items-center justify-between gap-2 rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 shadow-sm outline-none transition hover:border-bnn-400 hover:shadow focus:border-bnn-500 focus:ring-2 focus:ring-bnn-200"
        >
            <span data-select-label class="truncate">{{ $value }}</span>
            <svg data-select-chevron aria-hidden="true" viewBox="0 0 20 20" fill="none" class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200">
                <path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>

        <select id="per-page-select" name="per_page" onchange="this.form.submit()" tabindex="-1" aria-hidden="true" class="sr-only">
            @foreach($options as $option)
                <option value="{{ $option }}" @selected((int) $value === (int) $option)>{{ $option }}</option>
            @endforeach
        </select>

        <div
            id="per-page-select-listbox"
            data-select-menu
            role="listbox"
            aria-labelledby="per-page-label"
            class="invisible pointer-events-none absolute bottom-full z-40 mb-2 max-h-60 w-full min-w-[5.25rem] origin-bottom -translate-y-1 overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 opacity-0 shadow-xl ring-1 ring-slate-900/5 transition-[opacity,transform,visibility] duration-200"
        >
            @foreach($options as $index => $option)
                <div id="per-page-select-option-{{ $index }}" role="option" aria-selected="{{ (int) $value === (int) $option ? 'true' : 'false' }}" data-value="{{ $option }}" tabindex="-1" class="flex cursor-pointer items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm outline-none transition hover:bg-bnn-50 hover:text-bnn-800 focus:bg-bnn-50 focus:text-bnn-800 {{ (int) $value === (int) $option ? 'bg-bnn-50 font-semibold text-bnn-800' : 'text-slate-700' }}">
                    <span class="truncate">{{ $option }}</span>
                    <span data-check class="{{ (int) $value === (int) $option ? 'inline-flex' : 'hidden' }} shrink-0 text-bnn-600">
                        <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" aria-hidden="true"><path d="m4.5 10.5 3.5 3.5 7.5-8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    </span>
                </div>
            @endforeach
        </div>
    </div>
    <span>data per halaman</span>
</form>
