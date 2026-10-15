@extends('layouts.app')

@section('title', 'Detail Audit Log — Portal TAT')

@section('content')
<x-page-header title="Detail Audit Log" description="Informasi teknis dan perubahan aman dari satu aktivitas." />

<div class="mb-5 flex items-center justify-between gap-3">
    <a href="{{ route('audit-logs.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Kembali ke Audit Log</a>
</div>

<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <x-card title="Ringkasan Aktivitas">
            <dl class="grid gap-5 sm:grid-cols-2">
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Waktu</dt><dd class="mt-1 text-sm font-medium text-slate-800">{{ \App\Support\ApplicationDateTime::format($auditLog->created_at, 'd/m/Y H:i:s') }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Aktivitas</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $auditLog->actionLabel() }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Actor</dt><dd class="mt-1 text-sm font-medium text-slate-800">{{ $auditLog->user?->name ?? 'System' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Email</dt><dd class="mt-1 text-sm text-slate-700">{{ $auditLog->user?->email ?? '—' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Role</dt><dd class="mt-1 text-sm text-slate-700">{{ $auditLog->user?->role?->label() ?? '—' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">BNNK</dt><dd class="mt-1 text-sm text-slate-700">{{ $auditLog->bnnk?->name ?? '—' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Entitas</dt><dd class="mt-1 text-sm text-slate-700">{{ match($auditLog->entity_type) { 'TatRegister' => 'Register TAT', 'User' => 'User', default => $auditLog->entity_type ?? '—' } }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Reference ID</dt><dd class="mt-1 text-sm text-slate-700">{{ $auditLog->entity_id ?? '—' }}</dd></div>
                @if($auditLog->description)<div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Catatan</dt><dd class="mt-1 text-sm text-slate-700">{{ $auditLog->description }}</dd></div>@endif
            </dl>
        </x-card>

        <x-card title="Perubahan" description="Hanya field yang berubah dan sudah disaring yang ditampilkan.">
            @if($changes)
                <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Tabel perubahan (geser horizontal untuk melihat seluruh kolom)">
                    <table class="responsive-table w-full divide-y divide-slate-200" style="--table-min-width: 560px">
                        <thead><tr><th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Field</th><th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Sebelum</th><th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Sesudah</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($changes as $change)
                                <tr><td class="px-3 py-3 text-sm font-semibold text-slate-800">{{ $change['label'] }}</td><td class="px-3 py-3 text-sm text-slate-600">{{ $change['old'] }}</td><td class="px-3 py-3 text-sm text-slate-800">{{ $change['new'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-slate-500">Tidak ada perubahan field yang perlu ditampilkan.</p>
            @endif
        </x-card>
    </div>

    <div class="space-y-6">
        <x-card title="Metadata Teknis">
            @if($metadata)
                <dl class="space-y-4">
                    @foreach($metadata as $item)
                        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $item['label'] }}</dt><dd class="mt-1 break-words text-sm text-slate-700">{{ $item['value'] }}</dd></div>
                    @endforeach
                </dl>
            @else
                <p class="text-sm text-slate-500">Tidak ada metadata tambahan.</p>
            @endif
        </x-card>

        <x-card title="Request Context">
            <dl class="space-y-4">
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">IP Address</dt><dd class="mt-1 text-sm text-slate-700">{{ $auditLog->ip_address ?? '—' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">User-Agent</dt><dd class="mt-1 break-words text-sm text-slate-700">{{ $auditLog->user_agent ?? '—' }}</dd></div>
            </dl>
        </x-card>
    </div>
</div>
@endsection
