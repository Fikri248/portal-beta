@extends('layouts.app')
@section('title','Arsip Register TAT — Portal TAT')
@section('content')
<x-page-header title="Arsip Register TAT" description="Data yang telah diarsipkan tetap tersimpan dan dapat dipulihkan sesuai kewenangan." />

@if($records->count())
    <x-table minWidth="1120px" :stickyFirst="true" :stickyLast="false" adaptive>
        <x-slot:colgroup>
            <colgroup>
                <col style="width: 20%">
                <col style="width: 26%">
                <col style="width: 20%">
                <col style="width: 16%">
                <col style="width: 18%">
            </colgroup>
        </x-slot:colgroup>
        <x-slot:head>
            <tr>
                <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">No Register</th>
                <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Nama</th>
                <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">BNNK</th>
                <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Diarsipkan</th>
                <th class="table-head px-5 py-3 text-center font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
            </tr>
        </x-slot:head>

        @foreach($records as $record)
            <tr class="align-top hover:bg-slate-50">
                <td class="table-cell whitespace-nowrap px-5 py-4 font-medium text-slate-800">{{ $record->no_register ?? '—' }}</td>
                <td class="table-cell px-5 py-4 text-slate-700">{{ $record->nama ?? 'Belum diisi' }}<div class="table-sub mt-1 text-slate-400">{{ $record->nik ?? 'NIK belum tersedia' }}</div></td>
                <td class="table-cell whitespace-nowrap px-5 py-4 text-slate-600">{{ $record->bnnk->name }}</td>
                <td class="table-cell whitespace-nowrap px-5 py-4 text-slate-600">{{ \App\Support\ApplicationDateTime::format($record->deleted_at, 'd M Y H:i') }}</td>
                <td class="table-cell whitespace-nowrap px-5 py-4 text-center">
                    <form method="POST" action="{{ route('tat-registers.restore',$record->id) }}" class="inline">
                        @csrf
                        <x-table.action variant="success" type="submit">Pulihkan</x-table.action>
                    </form>
                </td>
            </tr>
        @endforeach
    </x-table>

    <div class="mt-5 flex flex-wrap items-center justify-between gap-3"><x-pagination-per-page :value="$perPage ?? 25" />{{ $records->links() }}</div>
@else
    <x-empty-state title="Arsip masih kosong" description="Belum ada Register TAT yang diarsipkan." />
@endif
@endsection
