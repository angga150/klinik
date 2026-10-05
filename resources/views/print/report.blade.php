<!doctype html><html lang="id"><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#243447}h1{font-size:19px;color:#0b817d}h2{font-size:14px}table{width:100%;border-collapse:collapse;margin-top:16px}th{text-align:left;background:#eef5f5;font-size:9px}td,th{padding:8px 5px;border-bottom:1px solid #e2e8f0}header{border-bottom:2px solid #0b817d;padding-bottom:12px}.muted{color:#64748b}.total{text-align:right;font-size:15px;font-weight:bold}footer{margin-top:24px;font-size:8px;color:#94a3b8}.wrap{word-wrap:break-word}tr{page-break-inside:avoid}thead{display:table-header-group}</style></head><body><header><h1>{{ $clinic->name }}</h1><p>{{ $clinic->address }} · {{ $clinic->phone }}</p></header><h2>{{ $title }}</h2><p class="muted">Periode {{ $filters['from'] }} – {{ $filters['to'] }}</p><table>
@if($rows->count())<thead><tr>
@foreach(array_keys((array)$rows->first()) as $column)<th>{{ $column }}</th>
@endforeach</tr></thead><tbody>
@foreach($rows as $row)<tr>
@foreach((array)$row as $value)<td class="wrap">{{ $value }}</td>
@endforeach</tr>
@endforeach</tbody>
@endif</table>
@unless($rows->count())<p>Tidak ada data pada periode ini.</p>
@endunless<footer>SIM Klinik Enterprise · Dokumen demo · Dicetak {{ now()->format('d/m/Y H:i') }} WIB</footer></body></html>