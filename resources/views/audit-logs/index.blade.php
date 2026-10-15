@extends('layouts.app')

@section('title', 'Audit Log — Portal TAT')

@section('content')
<x-page-header title="Audit Log" description="Riwayat aktivitas penting pengguna dan perubahan data Portal TAT." />

<x-card class="mb-6" title="Filter Aktivitas" description="Gunakan filter untuk mempersempit peninjauan riwayat aktivitas.">
    <form method="GET" action="{{ route('audit-logs.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <x-form.input name="date_from" label="Dari tanggal" type="date" :value="$filters['date_from']" />
        <x-form.input name="date_to" label="Sampai tanggal" type="date" :value="$filters['date_to']" />
        <x-form.select name="user_id" label="User" :options="$users->mapWithKeys(fn ($user) => [$user->id => $user->name.' — '.$user->email])->all()" :value="$filters['user_id']" placeholder="Semua user" />
        <x-form.select name="action" label="Aktivitas" :options="$actions" :value="$filters['action']" placeholder="Semua aktivitas" />
        <x-form.select name="bnnk_id" label="BNNK" :options="$bnnks->pluck('name', 'id')->all()" :value="$filters['bnnk_id']" placeholder="Semua BNNK" />
        <x-form.select name="entity_type" label="Entitas" :options="$entityTypes" :value="$filters['entity_type']" placeholder="Semua entitas" />
        <div class="flex items-end gap-3 md:col-span-2 xl:col-span-3">
            <x-button type="submit">Terapkan Filter</x-button>
            <a href="{{ route('audit-logs.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
        </div>
    </form>
</x-card>

@if($logs->count())
    <x-table minWidth="1120px" :stickyFirst="true" :stickyLast="false" adaptive>
        <x-slot:colgroup>
            <colgroup>
                <col style="width: 15%">
                <col style="width: 20%">
                <col style="width: 10%">
                <col style="width: 12%">
                <col style="width: 15%">
                <col style="width: 11%">
                <col style="width: 8%">
                <col style="width: 9%">
            </colgroup>
        </x-slot:colgroup>
        <x-slot:head>
            <tr>
                <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Waktu</th>
                <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">User</th>
                <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Role</th>
                <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">BNNK</th>
                <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Aktivitas</th>
                <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Entitas</th>
                <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Referensi</th>
                <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Detail</th>
            </tr>
        </x-slot:head>

        @foreach($logs as $log)
            <tr class="align-top hover:bg-slate-50">
                <td class="table-cell whitespace-nowrap px-5 py-4 text-slate-700">
                    <time datetime="{{ \App\Support\ApplicationDateTime::iso8601($log->created_at) }}">{{ \App\Support\ApplicationDateTime::format($log->created_at) }}</time>
                </td>
                <td class="table-cell px-5 py-4 text-slate-700">
                    <div class="font-semibold text-slate-800">{{ $log->user?->name ?? 'System' }}</div>
                    @if($log->user)<div class="table-sub mt-1 text-slate-400">{{ $log->user->email }}</div>@endif
                </td>
                <td class="table-cell whitespace-nowrap px-5 py-4 text-slate-600">{{ $log->user?->role?->label() ?? '—' }}</td>
                <td class="table-cell whitespace-nowrap px-5 py-4 text-slate-600">{{ $log->bnnk?->name ?? '—' }}</td>
                <td class="table-cell px-5 py-4 font-semibold text-slate-800">{{ $log->actionLabel() }}</td>
                <td class="table-cell whitespace-nowrap px-5 py-4 text-slate-600">{{ match($log->entity_type) { 'TatRegister' => 'Register TAT', 'User' => 'User', default => $log->entity_type ?? '—' } }}</td>
                <td class="table-cell whitespace-nowrap px-5 py-4 text-slate-600">{{ $log->entity_id ?? '—' }}</td>
                <td class="table-cell whitespace-nowrap px-5 py-4 text-center"><x-table.action :href="route('audit-logs.show', $log)">Lihat Detail</x-table.action></td>
            </tr>
        @endforeach
    </x-table>
    <div class="mt-5 flex flex-wrap items-center justify-between gap-3"><x-pagination-per-page :value="$perPage ?? 25" />{{ $logs->links() }}</div>
@else
    <x-empty-state title="Belum ada aktivitas tercatat." description="Aktivitas yang sesuai filter akan muncul di halaman ini setelah tindakan berhasil dilakukan." />
@endif
@endsection
