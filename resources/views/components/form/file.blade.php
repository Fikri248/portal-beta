@props(['name', 'label', 'accept' => null, 'required' => false, 'help' => null])
<div>
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-slate-700">{{ $label }} @if($required)<span class="text-red-500">*</span>@endif</label>
    <input id="{{ $name }}" name="{{ $name }}" type="file" @if($accept) accept="{{ $accept }}" @endif @if($required) required @endif {{ $attributes->merge(['class' => 'block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm outline-none file:mr-4 file:rounded-md file:border-0 file:bg-bnn-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-bnn-700 focus:border-bnn-500 focus:ring-2 focus:ring-bnn-200']) }} aria-describedby="{{ $errors->has($name) ? $name.'-error' : '' }}">
    @if($help)<p class="mt-1.5 text-xs text-slate-500">{{ $help }}</p>@endif
    <x-form.error :for="$name" />
</div>
