@extends('layouts.app')

@section('title', 'Kelola User — Portal TAT')

@section('content')
<x-page-header title="Kelola User" description="Kelola akun, peran, unit kerja, dan status akses pengguna Portal TAT.">
    <x-slot:actions>
        <a href="{{ route('users.create') }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800">Tambah User</a>
    </x-slot:actions>
</x-page-header>

<x-card>
    @if($users->count())
        <x-table minWidth="1120px" :stickyFirst="true" :stickyLast="false" adaptive>
            <x-slot:colgroup>
                <colgroup>
                    <col style="width: 20%">
                    <col style="width: 24%">
                    <col style="width: 12%">
                    <col style="width: 18%">
                    <col style="width: 11%">
                    <col style="width: 15%">
                </colgroup>
            </x-slot:colgroup>
            <x-slot:head>
                <tr>
                    <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Nama</th>
                    <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Email</th>
                    <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Role</th>
                    <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">BNNK</th>
                    <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                </tr>
            </x-slot:head>

            @foreach($users as $user)
                <tr class="align-top hover:bg-slate-50">
                    <td class="table-cell px-5 py-4 font-semibold text-slate-800">{{ $user->name }}</td>
                    <td class="table-cell px-5 py-4 text-slate-600">{{ $user->email }}</td>
                    <td class="table-cell whitespace-nowrap px-5 py-4 text-center text-slate-600">{{ $user->role->label() }}</td>
                    <td class="table-cell whitespace-nowrap px-5 py-4 text-slate-600">{{ $user->bnnk?->name ?? '—' }}</td>
                    <td class="table-cell px-5 py-4 text-center"><x-badge :status="$user->is_active ? 'active' : 'inactive'" :label="$user->is_active ? 'Aktif' : 'Nonaktif'" /></td>
                    <td class="table-cell whitespace-nowrap px-5 py-4 text-center">
                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <x-table.action :href="route('users.edit', $user)">Edit</x-table.action>
                            @if($user->is(auth()->user()))
                                <span class="text-xs text-slate-400">Akun Anda</span>
                            @else
                                <form method="POST" action="{{ route('users.status', $user) }}" class="inline" onsubmit="return confirm('{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} user ini?');">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
                                    <x-table.action :variant="$user->is_active ? 'danger' : 'success'" type="submit">{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</x-table.action>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-table>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-3"><x-pagination-per-page :value="$perPage ?? 25" />{{ $users->links() }}</div>
    @else
        <x-empty-state title="Belum ada user" description="Tambahkan user untuk memberikan akses ke Portal TAT.">
            <x-slot:action><a href="{{ route('users.create') }}" class="inline-flex h-10 items-center rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800">Tambah User</a></x-slot:action>
        </x-empty-state>
    @endif
</x-card>
@endsection
