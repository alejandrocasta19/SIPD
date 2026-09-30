<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:11px;color:#1f2937}h1{color:#006837;border-bottom:2px solid #006837;padding-bottom:8px}h2{font-size:14px;color:#006837;margin-top:22px}table{width:100%;border-collapse:collapse}td,th{border:1px solid #d1d5db;padding:6px;text-align:left}.muted{color:#64748b}</style></head><body>
<h1>SIPD - Reporte global</h1><p class="muted">Periodo: {{ $data['from'] ?: 'Inicio' }} a {{ $data['to'] ?: 'Actualidad' }}</p>
<h2>Resumen</h2><table><tr><th>Total de casos</th><td>{{ $data['total'] }}</td></tr></table>
<h2>Distribución por estado</h2><table><tr><th>Estado</th><th>Cantidad</th></tr>@foreach($data['states'] as $state => $total)<tr><td>{{ $state }}</td><td>{{ $total }}</td></tr>@endforeach</table>
<h2>Casos registrados por mes</h2><table><tr><th>Mes</th><th>Total</th></tr>@foreach($data['monthly'] as $month)<tr><td>{{ $month['period'] }}</td><td>{{ array_sum($month['states']) }}</td></tr>@endforeach</table>
<h2>Tipos de faltas pendientes</h2><table><tr><th>Tipo de falta</th><th>Cantidad</th></tr>@foreach($data['pending_faults'] as $fault)<tr><td>{{ $fault['label'] }}</td><td>{{ $fault['total'] }}</td></tr>@endforeach</table>
</body></html>
