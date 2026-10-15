@php
    $accountUser = auth()->user();
    $accountLabel = $accountUser->isBnnk()
        ? ($accountUser->bnnk?->name ?? $accountUser->name)
        : $accountUser->name;
@endphp
<header class="sticky top-0 z-20 flex h-20 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
    <div class="flex min-w-0 items-center gap-3">
        <button type="button" data-sidebar-open class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-400 lg:hidden" aria-label="Buka navigasi" aria-controls="app-sidebar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        <div class="hidden min-w-0 sm:block"><p class="truncate text-sm font-medium text-slate-500">Portal Pengelolaan Data Register TAT</p></div>
    </div>
    <div class="relative flex items-center">
        <button type="button" data-account-toggle class="flex min-w-0 items-center gap-2 rounded-xl border border-slate-200 bg-white p-1.5 pr-2.5 shadow-sm hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-400" aria-label="Buka menu akun" aria-expanded="false" aria-controls="account-menu">
            <span class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-lg bg-bnn-50"><img src="{{ asset('images/logo-bnn.png') }}" alt="Logo BNN" class="h-8 w-8 object-cover"></span>
            <span class="hidden min-w-0 max-w-[12rem] truncate text-sm font-medium text-slate-700 sm:block">{{ $accountLabel }}</span>
            <span class="shrink-0 text-slate-400"><svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg></span>
        </button>
        <div id="account-menu" data-account-menu class="absolute right-0 top-14 hidden w-72 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
            <div class="border-b border-slate-100 px-3 py-2.5">
                <p class="truncate text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                <p class="mt-1 truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                <div class="mt-2 flex items-center gap-2">
                    <x-badge status="active" :label="auth()->user()->role->label()" />
                    @if(auth()->user()->isBnnk())
                        <span class="truncate text-xs text-slate-500">{{ auth()->user()->bnnk?->name ?? 'BNNK belum terhubung' }}</span>
                    @endif
                </div>
            </div>
            <div class="border-b border-slate-100 py-1">
                <a href="{{ route('profile.edit') }}" class="flex items-center rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Profil</a>
                <a href="{{ route('password.edit') }}" class="flex items-center rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Ganti Password</a>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-1">@csrf<button type="submit" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm font-medium text-red-600 hover:bg-red-50">Keluar</button></form>
        </div>
    </div>
</header>
