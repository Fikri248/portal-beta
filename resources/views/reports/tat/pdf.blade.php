<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Register TAT</title>
    <style>
        @page { margin: 28px 24px; }
        body { color: #0f172a; font-family: DejaVu Sans, sans-serif; font-size: 8px; }
        h1 { font-size: 15px; margin: 0; text-align: center; }
        h2 { color: #475569; font-size: 10px; margin: 4px 0 12px; text-align: center; }
        .meta { border: 1px solid #cbd5e1; margin-bottom: 12px; padding: 7px; }
        .meta table { border: 0; margin: 0; }
        .meta td { border: 0; padding: 2px 6px 2px 0; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #e2e8f0; font-size: 7px; text-align: left; }
        th, td { border: 1px solid #cbd5e1; padding: 4px; vertical-align: top; }
        .narcotic div { margin-bottom: 2px; }
        .muted { color: #64748b; }
    </style>
</head>
<body>
    <h1>LAPORAN REGISTER TAT</h1>
    <h2>BNNP JAWA TIMUR</h2>
    <div class="meta">
        <table>
            <tr><td><strong>Periode</strong></td><td>{{ $metadata['period'] }}</td><td><strong>Ruang BNNK</strong></td><td>{{ $metadata['bnnk'] }}</td></tr>
            <tr><td><strong>Rekomendasi</strong></td><td>{{ $metadata['recommendation'] }}</td><td><strong>Pelaksanaan</strong></td><td>{{ $metadata['execution'] }}</td></tr>
            <tr><td><strong>Narkotika</strong></td><td>{{ $metadata['narcotic_type'] }}</td><td><strong>Dibuat</strong></td><td>{{ $metadata['generated_at'] }}</td></tr>
            @if($metadata['search'] !== '—')
                <tr><td><strong>Pencarian</strong></td><td colspan="3">{{ $metadata['search'] }}</td></tr>
            @endif
        </table>
    </div>
    <table>
        <thead>
            <tr>
                <th>No</th><th>No Register</th><th>Nama</th>
                @if($showBnnk)<th>BNNK</th>@endif
                <th>Tanggal Pelaksanaan</th><th>Narkotika</th><th>Rekomendasi</th><th>Pelaksanaan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $index => $record)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $record->no_register ?? '—' }}</td>
                    <td>{{ $record->nama ?? '—' }}</td>
                    @if($showBnnk)<td>{{ $record->bnnk?->name ?? '—' }}</td>@endif
                    <td>{{ $record->tanggal_pelaksanaan?->format('d/m/Y') ?? '—' }}</td>
                    <td class="narcotic">
                        @if($record->narcotics->isEmpty())
                            <span class="muted">—</span>
                        @else
                            @foreach($record->narcotics as $narcotic)
                                <div>{{ $narcotic->narcoticType?->name ?? '—' }}@if($narcotic->jumlah !== null) ({{ number_format((float) $narcotic->jumlah, 3, '.', '') }} {{ $narcotic->unit?->name ?? '' }})@endif</div>
                            @endforeach
                        @endif
                    </td>
                    <td>{{ $record->rekomendasi_tat?->label() ?? '—' }}</td>
                    <td>{{ $record->pelaksanaan_rekomendasi?->label() ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ $showBnnk ? 8 : 7 }}">Tidak ada data laporan sesuai filter.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
