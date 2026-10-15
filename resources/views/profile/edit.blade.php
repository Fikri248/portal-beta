@extends('layouts.app')

@section('title', 'Profil — Portal TAT')

@section('content')
<x-page-header title="Profil" description="Perbarui nama dan email akun Anda. Hak akses dikelola berdasarkan kewenangan sistem." />

<div class="grid gap-6 xl:grid-cols-3">
    <div class="xl:col-span-2">
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')
            <x-card title="Informasi Profil" description="Perubahan ini hanya berlaku untuk akun Anda sendiri.">
                <div class="grid gap-5 md:grid-cols-2">
                    <x-form.input name="name" label="Nama" :value="$user->name" required autocomplete="name" />
                    <x-form.input name="email" label="Email" type="email" :value="$user->email" required autocomplete="email" />
                </div>
                <div class="mt-6 flex justify-end">
                    <x-button type="submit">Simpan Profil</x-button>
                </div>
            </x-card>
        </form>
    </div>

    <x-card title="Konteks Akun" description="Informasi ini mengikuti role dan unit kerja yang ditetapkan administrator.">
        <dl class="space-y-4">
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Role</dt><dd class="mt-1"><x-badge status="active" :label="$user->role->label()" /></dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">BNNK / Unit</dt><dd class="mt-1 text-sm font-medium text-slate-800">{{ $user->bnnk?->name ?? $user->role->label() }}</dd></div>
        </dl>
    </x-card>
</div>
@endsection
