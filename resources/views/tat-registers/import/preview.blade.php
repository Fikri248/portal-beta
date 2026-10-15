@extends('layouts.app')

@section('title', 'Preview Import — Portal TAT')

@section('content')
@php($rows = $payload['rows'] ?? [])
<x-page-header title="Preview Import Excel" description="Seluruh baris telah lolos validasi. Periksa kembali sebelum menyimpan." />

<x-alert type="warning" class="mb-6">
    <p class="font-semibold">Konfirmasi diperlukan.</p>
    <p class="mt-1">Import akan menyimpan {{ count($rows) }} data sebagai Final untuk {{ $payload['bnnk_name'] }}. Proses bersifat all-or-nothing.</p>
</x-alert>

<x-card title="Ringkasan Import" description="Token preview hanya berlaku sementara dan terikat pada akun Anda.">
    <dl class="grid gap-4 text-sm sm:grid-cols-3">
        <div><dt class="text-slate-500">BNNK Tujuan</dt><dd class="mt-1 font-semibold text-slate-800">{{ $payload['bnnk_name'] }}</dd></div>
        <div><dt class="text-slate-500">Jumlah Data</dt><dd class="mt-1 font-semibold text-slate-800">{{ count($rows) }} baris</dd></div>
        <div><dt class="text-slate-500">Status</dt><dd class="mt-1"><x-badge status="final" label="Final" /></dd></div>
    </dl>
</x-card>

<div class="mt-6">
    <x-table minWidth="1280px" :stickyFirst="true">
        <x-slot:head>
            <tr>
                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Baris Excel</th>
                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">NO REGISTER</th>
                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">NO/BLN</th>
                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</th>
                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal Lahir</th>
                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Pelaksanaan</th>
                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Pasal yang Disangkakan</th>
                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Narkotika</th>
                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Rekomendasi</th>
                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Eksekusi</th>
            </tr>
        </x-slot:head>
        @foreach($rows as $row)
            @php($register = $row['register'])
            @php($recommendation = \App\Enums\TatRecommendation::tryFrom($register['rekomendasi_tat'] ?? ''))
            @php($execution = \App\Enums\RecommendationExecution::tryFrom($register['pelaksanaan_rekomendasi'] ?? ''))
            <tr class="hover:bg-slate-50">
                <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-700">{{ $row['excel_row'] }}</td>
                <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-800">{{ $register['no_register'] }}</td>
                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $register['no_bln'] }}</td>
                <td class="px-5 py-4 text-sm text-slate-700">{{ $register['nama'] }}</td>
                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $register['tanggal_lahir'] }}</td>
                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $register['tanggal_pelaksanaan'] }}</td>
                <td class="px-5 py-4 text-sm text-slate-600">{{ $register['pasal_yang_disangkakan'] ?? '—' }}</td>
                <td class="px-5 py-4 text-sm text-slate-600">@forelse($row['narcotics'] as $narcotic){{ $narcotic['type_name'] }}@if($narcotic['jumlah'] !== null) · {{ $narcotic['jumlah'] }} {{ $narcotic['unit_name'] }}@endif @empty—@endforelse</td>
                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $recommendation?->label() ?? '—' }}</td>
                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $execution?->label() ?? '—' }}</td>
            </tr>
        @endforeach
    </x-table>
</div>

<div class="mt-6 flex items-center justify-end gap-3">
    <form method="POST" action="{{ route('tat-registers.import.cancel', $token) }}">@csrf<x-button type="submit" variant="secondary">Batal</x-button></form>
    <form method="POST" action="{{ route('tat-registers.import.confirm', $token) }}">@csrf<x-button type="submit">Konfirmasi Import</x-button></form>
</div>
@endsection
