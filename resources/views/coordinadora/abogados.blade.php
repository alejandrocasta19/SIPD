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
        border: 1px solid var(--cth-border);
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
    .acts button.edit-rh:hover { background: #ecfdf5; color: var(--cth-green-text); }
    .perm-chip { font-size: 12px; color: #475569; }
    .perm-chip em { font-style: normal; color: #d97706; font-weight: 600; }
    .perm-lead { margin: 0 0 16px; color: #64748b; font-size: 13px; line-height: 1.45; }
    .perm-group { margin-bottom: 18px; padding-bottom: 6px; border-bottom: 1px solid var(--cth-line); }
    .perm-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 8px;
    }
    .perm-group h4 { margin: 0 0 4px; font-size: 12px; letter-spacing: .06em; text-transform: uppercase; color: #64748b; }
    .perm-group p { margin: 0; color: #94a3b8; font-size: 12px; line-height: 1.4; max-width: 380px; }
    .perm-time { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .perm-time span { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em; }
    .perm-time select, .perm-time input[type="number"] {
        height: 34px; border: 1px solid var(--cth-border); border-radius: 8px; font: inherit; font-size: 12px; padding: 0 8px;
    }
    .perm-time input[type="number"] { width: 88px; }
    .perm-row {
        display: grid;
        grid-template-columns: 18px minmax(0,1fr);
        gap: 8px;
        align-items: start;
        padding: 10px 0;
        border-bottom: 1px solid var(--cth-line);
        font-size: 14px;
    }
    .perm-row:last-child { border-bottom: 0; }
    .perm-row input[type="checkbox"] { margin-top: 4px; }
    .perm-copy b { display: block; color: #0f172a; font-weight: 600; }
    .perm-copy small { display: block; margin-top: 2px; color: #94a3b8; font-size: 12px; font-weight: 400; line-height: 1.35; }
    .sipd-dialog.modal-perm { max-width: 720px; max-height: 88vh; overflow: auto; }
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
        border: 1px solid var(--cth-border);
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
                                    data-modulos='@json($abogado->duracionesModuloParaFormulario())'
                                    onclick="abrirPermisos(this)">
                                    <i class="fas fa-key"></i>
                                </button>
                                <button type="button" class="edit-rh" title="Editar"
                                    data-name="{{ $abogado->name }}"
                                    data-email="{{ $abogado->email }}"
                                    data-cargo="{{ $abogado->cargo }}"
                                    data-action="{{ route('coordinadora.abogados.editar', $abogado->id) }}">
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
                <input type="text" name="name" id="editName" required value="{{ session('abrir_editar_rh') ? old('name') : '' }}">
                <label>Correo</label>
                <input type="email" name="email" id="editEmail" required value="{{ session('abrir_editar_rh') ? old('email') : '' }}">
                <label>Cargo</label>
                <input type="text" name="cargo" id="editCargo" required value="{{ session('abrir_editar_rh') ? old('cargo') : '' }}">
                <label>Nueva contraseña</label>
                <input type="password" name="nueva_password" id="editPassword" minlength="6" autocomplete="new-password" placeholder="Déjala vacía si no la cambias">
                <label>Confirmar contraseña</label>
                <input type="password" name="nueva_password_confirmation" id="editPasswordConfirm" minlength="6" autocomplete="new-password">
                @error('nueva_password')
                    <p class="sipd-dialog-lead" style="color:#be123c;">{{ $message }}</p>
                @enderror
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
            <p class="perm-lead">Ver, editar y eliminar van cada uno por su lado. El tiempo es uno por módulo y cubre las funciones que marques.</p>
            <form id="formPermisos" method="POST">
                @csrf
                @method('PUT')
                @foreach($catalogoPermisos as $grupoKey => $grupo)
                    <div class="perm-group" data-grupo="{{ $grupoKey }}">
                        <div class="perm-head">
                            <div>
                                <h4>{{ $grupo['label'] }}</h4>
                                @if(!empty($grupo['desc']))
                                    <p>{{ $grupo['desc'] }}</p>
                                @endif
                            </div>
                            <div class="perm-time">
                                <span>Tiempo del módulo</span>
                                <select name="duracion[{{ $grupoKey }}]" class="mod-dur">
                                    <option value="permanente">Permanente</option>
                                    @foreach($duracionesPermiso as $valor => $texto)
                                        <option value="{{ $valor }}">{{ $texto }}</option>
                                    @endforeach
                                </select>
                                <input type="number" name="horas[{{ $grupoKey }}]" min="1" max="168" placeholder="Horas" class="mod-hrs" style="display:none;">
                            </div>
                        </div>
                        @foreach($grupo['items'] as $clave => $etiqueta)
                            <label class="perm-row">
                                <input type="checkbox" name="permisos[]" value="{{ $clave }}">
                                <span class="perm-copy">
                                    <b>{{ $etiqueta }}</b>
                                    @if(!empty($grupo['hints'][$clave]))
                                        <small>{{ $grupo['hints'][$clave] }}</small>
                                    @endif
                                </span>
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

    function limpiarClaveEditar() {
        var pass = document.getElementById('editPassword');
        var conf = document.getElementById('editPasswordConfirm');
        if (pass) pass.value = '';
        if (conf) conf.value = '';
    }

    function abrirModalEditar(btn) {
        document.getElementById('editName').value = btn.getAttribute('data-name') || '';
        document.getElementById('editEmail').value = btn.getAttribute('data-email') || '';
        document.getElementById('editCargo').value = btn.getAttribute('data-cargo') || '';
        document.getElementById('formEditar').action = btn.getAttribute('data-action') || '';
        limpiarClaveEditar();
        document.getElementById('modalEditar').style.display = 'flex';
        document.getElementById('editName').focus();
    }

    document.querySelectorAll('.edit-rh').forEach(function (btn) {
        btn.addEventListener('click', function () {
            abrirModalEditar(btn);
        });
    });

    function cerrarModal() {
        document.getElementById('modalEditar').style.display = 'none';
        limpiarClaveEditar();
    }

    @if(session('abrir_editar_rh'))
        document.getElementById('formEditar').action = @json(session('abrir_editar_rh'));
        document.getElementById('modalEditar').style.display = 'flex';
    @endif

    function abrirPermisos(btn) {
        var data = {};
        var mods = {};
        try { data = JSON.parse(btn.getAttribute('data-permisos') || '{}'); } catch (e) { data = {}; }
        try { mods = JSON.parse(btn.getAttribute('data-modulos') || '{}'); } catch (e) { mods = {}; }
        document.getElementById('permNombre').textContent = btn.getAttribute('data-name') || '';
        document.getElementById('formPermisos').action = btn.getAttribute('data-action') || '';
        document.querySelectorAll('#formPermisos input[type="checkbox"]').forEach(function (cb) {
            var grant = data[cb.value];
            cb.checked = !!(grant && grant.on);
        });
        document.querySelectorAll('#formPermisos .perm-group').forEach(function (group) {
            var info = mods[group.getAttribute('data-grupo')] || {};
            var sel = group.querySelector('.mod-dur');
            var hrs = group.querySelector('.mod-hrs');
            if (sel) sel.value = info.duracion || 'permanente';
            if (hrs) hrs.value = info.horas || '';
            syncModuloDuracion(group);
        });
        document.getElementById('modalPermisos').style.display = 'flex';
    }

    function cerrarPermisos() {
        document.getElementById('modalPermisos').style.display = 'none';
    }

    var REQUIERE = @json($requierePermisos ?? []);

    function permCb(clave) {
        return document.querySelector('#formPermisos input[type="checkbox"][value="' + clave + '"]');
    }

    function hijosDe(padre) {
        return Object.keys(REQUIERE).filter(function (k) {
            return (REQUIERE[k] || []).indexOf(padre) !== -1;
        });
    }

    function syncModuloDuracion(group) {
        if (!group) return;
        var alguna = group.querySelector('input[type="checkbox"]:checked');
        var sel = group.querySelector('.mod-dur');
        var hrs = group.querySelector('.mod-hrs');
        if (sel) sel.disabled = !alguna;
        if (hrs) {
            var custom = sel && sel.value === 'custom';
            hrs.style.display = custom ? '' : 'none';
            hrs.disabled = !alguna || !custom;
        }
    }

    function marcarPadres(clave) {
        (REQUIERE[clave] || []).forEach(function (padre) {
            var cb = permCb(padre);
            if (cb && !cb.checked) {
                cb.checked = true;
                marcarPadres(padre);
            }
        });
    }

    function desmarcarHijos(padre) {
        hijosDe(padre).forEach(function (hijo) {
            var cb = permCb(hijo);
            if (cb && cb.checked) {
                cb.checked = false;
                desmarcarHijos(hijo);
            }
        });
    }

    document.getElementById('formPermisos').addEventListener('change', function (e) {
        var group = e.target.closest('.perm-group');
        var row = e.target.closest('.perm-row');
        if (row && e.target.type === 'checkbox') {
            if (e.target.checked) {
                marcarPadres(e.target.value);
            } else {
                desmarcarHijos(e.target.value);
            }
        }
        syncModuloDuracion(group);
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
