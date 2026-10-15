@extends('layouts.app')

@section('title', 'Ganti Password — Portal TAT')

@section('content')
<x-page-header title="Ganti Password" description="Pastikan password baru hanya diketahui oleh Anda dan tidak digunakan ulang di tempat lain." />

<div class="max-w-2xl">
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        @method('PUT')
        <x-card title="Keamanan Akun" description="Masukkan password saat ini untuk mengonfirmasi perubahan.">
            <div class="space-y-5">
                <x-form.input name="current_password" label="Current Password" type="password" required autocomplete="current-password" />
                <x-form.input name="password" label="New Password" type="password" required autocomplete="new-password" help="Minimal 8 karakter." />
                <x-form.input name="password_confirmation" label="Confirm New Password" type="password" required autocomplete="new-password" />
            </div>
            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('profile.edit') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
                <x-button type="submit">Simpan Password</x-button>
            </div>
        </x-card>
    </form>
</div>
@endsection
