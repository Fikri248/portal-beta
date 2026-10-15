@props([
    'name',
    'label',
    'options' => [],
    'required' => false,
    'disabled' => false,
    'value' => null,
    'placeholder' => 'Pilih opsi',
])

@php
    $providedId = $attributes->get('id', $name);
    $selectId = trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', $providedId), '-') ?: 'select';
    $triggerId = $selectId.'-trigger';
    $labelId = $selectId.'-label';
    $menuId = $selectId.'-listbox';
    $oldKey = trim((string) preg_replace('/\[(.*?)\]/', '.$1', $name), '.');
    $selectedValue = old($name, old($oldKey, $value));
    $selectedValue = $selectedValue === null ? '' : (string) $selectedValue;
    $selectedLabel = null;

    foreach ($options as $optionValue => $optionLabel) {
        if ((string) $optionValue === $selectedValue) {
            $selectedLabel = $optionLabel;
            break;
        }
    }
@endphp

<div data-custom-select data-select-name="{{ $name }}" class="relative" {{ $attributes->except(['class', 'id', 'name', 'required', 'disabled']) }}>
    <label id="{{ $labelId }}" for="{{ $triggerId }}" class="mb-1.5 block text-sm font-medium text-slate-700">
        {{ $label }} @if($required)<span class="text-red-500">*</span>@endif
    </label>

    <button
        id="{{ $triggerId }}"
        type="button"
        data-select-trigger
        role="combobox"
        aria-haspopup="listbox"
        aria-expanded="false"
        aria-controls="{{ $menuId }}"
        aria-labelledby="{{ $labelId }}"
        @if($required) aria-required="true" @endif
        @disabled($disabled)
        class="relative {{ $attributes->get('class', 'block h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-left text-sm text-slate-900 shadow-sm outline-none transition focus:border-bnn-500 focus:ring-2 focus:ring-bnn-200 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:opacity-60') }}"
    >
        <span data-select-label class="block truncate {{ $selectedLabel === null ? 'text-slate-400' : '' }}">{{ $selectedLabel ?? $placeholder }}</span>
        <svg data-select-chevron aria-hidden="true" viewBox="0 0 20 20" fill="none" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 transition-transform duration-200" style="position:absolute;" xmlns="http://www.w3.org/2000/svg">
            <path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </button>

    <select id="{{ $selectId }}" name="{{ $name }}" @required($required) @disabled($disabled) tabindex="-1" aria-hidden="true" class="sr-only">
        <option value="" @selected($selectedValue === '')>{{ $placeholder }}</option>
        @foreach($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selectedValue === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>

    <div
        id="{{ $menuId }}"
        data-select-menu
        role="listbox"
        aria-labelledby="{{ $labelId }}"
        class="invisible pointer-events-none absolute z-40 mt-2 max-h-60 w-full origin-top -translate-y-1 overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 opacity-0 shadow-lg ring-1 ring-slate-900/5 transition-[opacity,transform,visibility] duration-200"
    >
        @foreach($options as $optionValue => $optionLabel)
            <div id="{{ $selectId }}-option-{{ $loop->index }}" role="option" aria-selected="{{ $selectedValue === (string) $optionValue ? 'true' : 'false' }}" data-value="{{ $optionValue }}" tabindex="-1" class="cursor-pointer rounded-lg px-3 py-2 text-sm text-slate-700 outline-none transition hover:bg-bnn-50 focus:bg-bnn-50 {{ $selectedValue === (string) $optionValue ? 'bg-bnn-50 font-medium text-bnn-800' : '' }}">
                {{ $optionLabel }}
            </div>
        @endforeach
    </div>

    <x-form.error :for="$name" />
</div>
