@extends('layouts.master')

@section('styles')
<style>
    .hub-head { display:flex; justify-content:space-between; gap:16px; align-items:flex-start; margin-bottom:18px; }
    .hub-head h1 { margin:0 0 5px; font-size:28px; }
    .hub-head p { margin:0; color:#64748b; }
    .hub-toolbar { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:16px; }
    .hub-toolbar input { height:40px; min-width:280px; border:1px solid #cbd5e1; border-radius:8px; padding:0 12px; }
    .hub-btn { border:0; border-radius:8px; padding:10px 16px; font-weight:700; cursor:pointer; display:inline-flex; gap:7px; align-items:center; background:var(--cth-green); color:#fff; }
    
    .hub-table { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:auto; }
    .hub-table table { width:100%; min-width:1000px; border-collapse:collapse; font-size:13px; }
    .hub-table th, .hub-table td { padding:14px; border-bottom:1px solid #f1f5f9; text-align:left; vertical-align:middle; }
    .hub-table th { color:#64748b; font-size:11px; letter-spacing:.05em; text-transform:uppercase; background:#f8fafc; }
    .hub-table a { color:var(--cth-green-text); font-weight:700; text-decoration:none; }
    .empty-hub { text-align:center; padding:42px; color:#94a3b8; }
    .hub-pager { display:flex; justify-content:space-between; padding:14px; color:#64748b; }

    .doc-badge {
        display: inline-flex; align-items: center; justify-content: center;
        padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700;
        min-width: 100px; text-transform: uppercase; letter-spacing: 0.05em;
        cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .doc-badge:hover { transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .db-pendiente { background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5; }
    .db-en_diligenciamiento { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
    .db-completo { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .db-generado { background: #f0fdf4; color: var(--cth-green-text); border: 1px solid #bbf7d0; }

    .action-btn {
        background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1;
        padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700;
        text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
        transition: all .2s;
    }
    .action-btn:hover { background: #e2e8f0; color: #0f172a; }
</style>
@endsection

@section('content')
<div class="hub-head">
    <div>
        <h1>Central de Documentos Oficiales</h1>
        <p>Administra, diligencia y genera las plantillas institucionales de todos tus procesos activos.</p>
    </div>
</div>

<form class="hub-toolbar" method="GET" action="{{ route('documentos.hub') }}">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por conductor, cédula, placa o N° proceso...">
    <button class="hub-btn" type="submit"><i class="fas fa-search"></i> Buscar</button>
</form>

<div class="hub-table">
    <table>
        <thead>
            <tr>
                <th>PROCESO</th>
                <th>CONDUCTOR / CÉDULA</th>
                <th style="text-align:center;">AUTO DISCIPLINARIO</th>
                <th style="text-align:center;">AUTO COMPROBACIÓN</th>
                <th style="text-align:center;">ACTA DESCARGOS</th>
                <th style="text-align:right;">GESTIONAR</th>
            </tr>
        </thead>
        <tbody>
        @forelse($casos as $caso)
            @php
                $estados = [];
                $allGenerado = true;
                foreach(\App\Models\CasoDocumentoEstado::TIPOS as $tipo) {
                    $st = $caso->estadoDocumento($tipo);
                    $estados[$tipo] = $st;
                    if(!$st || $st->estado !== 'generado') {
                        $allGenerado = false;
                    }
                }
                
                $badgeHTML = function($estadoObj) {
                    if(!$estadoObj) return '<span class="doc-badge db-pendiente">Pendiente</span>';
                    
                    $map = [
                        'pendiente' => ['Pendiente', 'db-pendiente'],
                        'en_diligenciamiento' => ['Borrador', 'db-en_diligenciamiento'],
                        'completo' => ['Por Generar', 'db-completo'],
                        'generado' => ['Generado', 'db-generado'],
                    ];
                    $data = $map[$estadoObj->estado] ?? $map['pendiente'];
                    return '<span class="doc-badge '.$data[1].'">' . $data[0] . '</span>';
                };
            @endphp
            <tr>
                <td>
                    <a href="{{ route('abogado.detalleproceso', $caso->id) }}" style="font-size: 15px;">PRO-{{ str_pad($caso->id, 3, '0', STR_PAD_LEFT) }}</a><br>
                    <small style="color:#64748b;">{{ $caso->estado }}</small>
                </td>
                <td>
                    <strong>{{ $caso->nombre }}</strong><br>
                    <span style="color:#64748b;">{{ $caso->cedula ?: 'Sin cédula' }}</span>
                </td>
                <td style="text-align:center;">
                    <a href="{{ route('documentos.edit', [$caso->id, 'disciplinario']) }}" title="Editar/Generar Auto Disciplinario" style="text-decoration:none;">
                        {!! $badgeHTML($estados['disciplinario']) !!}
                    </a>
                </td>
                <td style="text-align:center;">
                    <a href="{{ route('documentos.edit', [$caso->id, 'comprobacion']) }}" title="Editar/Generar Auto Comprobación" style="text-decoration:none;">
                        {!! $badgeHTML($estados['comprobacion']) !!}
                    </a>
                </td>
                <td style="text-align:center;">
                    <a href="{{ route('documentos.edit', [$caso->id, 'acta']) }}" title="Editar/Generar Acta de Descargos" style="text-decoration:none;">
                        {!! $badgeHTML($estados['acta']) !!}
                    </a>
                </td>
                <td style="text-align:right; display:flex; gap:5px; justify-content:flex-end;">
                    @if($allGenerado && $caso->estado !== 'Sancionado' && $caso->estado !== 'Archivado')
                        <form action="{{ route('abogado.solicitar_veredicto', $caso->id) }}" method="POST" style="margin:0;" onsubmit="return confirm('¿Enviar toda la información de este caso al coordinador para dar su veredicto?');">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="action-btn" style="background: #2563eb; color: #fff; border-color: #1d4ed8;" title="Mandar información al coordinador/admin">
                                <i class="fas fa-paper-plane"></i> Solicitar Veredicto
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('abogado.detalleproceso', $caso->id) }}" class="action-btn" title="Editar datos del proceso">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                </td>
            </tr>
        @empty
            <tr><td class="empty-hub" colspan="6">No tienes casos registrados o no hay coincidencias de búsqueda.</td></tr>
        @endforelse
        </tbody>
    </table>
    @if($casos->hasPages())
        <div class="hub-pager">
            <span>Mostrando {{ $casos->firstItem() }}-{{ $casos->lastItem() }} de {{ $casos->total() }}</span>
            <span>{{ $casos->links() }}</span>
        </div>
    @endif
</div>
@endsection
