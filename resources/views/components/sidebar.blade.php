<aside id="app-sidebar" class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0">
    <div class="flex h-20 items-center border-b border-slate-100 px-6">
        <x-app-logo />
        <button type="button" data-sidebar-close class="ml-auto rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400 lg:hidden" aria-label="Tutup navigasi">
            <span aria-hidden="true" class="text-xl leading-none">&times;</span>
        </button>
    </div>
    <nav class="flex-1 space-y-7 overflow-y-auto px-4 py-6" aria-label="Navigasi utama">
        <div>
            <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-widest text-slate-400">Main</p>
            <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                <x-slot:icon><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13h7V3H3v10Zm11 8h7V3h-7v18ZM3 21h7v-4H3v4Z"/></svg></x-slot:icon>
                Dashboard
            </x-sidebar-link>
        </div>
        <div>
            <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-widest text-slate-400">Register TAT</p>
            <div class="space-y-1">
                <x-sidebar-link :href="route('reports.tat.index')" :active="request()->routeIs('reports.tat.*')">
                    <x-slot:icon><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M8 16v-5m4 5V8m4 8V3"/></svg></x-slot:icon>
                    Laporan
                </x-sidebar-link>
                @if(auth()->user()->isBnnk())
                    <x-sidebar-link :href="route('tat-registers.import.index')" :active="request()->routeIs('tat-registers.import.*')">
                        <x-slot:icon><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg></x-slot:icon>
                        Import Data
                    </x-sidebar-link>
                @endif
                <x-sidebar-link :href="route('tat-registers.index')" :active="request()->routeIs('tat-registers.index','tat-registers.show','tat-registers.edit','tat-registers.create')">
                    <x-slot:icon><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21V5.5Zm0 0V21m0 0h16"/></svg></x-slot:icon>
                    Data Register TAT
                </x-sidebar-link>
                <x-sidebar-link :href="route('tat-registers.drafts')" :active="request()->routeIs('tat-registers.drafts')">
                    <x-slot:icon><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/><circle cx="12" cy="12" r="9"/></svg></x-slot:icon>
                    Draft
                </x-sidebar-link>
                <x-sidebar-link :href="route('tat-registers.archive')" :active="request()->routeIs('tat-registers.archive')">
                    <x-slot:icon><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M6 7l1 13h10l1-13M9 7V4h6v3"/></svg></x-slot:icon>
                    Arsip
                </x-sidebar-link>
                <x-sidebar-link :href="route('tat-registers.search')" :active="request()->routeIs('tat-registers.search')">
                    <x-slot:icon><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" stroke-linejoin="round" d="m16 16 4.5 4.5"/></svg></x-slot:icon>
                    Pencarian Riwayat
                </x-sidebar-link>
            </div>
        </div>
        @if(auth()->user()->isBnnp())
            <div>
                <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-widest text-slate-400">Administration</p>
                <x-sidebar-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                    <x-slot:icon><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0"/></svg></x-slot:icon>
                    Kelola User
                </x-sidebar-link>
                <x-sidebar-link :href="route('audit-logs.index')" :active="request()->routeIs('audit-logs.*')">
                    <x-slot:icon><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21V5.5Zm0 0V21m4-12h8m-8 4h5"/></svg></x-slot:icon>
                    Audit Log
                </x-sidebar-link>
            </div>
        @endif
    </nav>
    <div class="border-t border-slate-100 p-4">
        <div class="rounded-xl bg-bnn-50 px-3 py-3">
            <p class="text-xs font-semibold text-bnn-800">{{ auth()->user()->role->label() }}</p>
            <p class="mt-1 truncate text-xs text-bnn-600">{{ auth()->user()->bnnk?->name ?? 'Akses seluruh wilayah' }}</p>
        </div>
    </div>
</aside>
<div id="sidebar-backdrop" data-sidebar-close class="fixed inset-0 z-30 hidden bg-slate-900/30 lg:hidden"></div>
