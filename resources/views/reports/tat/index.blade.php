@extends('layouts.app')

@section('title', 'Laporan Register TAT — Portal TAT')

@section('content')
    @php($exportQuery = request()->except('page'))

    <x-page-header title="Laporan Register TAT" description="Rekap Register TAT Final berdasarkan tanggal pelaksanaan. Data arsip dan Draft tidak termasuk dalam laporan normal.">
        <x-slot:actions>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('reports.tat.export.excel', $exportQuery) }}" class="inline-flex h-10 items-center rounded-lg border border-emerald-200 bg-emerald-50 px-4 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">Export Excel</a>
                <a href="{{ route('reports.tat.export.pdf', $exportQuery) }}" class="inline-flex h-10 items-center rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800">Export PDF</a>
            </div>
        </x-slot:actions>
    </x-page-header>

    <x-card title="Filter Laporan" description="Gunakan tanggal pelaksanaan sebagai periode laporan.">
        <form method="GET" action="{{ route('reports.tat.index') }}" class="space-y-5">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-form.input name="start_date" label="Tanggal Mulai" type="date" :value="$filters['start_date'] ?? ''" />
                <x-form.input name="end_date" label="Tanggal Selesai" type="date" :value="$filters['end_date'] ?? ''" />
                @if(auth()->user()->isBnnp())
                    <x-form.select name="bnnk_id" label="BNNK" :options="$bnnks->pluck('name', 'id')->all()" :value="$filters['bnnk_id'] ?? ''" placeholder="Semua BNNK" />
                @endif
                <x-form.select name="recommendation" label="Rekomendasi" :options="$recommendationOptions" :value="$filters['recommendation'] ?? ''" placeholder="Semua rekomendasi" />
                <x-form.select name="execution" label="Pelaksanaan" :options="$executionOptions" :value="$filters['execution'] ?? ''" placeholder="Semua pelaksanaan" />
                <x-form.select name="narcotic_type_id" label="Jenis Narkotika" :options="$narcoticTypes->pluck('name', 'id')->all()" :value="$filters['narcotic_type_id'] ?? ''" placeholder="Semua jenis narkotika" />
                <x-form.input name="search" label="Nama / No Register" :value="$filters['search'] ?? ''" placeholder="Cari pada tabel laporan" />
            </div>
            <div class="flex flex-wrap justify-end gap-2">
                <a href="{{ route('reports.tat.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                <x-button type="submit">Terapkan Filter</x-button>
            </div>
        </form>
    </x-card>

    @php($recommendationCards = [
        ['label' => 'Total Data', 'value' => $summary['total']],
        ['label' => 'Rawat Jalan', 'value' => $summary['recommendations'][\App\Enums\TatRecommendation::RawatJalan->value]],
        ['label' => 'Rawat Inap', 'value' => $summary['recommendations'][\App\Enums\TatRecommendation::RawatInap->value]],
        ['label' => 'Rehab Lapas/Rutan', 'value' => $summary['recommendations'][\App\Enums\TatRecommendation::RehabLapasRutan->value]],
        ['label' => 'Tidak Rehab', 'value' => $summary['recommendations'][\App\Enums\TatRecommendation::TidakRehab->value]],
    ])
    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach($recommendationCards as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>
    <div class="mt-4 flex flex-wrap gap-3 text-sm">
        <span class="rounded-full bg-emerald-50 px-3 py-1.5 font-semibold text-emerald-700">Dilaksanakan: {{ $summary['executions'][\App\Enums\RecommendationExecution::Dilaksanakan->value] }}</span>
        <span class="rounded-full bg-slate-100 px-3 py-1.5 font-semibold text-slate-700">Tidak Dilaksanakan: {{ $summary['executions'][\App\Enums\RecommendationExecution::TidakDilaksanakan->value] }}</span>
    </div>

    <div class="mt-6">
        @if($records->count())
            <x-table minWidth="1120px" :stickyFirst="true" :stickyLast="false" adaptive>
                <x-slot:colgroup>
                    <colgroup>
                        @if(auth()->user()->isBnnp())
                            <col style="width: 6%">
                            <col style="width: 14%">
                            <col style="width: 10%">
                            <col style="width: 11%">
                            <col style="width: 12%">
                            <col style="width: 13%">
                            <col style="width: 11%">
                            <col style="width: 11%">
                            <col style="width: 12%">
                        @else
                            <col style="width: 7%">
                            <col style="width: 16%">
                            <col style="width: 12%">
                            <col style="width: 13%">
                            <col style="width: 15%">
                            <col style="width: 12%">
                            <col style="width: 12%">
                            <col style="width: 13%">
                        @endif
                    </colgroup>
                </x-slot:colgroup>
                <x-slot:head>
                    <tr>
                        <th class="table-head whitespace-nowrap px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">NO/BLN</th>
                        <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">NO REGISTER</th>
                        <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Nama</th>
                        @if(auth()->user()->isBnnp())
                            <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">BNNK</th>
                        @endif
                        <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Tgl Pelaksanaan</th>
                        <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Jenis Narkotika</th>
                        <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Rekomendasi</th>
                        <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Pelaksanaan</th>
                        <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Hasil Rekom TAT</th>
                    </tr>
                </x-slot:head>
                @foreach($records as $record)
                    <tr class="align-top hover:bg-slate-50">
                        <td class="table-cell whitespace-nowrap px-5 py-4 text-slate-600">{{ $record->no_bln ?? '—' }}</td>
                        <td class="table-cell whitespace-nowrap px-5 py-4 font-semibold text-slate-800"><a href="{{ route('tat-registers.show', $record) }}" class="hover:underline">{{ $record->no_register ?? '—' }}</a></td>
                        <td class="table-cell px-5 py-4 text-slate-700">{{ $record->nama ?? 'Belum diisi' }}<div class="table-sub mt-1 text-slate-400">{{ $record->nik ?? 'NIK belum tersedia' }}</div></td>
                        @if(auth()->user()->isBnnp())
                            <td class="table-cell whitespace-nowrap px-5 py-4 text-slate-600">{{ $record->bnnk->name }}</td>
                        @endif
                        <td class="table-cell whitespace-nowrap px-5 py-4 text-slate-600">{{ $record->tanggal_pelaksanaan?->format('d M Y') ?? '—' }}</td>
                        <td class="table-cell px-5 py-4 text-slate-600">
                            @if($record->narcotics->isEmpty())
                                <span class="text-slate-400">—</span>
                            @else
                                <div class="space-y-1">
                                    @foreach($record->narcotics as $narcotic)
                                        <div>{{ $narcotic->narcoticType?->name ?? '—' }}@if($narcotic->jumlah !== null)<span class="table-sub text-slate-400"> ({{ number_format((float) $narcotic->jumlah, 3, '.', '') }} {{ $narcotic->unit?->name ?? '' }})</span>@endif</div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="table-cell px-5 py-4 text-slate-600">{{ $record->rekomendasi_tat?->label() ?? '—' }}</td>
                        <td class="table-cell px-5 py-4 text-slate-600">{{ $record->pelaksanaan_rekomendasi?->label() ?? '—' }}</td>
                        <td class="table-cell max-w-xs px-5 py-4 text-slate-600">{{ $record->hasil_rekom_tat ?? '—' }}</td>
                    </tr>
                @endforeach
            </x-table>
            <div class="mt-5 flex flex-wrap items-center justify-between gap-3"><x-pagination-per-page :value="$perPage ?? 25" />{{ $records->links() }}</div>
        @else
            <x-empty-state title="Tidak ada data laporan" description="Tidak ditemukan data Register TAT sesuai filter yang dipilih." />
        @endif
    </div>
@endsection
