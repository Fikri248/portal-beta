@extends('layouts.app')
@section('title', 'Dashboard — Portal TAT')
@section('content')
<x-page-header title="Dashboard" description="{{ $user->isBnnp() ? 'Pantauan Register TAT seluruh BNNK di wilayah Jawa Timur.' : 'Pantauan Register TAT unit kerja Anda.' }}">
    <x-slot:actions><x-badge :status="$user->isBnnp() ? 'active' : 'final'" :label="$user->role->label()" /></x-slot:actions>
</x-page-header>

<div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-5">
    @foreach($charts['kpi'] as $stat)
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">{{ $stat['label'] }}</p>
            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stat['value']) }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ $stat['hint'] }}</p>
        </div>
    @endforeach
</div>

<div class="mt-6 grid grid-cols-1 items-start gap-6 xl:grid-cols-3">
    <x-card class="xl:col-span-2">
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-bnn-50"><img src="{{ asset('images/logo-bnn.png') }}" alt="Logo BNN" class="h-12 w-12 object-cover"></div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-slate-500">Selamat datang kembali</p>
                <h2 class="mt-1 break-words text-xl font-bold tracking-tight text-slate-900">{{ $user->name }}</h2>
                <p class="mt-1.5 max-w-2xl text-sm leading-6 text-slate-500">{{ $user->isBnnp() ? 'Gunakan panel ini untuk memantau capaian seluruh BNNK dan menindaklanjuti data yang belum lengkap.' : 'Gunakan panel ini untuk memantau progres pengisian dan kelengkapan data unit kerja Anda.' }}</p>
            </div>
        </div>
    </x-card>
    <x-card title="Konteks akses">
        <dl class="space-y-4"><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Role</dt><dd class="mt-1 text-sm font-medium text-slate-800">{{ $user->role->label() }} @if($user->isBnnp())<span class="text-slate-500">· Super Admin</span>@endif</dd></div><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Unit kerja</dt><dd class="mt-1 text-sm font-medium text-slate-800">{{ $user->bnnk?->name ?? $user->role->label() }}</dd></div></dl>
    </x-card>
</div>

<div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
    <x-dashboard-chart
        id="chart-monthly"
        title="Tren Register 6 bulan terakhir"
        description="Jumlah Final dan Draft per bulan pelaksanaan."
        type="bar"
        :labels="$charts['monthly']['labels']"
        :datasets="[
            ['label' => 'Final', 'data' => $charts['monthly']['final'], 'backgroundColor' => '#0984E2'],
            ['label' => 'Draft', 'data' => $charts['monthly']['draft'], 'backgroundColor' => '#eda100'],
        ]"
    />
    <x-dashboard-chart
        id="chart-status"
        title="Komposisi status data"
        description="Perbandingan Final, Draft aktif, dan Arsip."
        type="doughnut"
        :labels="$charts['status']['labels']"
        :datasets="[['data' => $charts['status']['values'], 'backgroundColor' => $charts['status']['colors']]]"
    />
</div>

<div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
    <x-dashboard-chart
        id="chart-recommendation"
        title="Sebaran rekomendasi TAT"
        description="Hasil rekomendasi pada data Final dan Draft aktif."
        type="bar"
        :labels="$charts['recommendation']['labels']"
        :datasets="[['label' => 'Jumlah', 'data' => $charts['recommendation']['values'], 'backgroundColor' => $charts['recommendation']['colors']]]"
    />
    <x-dashboard-chart
        id="chart-execution"
        title="Pelaksanaan rekomendasi"
        description="Status tindak lanjut rekomendasi yang diberikan."
        type="doughnut"
        :labels="$charts['execution']['labels']"
        :datasets="[['data' => $charts['execution']['values'], 'backgroundColor' => $charts['execution']['colors']]]"
    />
</div>

<div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
    <x-dashboard-chart
        id="chart-gender"
        title="Komposisi jenis kelamin"
        description="Sebaran klien laki-laki dan perempuan."
        type="doughnut"
        :labels="$charts['gender']['labels']"
        :datasets="[['data' => $charts['gender']['values'], 'backgroundColor' => $charts['gender']['colors']]]"
    />
    <x-dashboard-chart
        id="chart-narcotics"
        title="5 jenis narkotika terbanyak"
        description="{{ $user->isBnnp() ? 'Jenis barang bukti yang paling sering tercatat.' : 'Jenis barang bukti yang paling sering tercatat di unit kerja Anda.' }}"
        type="bar"
        :labels="$charts['topNarcotics']['labels']"
        :datasets="[['label' => 'Jumlah temuan', 'data' => $charts['topNarcotics']['values'], 'backgroundColor' => '#0984E2']]"
    />
</div>

@if($user->isBnnp())
    <div class="mt-6">
        <x-dashboard-chart
            id="chart-per-bnnk"
            title="Perbandingan capaian per BNNK"
            description="Jumlah Final dan Draft aktif pada tiap satuan kerja."
            type="bar"
            height="h-96"
            :labels="$charts['perBnnk']['labels']"
            :datasets="[
                ['label' => 'Final', 'data' => $charts['perBnnk']['final'], 'backgroundColor' => '#0984E2'],
                ['label' => 'Draft', 'data' => $charts['perBnnk']['draft'], 'backgroundColor' => '#eda100'],
            ]"
        />
    </div>
@endif

@endsection
