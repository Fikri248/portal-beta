@extends('layouts.app')

@section('title', 'Detail Register TAT — Portal TAT')

@section('content')
<x-page-header title="Detail Register TAT" description="Informasi lengkap Register TAT dan data narkotika terkait.">
    <x-slot:actions>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('tat-registers.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Kembali</a>
            <a href="{{ route('tat-registers.edit', $tatRegister) }}" class="inline-flex h-10 items-center rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800">Edit</a>
            <form method="POST" action="{{ route('tat-registers.destroy', $tatRegister) }}" class="inline">
                @csrf
                @method('DELETE')
                <x-button variant="danger" type="submit">Arsipkan</x-button>
            </form>
        </div>
    </x-slot:actions>
</x-page-header>

<div class="mb-6 flex flex-wrap items-center gap-3">
    <x-badge :status="$tatRegister->status->value" :label="$tatRegister->status?->label() ?? '—'" />
    <span class="text-sm text-slate-500">{{ $tatRegister->bnnk->name }}</span>
    <span class="text-sm text-slate-400">•</span>
    <span class="text-sm text-slate-500">No Register: {{ $tatRegister->no_register ?? 'Belum diisi' }}</span>
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
    <x-card title="Data Pengajuan">
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach(['no' => 'NO', 'no_bln' => 'NO/BLN', 'asal_pengajuan' => 'Asal Pengajuan', 'tanggal_surat' => 'Tanggal Surat', 'tanggal_berkas_diterima' => 'Tanggal Berkas Diterima', 'tanggal_pelaksanaan' => 'Tanggal Pelaksanaan', 'no_surat_pengajuan' => 'No Surat Pengajuan', 'no_lkn_lp_li' => 'No LKN/LP/LI', 'tanggal_penangkapan' => 'Tanggal Penangkapan'] as $field => $label)
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</dt>
                    <dd class="mt-1 text-sm text-slate-800">{{ str_contains($field, 'tanggal') ? ($tatRegister->$field?->format('d M Y') ?? '—') : ($tatRegister->$field ?? '—') }}</dd>
                </div>
            @endforeach
            <div class="sm:col-span-2">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Pasal yang Disangkakan</dt>
                <dd class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-800">{{ $tatRegister->pasal_yang_disangkakan ?? '—' }}</dd>
            </div>
        </dl>
    </x-card>

    <x-card title="Identitas Klien">
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach(['nama' => 'Nama', 'tempat_lahir' => 'Tempat Lahir', 'tanggal_lahir' => 'Tanggal Lahir', 'jenis_kelamin' => 'Jenis Kelamin', 'usia' => 'Usia', 'pendidikan' => 'Pendidikan', 'pekerjaan' => 'Pekerjaan', 'no_handphone' => 'No Handphone', 'nik' => 'NIK'] as $field => $label)
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</dt>
                    <dd class="mt-1 text-sm text-slate-800">{{ $field === 'tanggal_lahir' ? ($tatRegister->$field?->format('d M Y') ?? '—') : ($field === 'jenis_kelamin' ? ($tatRegister->$field?->label() ?? '—') : ($tatRegister->$field ?? '—')) }}</dd>
                </div>
            @endforeach
            <div class="sm:col-span-2">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Alamat KTP</dt>
                <dd class="mt-1 whitespace-pre-line text-sm text-slate-800">{{ $tatRegister->alamat_ktp ?? '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Alamat Domisili</dt>
                <dd class="mt-1 whitespace-pre-line text-sm text-slate-800">{{ $tatRegister->alamat_domisili ?? '—' }}</dd>
            </div>
        </dl>
    </x-card>
</div>

<div class="mt-6">
    <x-card title="Data Narkotika / Barang Bukti">
        @if($tatRegister->narcotics->count())
            <x-table minWidth="0" :stickyLast="false">
                <x-slot:head>
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Jenis</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Jumlah</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Satuan</th>
                    </tr>
                </x-slot:head>
                @foreach($tatRegister->narcotics as $item)
                    <tr>
                        <td class="px-5 py-4 text-sm text-slate-800">{{ $item->narcoticType->name }}</td>
                        <td class="px-5 py-4 text-sm text-slate-600">{{ $item->jumlah ?? '—' }}</td>
                        <td class="px-5 py-4 text-sm text-slate-600">{{ $item->unit?->name ?? '—' }}</td>
                    </tr>
                @endforeach
            </x-table>
        @else
            <x-empty-state title="Tidak ada barang bukti fisik" description="Register ini tidak memiliki entri narkotika." />
        @endif
    </x-card>
</div>

<div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
    <x-card title="Hasil Asesmen">
        <dl class="space-y-5">
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Hasil Asesmen Hukum</dt><dd class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $tatRegister->hasil_asesmen_hukum ?? '—' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Hasil Asesmen Medis</dt><dd class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $tatRegister->hasil_asesmen_medis ?? '—' }}</dd></div>
        </dl>
    </x-card>
    <x-card title="Rekomendasi & Tindak Lanjut">
        <dl class="space-y-5">
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Rekomendasi TAT</dt><dd class="mt-1 text-sm text-slate-700">{{ $tatRegister->rekomendasi_tat?->label() ?? '—' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Pelaksanaan</dt><dd class="mt-1 text-sm text-slate-700">{{ $tatRegister->pelaksanaan_rekomendasi?->label() ?? '—' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Hasil Rekomendasi TAT</dt><dd class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $tatRegister->hasil_rekom_tat ?? '—' }}</dd></div>
        </dl>
    </x-card>
</div>

<div class="mt-6">
    <x-card title="Foto & Keterangan">
        <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Foto Klien</dt>
                <dd class="mt-1">
                    @if($photoAvailable)
                        <a href="{{ route('tat-registers.photo', $tatRegister) }}" class="inline-block" aria-label="Buka foto klien">
                            <img src="{{ route('tat-registers.photo', $tatRegister) }}" alt="Foto klien {{ $tatRegister->nama ?? '' }}" class="max-h-72 max-w-full rounded-xl border border-slate-200 bg-slate-50 object-contain" />
                        </a>
                    @else
                        <span class="text-sm text-slate-500">Belum ada foto</span>
                    @endif
                </dd>
            </div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Keterangan</dt><dd class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $tatRegister->keterangan ?? '—' }}</dd></div>
        </dl>
    </x-card>
</div>
@endsection
