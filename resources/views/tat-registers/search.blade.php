@extends('layouts.app')
@section('title','Pencarian Riwayat TAT — Portal TAT')
@section('content')
@php($selectedMode=old('mode', request('mode', 'nik')))
<x-page-header title="Pencarian Riwayat TAT" description="Cari riwayat Register TAT berdasarkan NIK atau Nama dan Tanggal Lahir." />

<x-card title="Kriteria Pencarian" description="Pilih satu metode pencarian. Hasil hanya menampilkan data yang dapat Anda akses.">
    <form method="GET" action="{{ route('tat-registers.search') }}" class="space-y-5">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <x-form.select name="mode" label="Metode Pencarian" :options="['nik'=>'NIK','name_dob'=>'Nama + Tanggal Lahir']" :value="$selectedMode" required />
            <div class="hidden md:block"></div>
            <div data-search-fields="nik" class="{{ $selectedMode === 'nik' ? '' : 'hidden' }}">
                <x-form.input name="nik" label="NIK" :value="request('nik')" help="Masukkan NIK secara lengkap." />
            </div>
            <div data-search-fields="name_dob" class="{{ $selectedMode === 'name_dob' ? 'contents' : 'hidden' }}">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <x-form.input name="nama" label="Nama" :value="request('nama')" />
                    <x-form.input name="tanggal_lahir" label="Tanggal Lahir" type="date" :value="request('tanggal_lahir')" />
                </div>
            </div>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-3">
            <a href="{{ route('tat-registers.search') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
            <x-button type="submit">Cari Riwayat</x-button>
        </div>
    </form>
</x-card>

@if($searched)
    <div class="mt-6">
        <x-card title="Hasil Pencarian" :description="$mode === 'nik' ? 'Kriteria NIK: '.request('nik') : 'Kriteria: '.request('nama').' · '.request('tanggal_lahir')">
            @if($records?->count())
                <p class="mb-4 text-sm text-slate-500">{{ $records->total() }} riwayat ditemukan dalam cakupan akses Anda.</p>
                <x-table minWidth="1120px" :stickyFirst="true" :stickyLast="false" adaptive>
                    <x-slot:colgroup>
                        <colgroup>
                            @if(auth()->user()->isBnnp())
                                <col style="width: 14%">
                                <col style="width: 13%">
                                <col style="width: 13%">
                                <col style="width: 11%">
                                <col style="width: 11%">
                                <col style="width: 11%">
                                <col style="width: 10%">
                                <col style="width: 9%">
                                <col style="width: 8%">
                            @else
                                <col style="width: 15%">
                                <col style="width: 14%">
                                <col style="width: 14%">
                                <col style="width: 12%">
                                <col style="width: 13%">
                                <col style="width: 11%">
                                <col style="width: 10%">
                                <col style="width: 11%">
                            @endif
                        </colgroup>
                    </x-slot:colgroup>
                    <x-slot:head>
                        <tr>
                            <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">No Register</th>
                            <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Nama</th>
                            <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">NIK</th>
                            <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Tanggal Lahir</th>
                            @if(auth()->user()->isBnnp())
                                <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">BNNK</th>
                            @endif
                            <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Tgl Pelaksanaan</th>
                            <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Rekomendasi</th>
                            <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                        </tr>
                    </x-slot:head>

                    @foreach($records as $record)
                        <tr class="align-top hover:bg-slate-50">
                            <td class="table-cell whitespace-nowrap px-5 py-4 font-medium text-slate-800">{{ $record->no_register ?? '—' }}</td>
                            <td class="table-cell px-5 py-4 text-slate-700">{{ $record->nama ?? '—' }}</td>
                            <td class="table-cell whitespace-nowrap px-5 py-4 text-slate-600">{{ $record->nik ?? '—' }}</td>
                            <td class="table-cell whitespace-nowrap px-5 py-4 text-slate-600">{{ $record->tanggal_lahir?->format('d M Y') ?? '—' }}</td>
                            @if(auth()->user()->isBnnp())
                                <td class="table-cell whitespace-nowrap px-5 py-4 text-slate-600">{{ $record->bnnk->name }}</td>
                            @endif
                            <td class="table-cell whitespace-nowrap px-5 py-4 text-slate-600">{{ $record->tanggal_pelaksanaan?->format('d M Y') ?? '—' }}</td>
                            <td class="table-cell px-5 py-4 text-slate-600">{{ $record->rekomendasi_tat?->label() ?? '—' }}</td>
                            <td class="table-cell px-5 py-4 text-center"><x-badge :status="$record->status->value" :label="$record->status?->label() ?? '—'" /></td>
                            <td class="table-cell whitespace-nowrap px-5 py-4 text-center"><x-table.action :href="route('tat-registers.show', $record)">Detail</x-table.action></td>
                        </tr>
                    @endforeach
                </x-table>
                <div class="mt-5 flex flex-wrap items-center justify-between gap-3"><x-pagination-per-page :value="$perPage ?? 25" />{{ $records->links() }}</div>
            @else
                <x-empty-state title="Riwayat tidak ditemukan" :description="auth()->user()->isBnnk() ? 'Tidak ditemukan data yang dapat diakses untuk kriteria pencarian tersebut.' : 'Tidak ditemukan Register TAT untuk kriteria pencarian tersebut.'" />
            @endif
        </x-card>
    </div>
@else
    <div class="mt-6">
        <x-empty-state title="Mulai pencarian" description="Masukkan kriteria untuk melihat riwayat Register TAT yang dapat Anda akses." />
    </div>
@endif

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const mode = document.querySelector('[name="mode"]');
        const groups = document.querySelectorAll('[data-search-fields]');
        if (!mode || !groups.length) return;
        const syncFields = () => groups.forEach((group) => {
            const active = group.dataset.searchFields === mode.value;
            group.classList.toggle('hidden', !active);
            if (group.dataset.searchFields === 'name_dob') group.classList.toggle('contents', active);
        });
        mode.addEventListener('change', syncFields);
        syncFields();
    });
</script>
@endpush
@endsection
