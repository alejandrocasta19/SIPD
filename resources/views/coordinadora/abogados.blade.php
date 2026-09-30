@extends('layouts.master')

@php
    $pageTitle = 'Equipo de RH';
    $inicialesDe = function ($nombre) {
        return collect(preg_split('/\s+/', trim($nombre)))
            ->filter()
            ->take(2)
            ->map(function ($part) { return strtoupper(substr($part, 0, 1)); })
            ->implode('');
    };
@endphp

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Equipo de RH</h1>
            <p>Personal operativo que tramita los expedientes disciplinarios.</p>
        </div>
        <button type="button" class="btn-add" id="btnAgregar">
            <i class="fas fa-plus"></i> Agregar RH
        </button>
    </div>
@endsection

@section('styles')
<style>
    button.btn-add { cursor: pointer; border: 0; }

    .form-card {
        display: none;
        background: #fff;
        border-radius: 18px;
        padding: 20px;
        margin-bottom: 16px;
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
    }

    .form-card h3 {
        margin: 0 0 16px;
        font-size: 16px;
        font-weight: 700;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr) auto;
        gap: 12px;
        align-items: end;
    }

    .form-grid label {
        display: block;
        font-size: 13px;
        color: #64748b;
        margin-bottom: 6px;
        font-weight: 600;
    }

    .form-grid input {
        width: 100%;
        height: 42px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0 12px;
        font: inherit;
        font-size: 14px;
        outline: none;
    }

    .form-grid input:focus { border-color: var(--cth-green-bright); }

    .btn-save {
        height: 42px;
        border: 0;
        border-radius: 10px;
        background: var(--cth-green);
        color: #fff;
        font-weight: 700;
        padding: 0 18px;
        cursor: pointer;
    }

    .table-card {
        background: #fff;
        border-radius: 18px;
        overflow-x: auto;
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
    }

    table.abg {
        width: 100%;
        border-collapse: collapse;
    }

    table.abg th {
        text-align: left;
        padding: 16px 18px;
        color: #94a3b8;
        font-size: 11px;
        letter-spacing: .06em;
        font-weight: 700;
        border: 1px solid var(--cth-border);
    }

    table.abg td {
        padding: 16px 18px;
        border: 1px solid var(--cth-border);
        color: #334155;
        font-size: 14px;
        vertical-align: middle;
    }

    .who {
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
        color: #0f172a;
        white-space: nowrap;
    }

    .ava {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #ecfdf5;
        color: var(--cth-green-text);
        display: grid;
        place-items: center;
        font-size: 12px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .st {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
        color: var(--cth-green-text);
    }

    .st i { font-size: 8px; color: var(--cth-green-bright); }

    .acts { display: flex; gap: 8px; }

    .acts button {
        width: 32px;
        height: 32px;
        border: 0;
        background: transparent;
        color: #94a3b8;
        cursor: pointer;
        border-radius: 8px;
    }

    .acts button.key:hover { background: #eff6ff; color: #2563eb; }
    .perm-chip { font-size: 12px; color: #475569; }
    .perm-chip em { font-style: normal; color: #d97706; font-weight: 600; }
    .perm-lead { margin: 0 0 16px; color: #64748b; font-size: 13px; }
    .perm-group { margin-bottom: 14px; }
    .perm-group h4 { margin: 0 0 8px; font-size: 12px; letter-spacing: .06em; text-transform: uppercase; color: #94a3b8; }
    .perm-row {
        display: grid;
        grid-template-columns: 18px minmax(0,1fr) 130px 88px;
        gap: 8px;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: 14px;
    }
    .perm-row select, .perm-row input[type="number"] {
        height: 34px; border: 1px solid #e2e8f0; border-radius: 8px; font: inherit; font-size: 12px; padding: 0 8px;
    }
    .sipd-dialog.modal-perm { max-width: 680px; }
    .acts button.danger:hover { background: #fff1f2; color: #e11d48; }

    .empty {
        text-align: center;
        padding: 40px 16px;
        color: #94a3b8;
    }

    .modal-bg {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, .4);
        align-items: center;
        justify-content: center;
        z-index: 50;
        padding: 20px;
    }

    .modal {
        background: #fff;
        width: 100%;
        max-width: 420px;
        border-radius: 18px;
        padding: 24px;
    }

    .modal h3 { margin: 0 0 18px; font-size: 18px; }

    .modal label {
        display: block;
        font-size: 13px;
        color: #64748b;
        margin-bottom: 6px;
        font-weight: 600;
    }

    .modal input {
        width: 100%;
        height: 42px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0 12px;
        margin-bottom: 14px;
        font: inherit;
    }

    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin-top: 8px;
    }

    .btn-ghost, .btn-ok {
        border: 0;
        border-radius: 10px;
        padding: 10px 16px;
        font-weight: 700;
        cursor: pointer;
    }

    .btn-ghost { background: #f1f5f9; color: #475569; }
    .btn-ok { background: var(--cth-green); color: #fff; }

    @media (max-width: 900px) {
        .form-grid { grid-template-columns: 1fr; }
        .table-card { overflow-x: auto; }
    }
</style>
@endsection

@section('content')

    <div class="form-card" id="formularioContainer">
        <h3>Agregar RH</h3>
        <form action="{{ route('coordinadora.abogados.guardar') }}" method="POST">
            @csrf
            <div class="form-grid">
                <div>
                    <label>Nombre</label>
                    <input type="text" name="name" required placeholder="Nombre">
                </div>
                <div>
                    <label>Correo</label>
                    <input type="email" name="email" required placeholder="Correo electrónico">
                </div>
                <div>
                    <label>Contraseña</label>
                    <input type="password" name="password" required placeholder="Contraseña">
                </div>
                <div>
                    <label>Cargo</label>
                    <input type="text" name="cargo" required placeholder="Cargo">
                </div>
                <button type="submit" class="btn-save">Guardar</button>
            </div>
        </form>
    </div>

    <div class="table-card">
        <table class="abg">
            <thead>
                <tr>
                    <th>NOMBRE</th>
                    <th>CORREO</th>
                    <th>CARGO</th>
                    <th>CARGA</th>
                    <th>PERMISOS</th>
                    <th>ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                @forelse($abogados as $abogado)
                    <tr>
                        <td>
                            <div class="who">
                                <span class="ava">{{ $inicialesDe($abogado->name) }}</span>
                                {{ $abogado->name }}
                            </div>
                        </td>
                        <td>{{ $abogado->email }}</td>
                        <td>{{ $abogado->cargo ?: 'Equipo de RH' }}</td>
                        <td>{{ $abogado->procesos_count }} caso{{ $abogado->procesos_count === 1 ? '' : 's' }}
                            @if(($abogado->procesos_abiertos_count ?? 0) > 0)
                                · {{ $abogado->procesos_abiertos_count }} abierto{{ $abogado->procesos_abiertos_count === 1 ? '' : 's' }}
                            @endif
                        </td>
                        <td>
                            @php
                                $vigentes = $abogado->permisos->filter(function ($p) { return $p->estaVigente(); });
                                $temps = $vigentes->filter(function ($p) { return $p->esTemporal(); })->count();
                            @endphp
                            <span class="perm-chip">
                                {{ $vigentes->count() }} activo{{ $vigentes->count() === 1 ? '' : 's' }}
                                @if($temps > 0)
                                    · <em>{{ $temps }} temporal{{ $temps === 1 ? '' : 'es' }}</em>
                                @endif
                            </span>
                        </td>
                        <td>
                            <div class="acts">
                                <button type="button" class="key" title="Permisos"
                                    data-name="{{ $abogado->name }}"
                                    data-action="{{ route('coordinadora.abogados.permisos', $abogado->id) }}"
                                    data-permisos='@json($abogado->permisosParaFormulario())'
                                    onclick="abrirPermisos(this)">
                                    <i class="fas fa-key"></i>
                                </button>
                                <button type="button" title="Editar"
                                    onclick="abrirModalEditar('{{ $abogado->id }}', @json($abogado->name), @json($abogado->email), @json($abogado->cargo))">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <form action="{{ route('coordinadora.abogados.eliminar', $abogado->id) }}" method="POST"
                                      data-confirm="Se eliminará este registro de recursos humanos."
                                      data-confirm-title="Eliminar registro"
                                      data-confirm-ok="Eliminar"
                                      data-confirm-danger="1"
                                      data-confirm-icon="warning">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="danger" title="Eliminar">
                                        <i class="far fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty">No hay personal de RH registrado</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="sipd-dialog-bg" id="modalEditar">
        <div class="sipd-dialog">
            <h3>Editar RH</h3>
            <form id="formEditar" method="POST">
                @csrf
                @method('PUT')
                <label>Nombre</label>
                <input type="text" name="name" id="editName" required>
                <label>Correo</label>
                <input type="email" name="email" id="editEmail" required>
                <label>Cargo</label>
                <input type="text" name="cargo" id="editCargo" required>
                <div class="sipd-dialog-actions">
                    <button type="button" class="btn-ghost" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn-ok">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="sipd-dialog-bg" id="modalPermisos">
        <div class="sipd-dialog modal-perm">
            <h3>Permisos · <span id="permNombre"></span></h3>
            <p class="perm-lead">Editar y eliminar van por horas: 1, 3, 5 o un valor personalizado.</p>
            <form id="formPermisos" method="POST">
                @csrf
                @method('PUT')
                @foreach($catalogoPermisos as $grupo)
                    <div class="perm-group">
                        <h4>{{ $grupo['label'] }}</h4>
                        @foreach($grupo['items'] as $clave => $etiqueta)
                            <label class="perm-row">
                                <input type="checkbox" name="permisos[]" value="{{ $clave }}">
                                <span>{{ $etiqueta }}</span>
                                <select name="duracion[{{ $clave }}]" class="dur-sel">
                                    <option value="permanente">Permanente</option>
                                    @foreach($duracionesPermiso as $valor => $texto)
                                        <option value="{{ $valor }}">{{ $texto }}</option>
                                    @endforeach
                                </select>
                                <input type="number" name="horas[{{ $clave }}]" min="1" max="168" placeholder="Horas" class="hrs-in" style="display:none;">
                            </label>
                        @endforeach
                    </div>
                @endforeach
                <div class="sipd-dialog-actions">
                    <button type="button" class="btn-ghost" onclick="cerrarPermisos()">Cancelar</button>
                    <button type="submit" class="btn-ok">Guardar permisos</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.getElementById('btnAgregar').addEventListener('click', function () {
        var box = document.getElementById('formularioContainer');
        box.style.display = box.style.display === 'block' ? 'none' : 'block';
    });

    function abrirModalEditar(id, nombre, email, cargo) {
        document.getElementById('modalEditar').style.display = 'flex';
        document.getElementById('editName').value = nombre;
        document.getElementById('editEmail').value = email;
        document.getElementById('editCargo').value = cargo;
        document.getElementById('formEditar').action = '/coordinadora/abogados/editar/' + id;
    }

    function cerrarModal() {
        document.getElementById('modalEditar').style.display = 'none';
    }

    function abrirPermisos(btn) {
        var data = {};
        try { data = JSON.parse(btn.getAttribute('data-permisos') || '{}'); } catch (e) { data = {}; }
        document.getElementById('permNombre').textContent = btn.getAttribute('data-name') || '';
        document.getElementById('formPermisos').action = btn.getAttribute('data-action') || '';
        document.querySelectorAll('#formPermisos input[type="checkbox"]').forEach(function (cb) {
            var grant = data[cb.value];
            cb.checked = !!(grant && grant.on);
            var row = cb.closest('.perm-row');
            var sel = row.querySelector('.dur-sel');
            var hrs = row.querySelector('.hrs-in');
            if (sel) sel.value = grant && grant.duracion ? grant.duracion : 'permanente';
            if (hrs) {
                hrs.style.display = sel && sel.value === 'custom' ? '' : 'none';
                hrs.disabled = !cb.checked || (sel && sel.value !== 'custom');
            }
            if (sel) sel.disabled = !cb.checked;
        });
        document.getElementById('modalPermisos').style.display = 'flex';
    }

    function cerrarPermisos() {
        document.getElementById('modalPermisos').style.display = 'none';
    }

    document.getElementById('formPermisos').addEventListener('change', function (e) {
        var row = e.target.closest('.perm-row');
        if (!row) return;
        var cb = row.querySelector('input[type="checkbox"]');
        var sel = row.querySelector('.dur-sel');
        var hrs = row.querySelector('.hrs-in');
        if (sel) sel.disabled = !cb.checked;
        if (hrs) {
            hrs.style.display = sel && sel.value === 'custom' ? '' : 'none';
            hrs.disabled = !cb.checked || sel.value !== 'custom';
        }
    });
    document.getElementById('formPermisos').addEventListener('submit', function () {
        this.querySelectorAll('select, input').forEach(function (el) { el.disabled = false; });
    });
    ['modalPermisos', 'modalEditar'].forEach(function (id) {
        document.getElementById(id).addEventListener('click', function (e) {
            if (e.target === this) this.style.display = 'none';
        });
    });
</script>
@endsection
