<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal TAT')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-50 text-slate-900 antialiased">
    @auth
        <x-sidebar />
        <div class="min-h-screen lg:pl-72">
            <x-topbar />
            <main>
                <x-container>
                    @if (session('success'))<x-alert type="success" class="mb-6">{{ session('success') }}</x-alert>@endif
                    @if (session('error'))<x-alert type="error" class="mb-6">{{ session('error') }}</x-alert>@endif
                    @if (session('warning'))<x-alert type="warning" class="mb-6">{{ session('warning') }}</x-alert>@endif
                    @if ($errors->any())
                        <x-alert type="error" class="mb-6"><p class="font-semibold">Periksa kembali data yang dimasukkan.</p><ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></x-alert>
                    @endif
                    @yield('content')
                </x-container>
            </main>
        </div>
    @else
        @yield('content')
    @endauth
@stack('scripts')
</body>
</html>



