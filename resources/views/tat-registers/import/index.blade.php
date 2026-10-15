@extends('layouts.app')

@section('title', 'Import Excel — Portal TAT')

@section('content')
<x-page-header title="Import Excel" description="Upload file Register TAT, validasi seluruh data, lalu tinjau sebelum import." />

<div class="mb-6 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p class="font-semibold text-slate-800">Gunakan template Register TAT agar format file sesuai.</p>
        <p class="mt-1 text-sm text-slate-500">Tanggal disarankan menggunakan format DD/MM/YYYY.</p>
    </div>
    <a href="{{ route('tat-registers.import.template') }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800">Download Template Excel</a>
</div>

@if(session('import_errors'))
    @php($summary = session('import_error_summary', ['total' => count(session('import_errors')), 'rows' => 0]))
    <x-alert type="error" class="mb-6">
        <p class="font-semibold">File belum dapat diimpor.</p>
        <p class="mt-1">Ditemukan {{ $summary['total'] }} masalah pada {{ $summary['rows'] }} baris. Perbaiki Excel lalu upload kembali.</p>
        <div class="mt-4 overflow-x-auto rounded-lg border border-red-200 bg-white" tabindex="0" role="region" aria-label="Tabel error import (geser horizontal untuk melihat seluruh kolom)">
            <table class="responsive-table w-full divide-y divide-red-100 text-left text-sm" style="--table-min-width: 560px">
                <thead class="bg-red-50 text-xs uppercase tracking-wide text-red-700">
                    <tr><th class="px-4 py-3">Baris</th><th class="px-4 py-3">Field</th><th class="px-4 py-3">Masalah</th></tr>
                </thead>
                <tbody class="divide-y divide-red-100">
                    @foreach(session('import_errors') as $importError)
                        <tr><td class="whitespace-nowrap px-4 py-3 font-semibold">{{ $importError['row'] }}</td><td class="whitespace-nowrap px-4 py-3">{{ $importError['field'] }}</td><td class="px-4 py-3">{{ $importError['message'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-alert>
@endif

<x-card title="Upload File Register TAT" description="File akan divalidasi utuh. Tidak ada baris yang disimpan sebelum Anda mengonfirmasi preview.">
    <form method="POST" action="{{ route('tat-registers.import.validate') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        <div>
            <p class="mb-1.5 block text-sm font-medium text-slate-700">BNNK Pemilik Data</p>
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700">{{ $bnnks->first()?->name ?? 'BNNK belum terhubung' }}</div>
            <p class="mt-1.5 text-xs text-slate-500">Kepemilikan import dikunci ke BNNK akun Anda.</p>
        </div>
        <div>
            <label for="file" class="mb-1.5 block text-sm font-medium text-slate-700">File Excel <span class="text-red-500">*</span></label>
            <input id="file" name="file" type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm outline-none file:mr-4 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-slate-700 focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
            <p class="mt-1.5 text-xs text-slate-500">Format yang didukung: .xlsx, maksimal 10 MB. Header harus mengikuti struktur Register TAT.</p>
            <x-form.error for="file" />
        </div>
        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ route('tat-registers.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
            <x-button type="submit">Validasi File</x-button>
        </div>
    </form>
</x-card>

<x-card title="Kolom yang Diproses" description="Data kepemilikan, status, dan foto tidak dibaca dari Excel." class="mt-6">
    <div class="grid gap-3 text-sm text-slate-600 md:grid-cols-2">
        <p><span class="font-semibold text-slate-800">Identitas:</span> NO, NO/BLN, NAMA, NIK, tanggal lahir, jenis kelamin, dan data identitas lainnya.</p>
        <p><span class="font-semibold text-slate-800">TAT:</span> rekomendasi tepat satu pilihan dan pelaksanaan rekomendasi.</p>
        <p><span class="font-semibold text-slate-800">Narkotika:</span> satu jenis, jumlah/berat desimal, dan satuan dari master aktif.</p>
        <p><span class="font-semibold text-slate-800">Validasi:</span> seluruh baris, duplicate NO REGISTER, dan scoped NO/BLN diperiksa sebelum preview.</p>
    </div>
</x-card>
@endsection
