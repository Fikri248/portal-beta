@props(['for' => null])
@if($for && $errors->has($for))<p id="{{ $for }}-error" class="mt-1.5 text-xs font-medium text-red-600">{{ $errors->first($for) }}</p>@endif
