@extends('layouts.master')

@php
    $pageTitle = 'Equipo de RH';
@endphp

@section('page-header')
    <div class="proc-head">
        <div>
            <h1>Equipo de RH</h1>
            <p>Personal operativo que tramita los expedientes disciplinarios.</p>
        </div>
        <button type="button" class="btn-add" id="btnAgregar">
            <i class="fas fa-plus"></i> Agregar nuevo integrante
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
        margin: 0 0 6px;
        font-size: 16px;
        font-weight: 700;
    }

    .form-lead {
        margin: 0 0 16px;
        color: #64748b;
        font-size: 13px;
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

    .form-grid input,
    .form-grid select {
        width: 100%;
        height: 42px;
        border: 1px solid var(--cth-border);
        border-radius: 10px;
        padding: 0 12px;
        font: inherit;
        font-size: 14px;
        outline: none;
        background: #fff;
    }

    .form-grid select {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%2364748b' d='M1 1l5 5 5-5'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 32px;
    }

    .form-grid input:focus,
    .form-grid select:focus { border-color: var(--cth-green-bright); }

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

    .eq-board { margin-top: 4px; }
    .eq-board-title {
        margin: 0 0 6px;
        text-align: center;
        font-size: 20px;
        font-weight: 800;
        color: var(--cth-ink);
        letter-spacing: -.02em;
    }
    .eq-board-lead {
        margin: 0 0 22px;
        text-align: center;
        color: #64748b;
        font-size: 14px;
    }
    .eq-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
    }
    .eq-card {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        background: #fff;
        border: 1px solid var(--cth-border);
        border-radius: 22px;
        padding: 28px 22px 22px;
        box-shadow: var(--cth-shadow);
        min-height: 320px;
    }
    .eq-card:hover { border-color: #9cbcab; }
    .eq-card.is-inactive { opacity: .72; background: #f8fafc; }
    .eq-card-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: var(--cth-green-soft);
        color: var(--cth-green);
        display: grid;
        place-items: center;
        font-size: 22px;
        margin-bottom: 14px;
    }
    .eq-card h3 {
        margin: 0 0 8px;
        font-size: 18px;
        font-weight: 800;
        color: var(--cth-ink);
        letter-spacing: -.02em;
    }
    .eq-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 12px;
        border-radius: 999px;
        background: #ecfdf5;
        color: var(--cth-green-text);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .eq-badge.off { background: #f1f5f9; color: #64748b; }
    .eq-card hr {
        width: 100%;
        border: 0;
        border-top: 1px solid var(--cth-line);
        margin: 18px 0 16px;
    }
    .eq-assign {
        margin: 0 0 6px;
        color: #334155;
        font-size: 14px;
        font-weight: 600;
        line-height: 1.4;
    }
    .eq-assign small {
        display: block;
        margin-top: 4px;
        color: #94a3b8;
        font-size: 12px;
        font-weight: 500;
    }
    .eq-lock {
        margin-top: auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        border: 0;
        background: transparent;
        color: var(--cth-green);
        font: inherit;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        padding: 18px 8px 4px;
    }
    .eq-lock:hover { color: var(--cth-green-dark); }
    .eq-lock i { font-size: 16px; }
    .eq-board .sipd-pager { margin-top: 22px; }
    .eq-card-acts {
        position: absolute;
        top: 12px;
        right: 12px;
        display: flex;
        gap: 4px;
    }
    .eq-card-acts button {
        width: 32px;
        height: 32px;
        border: 0;
        background: transparent;
        color: #94a3b8;
        cursor: pointer;
        border-radius: 8px;
    }
    .eq-card-acts button.edit-rh:hover { background: #ecfdf5; color: var(--cth-green-text); }
    .eq-card-acts button.danger:hover { background: #fff1f2; color: #e11d48; }
    .eq-empty {
        grid-column: 1 / -1;
        text-align: center;
        padding: 48px 16px;
        color: #94a3b8;
        background: #fff;
        border: 1px dashed var(--cth-border);
        border-radius: 22px;
    }

    @media (max-width: 900px) {
        .form-grid { grid-template-columns: 1fr; }
        .eq-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 640px) {
        .eq-grid { grid-template-columns: 1fr; }
    }
    .perm-lead { margin: 0 0 16px; color: #64748b; font-size: 13px; line-height: 1.45; }
    .perm-back {
        display: none;
        align-items: center;
        gap: 8px;
        margin: 0 0 14px;
        border: 0;
        background: transparent;
        color: var(--cth-green);
        font: inherit;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        padding: 0;
    }
    .perm-back.is-on { display: inline-flex; }
    .perm-mod-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 8px;
    }
    .perm-mod-grid.is-off { display: none; }
    .perm-mod-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 8px;
        min-height: 132px;
        background: #fff;
        border: 1px solid var(--cth-border);
        border-radius: 16px;
        padding: 16px 10px 14px;
        cursor: pointer;
        font: inherit;
        color: inherit;
        box-shadow: var(--cth-shadow);
    }
    .sipd-dialog .perm-mod-card { background: #fff; }
    .perm-mod-card:hover { border-color: #9cbcab; }
    .perm-mod-card.is-on { border-color: var(--cth-green-bright); background: #f7fbf8; }
    .perm-mod-icon {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: var(--cth-green-soft);
        color: var(--cth-green);
        display: grid;
        place-items: center;
        font-size: 16px;
    }
    .perm-mod-card b {
        font-size: 13px;
        font-weight: 700;
        color: var(--cth-ink);
        line-height: 1.3;
    }
    .perm-mod-card small {
        color: #94a3b8;
        font-size: 11px;
        font-weight: 600;
    }
    .perm-group { display: none; margin-bottom: 8px; }
    .perm-group.is-open { display: block; }
    .perm-head {
        margin-bottom: 8px;
    }
    .perm-group h4 { margin: 0 0 4px; font-size: 15px; font-weight: 800; color: var(--cth-ink); letter-spacing: -.02em; text-transform: none; }
    .perm-group p { margin: 0; color: #94a3b8; font-size: 12px; line-height: 1.4; max-width: 520px; }
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
    .sipd-dialog .perm-row { display: grid; }
    .perm-row:last-child { border-bottom: 0; }
    .perm-row input[type="checkbox"] { margin-top: 4px; }
    .perm-row .perm-time { grid-column: 2; margin-top: 2px; }
    .perm-copy b { display: block; color: #0f172a; font-weight: 600; }
    .perm-copy small { display: block; margin-top: 2px; color: #94a3b8; font-size: 12px; font-weight: 400; line-height: 1.35; }
    .perm-flag {
        display: inline-block;
        margin-top: 4px;
        font-size: 11px;
        font-weight: 700;
        color: #94a3b8;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .perm-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 14px;
    }
    .perm-chips > p {
        flex: 1 0 100%;
        width: 100%;
        max-width: none;
        margin: 0 0 2px;
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
    }
    .sipd-dialog label.perm-chip {
        display: inline-flex;
        align-items: center;
        margin: 0;
        padding: 8px 12px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background: #f1f5f9;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.2;
        cursor: pointer;
    }
    .perm-chip input {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        border: 0;
    }
    .sipd-dialog label.perm-chip.is-on {
        background: var(--cth-green-soft);
        border-color: var(--cth-green-bright);
        color: var(--cth-green);
    }
    .sipd-dialog.modal-perm { max-width: 760px; max-height: 88vh; overflow: auto; }
    @media (max-width: 700px) {
        .perm-mod-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
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

    .estado-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }
    .estado-chip.on { background: #ecfdf5; color: #166534; }
    .estado-chip.off { background: #f1f5f9; color: #64748b; }
    .estado-chip i { font-size: 8px; }
    tr.is-inactive td { color: #94a3b8; }
    tr.is-inactive .who { color: #64748b; }

    .perfil-toggle {
        margin: 4px 0 16px;
        padding: 12px 14px;
        background: #f8fafc;
        border: 1px solid var(--cth-border);
        border-radius: 12px;
    }
    .sipd-dialog .toggle-line {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        cursor: pointer;
        margin: 0;
        font-weight: 500;
        color: #0f172a;
    }
    .sipd-dialog .toggle-line input {
        position: absolute;
        opacity: 0;
        width: 0 !important;
        height: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        border: 0 !important;
    }
    .toggle-ui {
        width: 42px;
        height: 24px;
        border-radius: 999px;
        background: #cbd5e1;
        position: relative;
        flex-shrink: 0;
        margin-top: 2px;
        transition: background .15s;
    }
    .toggle-ui::after {
        content: '';
        position: absolute;
        top: 3px;
        left: 3px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15,23,42,.2);
        transition: transform .15s;
    }
    .sipd-dialog .toggle-line input:checked + .toggle-ui { background: var(--cth-green); }
    .sipd-dialog .toggle-line input:checked + .toggle-ui::after { transform: translateX(18px); }
    .toggle-copy { display: block; }
    .toggle-copy b { display: block; font-size: 14px; }
    .toggle-copy small { display: block; color: #64748b; font-size: 12px; font-weight: 500; margin-top: 2px; }
</style>
@endsection

@section('content')

    <div class="form-card" id="formularioContainer">
        <h3>Agregar nuevo integrante</h3>
        <p class="form-lead">Crea su cuenta de Equipo RH. Con el correo y la contraseña podrá entrar al sistema.</p>
        <form action="{{ route('coordinadora.abogados.guardar') }}" method="POST" autocomplete="off">
            @csrf
            <div class="form-grid">
                <div>
                    <label>Nombre completo</label>
                    <input type="text" name="name" required placeholder="Nombre y apellidos" autocomplete="off">
                </div>
                <div>
                    <label>Correo sipd</label>
                    <input type="email" name="email" required placeholder="nombreapellido@sipd.co" pattern="[A-Za-z]+@sipd\.co" title="texto@sipd.co" autocomplete="off">
                </div>
                <div>
                    <label>Contraseña</label>
                    <input type="password" name="password" required placeholder="Contraseña de acceso" autocomplete="new-password">
                </div>
                <div>
                    <label>Cargo</label>
                    <select name="cargo" required>
                        <option value="" disabled {{ old('cargo') ? '' : 'selected' }}>Selecciona el cargo</option>
                        @foreach($cargosEquipo as $cargoOpcion)
                            <option value="{{ $cargoOpcion }}" {{ old('cargo') === $cargoOpcion ? 'selected' : '' }}>{{ $cargoOpcion }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn-save">Crear integrante</button>
            </div>
        </form>
    </div>

    <section class="eq-board">
        <h2 class="eq-board-title">Distribuye cargos y permisos</h2>
        <p class="eq-board-lead">Asignados por la coordinadora a cada integrante del equipo.</p>
        <div class="eq-grid">
            @forelse($abogados as $abogado)
                @php
                    $vigentes = $abogado->permisos->filter(function ($p) { return $p->estaVigente(); });
                    $temps = $vigentes->filter(function ($p) { return $p->esTemporal(); })->count();
                @endphp
                <article class="eq-card{{ $abogado->estaActivo() ? '' : ' is-inactive' }}">
                    <div class="eq-card-acts">
                        <button type="button" class="edit-rh" title="Editar"
                            data-name="{{ $abogado->name }}"
                            data-email="{{ $abogado->email }}"
                            data-cargo="{{ $abogado->cargo }}"
                            data-activo="{{ $abogado->estaActivo() ? '1' : '0' }}"
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
                    <div class="eq-card-icon" aria-hidden="true">
                        <i class="far fa-user"></i>
                    </div>
                    <h3 title="{{ $abogado->name }}">{{ $abogado->nombreCorto() }}</h3>
                    @if($abogado->estaActivo())
                        <span class="eq-badge">Equipo RH</span>
                    @else
                        <span class="eq-badge off">Inactivo</span>
                    @endif
                    <hr>
                    <p class="eq-assign">
                        {{ $abogado->cargo ?: 'Equipo de RH' }}
                        <small>
                            Cargo asignado por la coordinadora
                            · {{ $vigentes->count() }} permiso{{ $vigentes->count() === 1 ? '' : 's' }}
                            @if($temps > 0)
                                · {{ $temps }} temporal{{ $temps === 1 ? '' : 'es' }}
                            @endif
                        </small>
                    </p>
                    <button type="button" class="eq-lock" title="Permisos"
                        data-name="{{ $abogado->name }}"
                        data-action="{{ route('coordinadora.abogados.permisos', $abogado->id) }}"
                        data-permisos='@json($abogado->permisosParaFormulario())'
                        onclick="abrirPermisos(this)">
                        <i class="fas fa-lock"></i>
                        Configurar permisos
                    </button>
                </article>
            @empty
                <div class="eq-empty">No hay personal de RH registrado</div>
            @endforelse
        </div>
        @include('partials.paginacion', ['paginador' => $abogados, 'etiqueta' => 'integrantes'])
    </section>

    <div class="sipd-dialog-bg" id="modalEditar">
        <div class="sipd-dialog">
            <h3>Editar integrante</h3>
            <form id="formEditar" method="POST">
                @csrf
                @method('PUT')
                <label>Nombre</label>
                <input type="text" name="name" id="editName" required value="{{ session('abrir_editar_rh') ? old('name') : '' }}">
                <label>Correo</label>
                <input type="email" name="email" id="editEmail" required value="{{ session('abrir_editar_rh') ? old('email') : '' }}">
                <label>Cargo</label>
                <input type="text" name="cargo" id="editCargo" required value="{{ session('abrir_editar_rh') ? old('cargo') : '' }}">
                <div class="perfil-toggle">
                    <input type="hidden" name="activo" value="0">
                    <label class="toggle-line" for="editActivo">
                        <input type="checkbox" name="activo" value="1" id="editActivo" {{ (session('abrir_editar_rh') ? (string) old('activo', '1') : '1') === '1' ? 'checked' : '' }}>
                        <span class="toggle-ui"></span>
                        <span class="toggle-copy">
                            <b id="editActivoLabel">Perfil activo</b>
                            <small>Si lo desactivas, esta persona no podrá iniciar sesión.</small>
                        </span>
                    </label>
                </div>
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
            <p class="perm-lead" id="permLead">Elige un módulo para configurar sus opciones. Inicio lo ve todo el equipo, por eso no aparece aquí.</p>
            <button type="button" class="perm-back" id="permBack" onclick="mostrarModulos()">
                <i class="fas fa-arrow-left"></i> Módulos
            </button>
            <form id="formPermisos" method="POST">
                @csrf
                @method('PUT')
                <div class="perm-mod-grid" id="permModGrid">
                    @foreach($catalogoPermisos as $grupoKey => $grupo)
                        <button type="button" class="perm-mod-card" data-grupo="{{ $grupoKey }}" onclick="abrirModulo('{{ $grupoKey }}')">
                            <span class="perm-mod-icon" aria-hidden="true">
                                <i class="{{ $grupo['icon'] ?? 'fas fa-folder' }}"></i>
                            </span>
                            <b>{{ $grupo['label'] }}</b>
                            <small class="perm-mod-count">0 de {{ count($grupo['items']) }}</small>
                        </button>
                    @endforeach
                </div>
                @foreach($catalogoPermisos as $grupoKey => $grupo)
                    <div class="perm-group" data-grupo="{{ $grupoKey }}" data-lead="{{ $grupo['lead'] ?? '' }}">
                        <div class="perm-head">
                            <h4>{{ $grupo['label'] }}</h4>
                            @if(!empty($grupo['desc']))
                                <p>{{ $grupo['desc'] }}</p>
                            @endif
                        </div>
                        @foreach($grupo['items'] as $clave => $etiqueta)
                            @php $conTiempo = \App\Support\RhPermisos::esTemporalizable($clave); @endphp
                            <label class="perm-row{{ $conTiempo ? ' has-time' : '' }}">
                                <input type="checkbox" name="permisos[]" value="{{ $clave }}">
                                <span class="perm-copy">
                                    <b>{{ $etiqueta }}</b>
                                    @if(!empty($grupo['hints'][$clave]))
                                        <small>{{ $grupo['hints'][$clave] }}</small>
                                    @endif
                                    @unless($conTiempo)
                                        <span class="perm-flag">Permanente</span>
                                    @endunless
                                </span>
                                @if($conTiempo)
                                    <div class="perm-time">
                                        <span>Tiempo</span>
                                        <select name="duracion[{{ $clave }}]" class="fn-dur" data-clave="{{ $clave }}">
                                            <option value="permanente">Permanente</option>
                                            @foreach($duracionesPermiso as $valor => $texto)
                                                <option value="{{ $valor }}">{{ $texto }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number" name="horas[{{ $clave }}]" min="1" max="168" placeholder="Horas" class="fn-hrs" style="display:none;">
                                    </div>
                                @endif
                            </label>
                        @endforeach
                        @if($grupoKey === 'registro')
                            <div class="perm-chips">
                                <p>Modalidades</p>
                                @foreach(\App\Support\Modalidades::permisos() as $clave => $etiqueta)
                                    <label class="perm-chip">
                                        <input type="checkbox" name="permisos[]" value="{{ $clave }}">
                                        <span>{{ $etiqueta }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
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

    function actualizarEtiquetaActivo() {
        var on = document.getElementById('editActivo').checked;
        document.getElementById('editActivoLabel').textContent = on ? 'Perfil activo' : 'Perfil inactivo';
    }

    function abrirModalEditar(btn) {
        document.getElementById('editName').value = btn.getAttribute('data-name') || '';
        document.getElementById('editEmail').value = btn.getAttribute('data-email') || '';
        document.getElementById('editCargo').value = btn.getAttribute('data-cargo') || '';
        document.getElementById('formEditar').action = btn.getAttribute('data-action') || '';
        document.getElementById('editActivo').checked = btn.getAttribute('data-activo') === '1';
        actualizarEtiquetaActivo();
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

    document.getElementById('editActivo').addEventListener('change', actualizarEtiquetaActivo);

    @if(session('abrir_editar_rh'))
        document.getElementById('formEditar').action = @json(session('abrir_editar_rh'));
        document.getElementById('editActivo').checked = @json((string) old('activo', '1') === '1');
        actualizarEtiquetaActivo();
        document.getElementById('modalEditar').style.display = 'flex';
    @endif

    function refrescarResumenModulos() {
        document.querySelectorAll('.perm-mod-card').forEach(function (card) {
            var key = card.getAttribute('data-grupo');
            var group = document.querySelector('#formPermisos .perm-group[data-grupo="' + key + '"]');
            if (!group) return;
            var cbs = group.querySelectorAll('.perm-row input[type="checkbox"]');
            var on = group.querySelectorAll('.perm-row input[type="checkbox"]:checked').length;
            var count = card.querySelector('.perm-mod-count');
            if (count) count.textContent = on + ' de ' + cbs.length;
            card.classList.toggle('is-on', on > 0);
        });
    }

    function mostrarModulos() {
        document.getElementById('permModGrid').classList.remove('is-off');
        document.getElementById('permBack').classList.remove('is-on');
        document.getElementById('permLead').textContent = 'Elige un módulo para configurar sus opciones. Inicio lo ve todo el equipo, por eso no aparece aquí.';
        document.querySelectorAll('#formPermisos .perm-group').forEach(function (group) {
            group.classList.remove('is-open');
        });
        refrescarResumenModulos();
    }

    function abrirModulo(grupo) {
        document.getElementById('permModGrid').classList.add('is-off');
        document.getElementById('permBack').classList.add('is-on');
        var lead = 'Ver, registrar, guardar borrador y descargar se activan o se apagan y quedan permanentes. El tiempo solo aplica a editar y eliminar.';
        document.querySelectorAll('#formPermisos .perm-group').forEach(function (group) {
            var abierto = group.getAttribute('data-grupo') === grupo;
            group.classList.toggle('is-open', abierto);
            if (!abierto) return;
            if (group.getAttribute('data-lead')) lead = group.getAttribute('data-lead');
            syncTodasDuraciones();
        });
        document.getElementById('permLead').textContent = lead;
    }

    function abrirPermisos(btn) {
        var data = {};
        try { data = JSON.parse(btn.getAttribute('data-permisos') || '{}'); } catch (e) { data = {}; }
        document.getElementById('permNombre').textContent = btn.getAttribute('data-name') || '';
        document.getElementById('formPermisos').action = btn.getAttribute('data-action') || '';
        document.querySelectorAll('#formPermisos input[type="checkbox"]').forEach(function (cb) {
            var grant = data[cb.value];
            cb.checked = !!(grant && grant.on);
        });
        document.querySelectorAll('#formPermisos .fn-dur').forEach(function (sel) {
            var grant = data[sel.getAttribute('data-clave')] || {};
            var hrs = sel.parentNode.querySelector('.fn-hrs');
            sel.value = grant.duracion || 'permanente';
            if (hrs) hrs.value = grant.horas || '';
        });
        syncTodasDuraciones();
        syncChips();
        mostrarModulos();
        document.getElementById('modalPermisos').style.display = 'flex';
    }

    function cerrarPermisos() {
        document.getElementById('modalPermisos').style.display = 'none';
        mostrarModulos();
    }

    var REQUIERE = @json($requierePermisos ?? []);

    function permCbs(clave) {
        return document.querySelectorAll('#formPermisos input[type="checkbox"][value="' + clave + '"]');
    }

    function hijosDe(padre) {
        return Object.keys(REQUIERE).filter(function (k) {
            return (REQUIERE[k] || []).indexOf(padre) !== -1;
        });
    }

    function syncFilaDuracion(row) {
        if (!row) return;
        var cb = row.querySelector('input[type="checkbox"]');
        var on = !!(cb && cb.checked);
        var sel = row.querySelector('.fn-dur');
        var hrs = row.querySelector('.fn-hrs');
        if (sel) sel.disabled = !on;
        if (hrs) {
            var custom = sel && sel.value === 'custom';
            hrs.style.display = custom ? '' : 'none';
            hrs.disabled = !on || !custom;
        }
    }

    function syncTodasDuraciones() {
        document.querySelectorAll('#formPermisos .perm-row.has-time').forEach(syncFilaDuracion);
    }

    function syncChips() {
        document.querySelectorAll('#formPermisos .perm-chip').forEach(function (chip) {
            var cb = chip.querySelector('input[type="checkbox"]');
            chip.classList.toggle('is-on', !!(cb && cb.checked));
        });
    }

    function marcarPadres(clave) {
        (REQUIERE[clave] || []).forEach(function (padre) {
            var encender = false;
            permCbs(padre).forEach(function (cb) {
                if (!cb.checked) encender = true;
                cb.checked = true;
            });
            if (encender) marcarPadres(padre);
        });
    }

    function desmarcarHijos(padre) {
        hijosDe(padre).forEach(function (hijo) {
            var apagar = false;
            permCbs(hijo).forEach(function (cb) {
                if (cb.checked) apagar = true;
                cb.checked = false;
            });
            if (apagar) desmarcarHijos(hijo);
        });
    }

    function syncGemelos(clave, checked) {
        permCbs(clave).forEach(function (cb) { cb.checked = checked; });
    }

    function syncDuracionGemela(origen) {
        var clave = origen.getAttribute('data-clave');
        if (!clave) return;
        document.querySelectorAll('#formPermisos .fn-dur[data-clave="' + clave + '"]').forEach(function (sel) {
            if (sel !== origen) sel.value = origen.value;
            var hrs = sel.parentNode.querySelector('.fn-hrs');
            var origenHrs = origen.parentNode.querySelector('.fn-hrs');
            if (hrs && origenHrs && hrs !== origenHrs) hrs.value = origenHrs.value;
        });
    }

    document.getElementById('formPermisos').addEventListener('change', function (e) {
        var row = e.target.closest('.perm-row');
        if (row && e.target.type === 'checkbox') {
            syncGemelos(e.target.value, e.target.checked);
            if (e.target.checked) {
                marcarPadres(e.target.value);
            } else {
                desmarcarHijos(e.target.value);
            }
        }
        if (e.target.classList.contains('fn-dur') || e.target.classList.contains('fn-hrs')) {
            var sel = e.target.classList.contains('fn-dur') ? e.target : e.target.parentNode.querySelector('.fn-dur');
            if (sel) syncDuracionGemela(sel);
        }
        syncTodasDuraciones();
        syncChips();
        refrescarResumenModulos();
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
