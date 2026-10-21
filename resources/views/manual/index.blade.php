@extends('layouts.master')

@php
    $pageTitle = 'Manual de usuario';
    $totalFunciones = collect($modulos)->sum(function ($modulo) {
        return count($modulo['funciones']);
    });
@endphp

@section('styles')
<style>
    .manual-hero {
        display:flex;
        align-items:flex-end;
        justify-content:space-between;
        gap:22px;
        margin-bottom:22px;
        padding:26px 30px;
        border:1px solid #cfe5d7;
        border-radius:20px;
        background:linear-gradient(120deg,#fff 0%,#eff8f2 100%);
        box-shadow:0 8px 26px rgba(0,66,37,.06);
    }
    .manual-kicker {
        display:inline-flex;
        align-items:center;
        gap:7px;
        margin-bottom:9px;
        color:var(--cth-green);
        font-size:10px;
        font-weight:800;
        letter-spacing:.12em;
        text-transform:uppercase;
    }
    .manual-hero h1 { margin:0; color:#10251a; font-size:clamp(24px,3vw,34px); font-weight:850; letter-spacing:-.04em; }
    .manual-hero p { max-width:700px; margin:8px 0 0; color:#617268; font-size:14px; line-height:1.65; }
    .manual-role {
        flex:0 0 auto;
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:9px 13px;
        border:1px solid #b8dbc5;
        border-radius:999px;
        background:#fff;
        color:#14532d;
        font-size:12px;
        font-weight:800;
    }
    .manual-toolbar {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:14px;
        margin:0 0 16px;
    }
    .manual-toolbar p { margin:0; color:#64748b; font-size:13px; }
    .manual-search { position:relative; width:min(360px,100%); }
    .manual-search i { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:#829087; }
    .manual-search input {
        width:100%;
        height:42px;
        padding:0 14px 0 38px;
        border:1px solid var(--cth-border);
        border-radius:11px;
        outline:0;
        background:#fff;
        color:var(--cth-ink);
        font:inherit;
        font-size:13px;
    }
    .manual-search input:focus { border-color:#57a879; box-shadow:0 0 0 3px rgba(0,104,55,.1); }
    .manual-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
    .manual-card {
        --manual-accent:#16834c;
        display:flex;
        min-height:184px;
        flex-direction:column;
        padding:18px 19px 15px;
        border:1px solid #e0e8e2;
        border-top:4px solid var(--manual-accent);
        border-radius:17px;
        background:linear-gradient(155deg,#fff 0%,#fff 70%,color-mix(in srgb,var(--manual-accent) 3%,white) 100%);
        box-shadow:0 3px 10px rgba(15,23,42,.045);
        transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease;
    }
    .manual-card:hover { transform:translateY(-3px); border-color:color-mix(in srgb,var(--manual-accent) 38%,white); box-shadow:0 14px 30px rgba(15,23,42,.1); }
    .manual-card--blue { --manual-accent:#3183c7; }
    .manual-card--orange { --manual-accent:#e58b20; }
    .manual-card--purple { --manual-accent:#9a58b7; }
    .manual-card--red { --manual-accent:#d24c54; }
    .manual-card--teal { --manual-accent:#159a91; }
    .manual-card-head { display:flex; align-items:center; justify-content:space-between; gap:10px; }
    .manual-card-icon {
        display:grid;
        width:40px;
        height:40px;
        place-items:center;
        border:1px solid color-mix(in srgb,var(--manual-accent) 16%,white);
        border-radius:13px;
        background:color-mix(in srgb,var(--manual-accent) 9%, white);
        color:var(--manual-accent);
        font-size:15px;
    }
    .manual-card-tag {
        padding:4px 8px;
        border-radius:999px;
        background:#f2f6f3;
        color:#587064;
        font-size:10px;
        font-weight:800;
    }
    .manual-card h2 { margin:14px 0 5px; color:#17251c; font-size:16px; font-weight:850; letter-spacing:-.02em; }
    .manual-card p { margin:0; color:#68786e; font-size:12px; line-height:1.6; }
    .manual-card-access {
        display:inline-flex;
        align-items:center;
        gap:6px;
        align-self:flex-start;
        margin-top:12px;
        padding:5px 8px;
        border:1px solid #dce9df;
        border-radius:999px;
        background:#f5faf6;
        color:#42634e;
        font-size:10px;
        font-weight:800;
    }
    .manual-card-access.is-request { border-color:#f4d9a8; background:#fff9ed; color:#98600d; }
    .manual-card-access i { font-size:10px; }
    .manual-card-foot {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        margin-top:auto;
        padding-top:15px;
        border-top:1px solid #edf1ee;
        color:#87948c;
        font-size:11px;
    }
    .manual-open {
        display:inline-flex;
        align-items:center;
        gap:6px;
        padding:0;
        border:0;
        background:transparent;
        color:var(--manual-accent);
        font:inherit;
        font-weight:800;
        cursor:pointer;
    }
    .manual-open:hover { text-decoration:underline; }
    .manual-empty {
        display:none;
        grid-column:1/-1;
        padding:30px;
        border:1px dashed #cbd5ce;
        border-radius:14px;
        color:#68776d;
        text-align:center;
    }
    .manual-note {
        display:flex;
        align-items:flex-start;
        gap:10px;
        margin-top:18px;
        padding:12px 15px;
        border:1px solid #dbe8df;
        border-radius:12px;
        background:#f8fbf9;
        color:#5d6f63;
        font-size:12px;
        line-height:1.55;
    }
    .manual-note i { margin-top:2px; color:var(--cth-green); }
    .manual-modal {
        position:fixed;
        inset:0;
        z-index:1200;
        display:grid;
        place-items:center;
        padding:18px;
        background:rgba(20,35,27,.48);
        backdrop-filter:blur(3px);
    }
    body.manual-modal-open { overflow:hidden; }
    .manual-modal[hidden] { display:none; }
    .manual-modal-box {
        display:flex;
        width:min(560px,100%);
        max-height:min(82vh,760px);
        flex-direction:column;
        overflow:hidden;
        border:1px solid rgba(255,255,255,.8);
        border-radius:19px;
        background:#fff;
        box-shadow:0 24px 70px rgba(10,32,20,.28);
    }
    .manual-modal-head {
        position:relative;
        flex:0 0 auto;
        padding:20px 54px 18px 22px;
        background:linear-gradient(115deg,#087c43,#1d9a57);
        color:#fff;
    }
    .manual-modal-head small { display:block; margin-bottom:5px; color:rgba(255,255,255,.8); font-size:9px; font-weight:800; letter-spacing:.14em; text-transform:uppercase; }
    .manual-modal-head h2 { margin:0; font-size:20px; font-weight:850; }
    .manual-modal-head p { margin:5px 0 0; color:rgba(255,255,255,.87); font-size:12px; line-height:1.5; }
    .manual-modal-close {
        position:absolute;
        top:14px;
        right:14px;
        display:grid;
        width:31px;
        height:31px;
        place-items:center;
        border:1px solid rgba(255,255,255,.45);
        border-radius:50%;
        background:rgba(255,255,255,.14);
        color:#fff;
        cursor:pointer;
    }
    .manual-modal-body { overflow:auto; padding:19px 22px 22px; }
    .manual-section + .manual-section { margin-top:20px; }
    .manual-access-panel {
        margin-top:19px;
        padding:14px;
        border:1px solid #dce9df;
        border-radius:13px;
        background:linear-gradient(135deg,#f5faf6,#fff);
    }
    .manual-access-panel h3 { display:flex; align-items:center; gap:7px; margin:0 0 7px; color:#245b39; font-size:11px; font-weight:850; letter-spacing:.08em; text-transform:uppercase; }
    .manual-access-panel h3 i { color:var(--cth-green); }
    .manual-access-description { margin:0 0 11px; color:#52665a; font-size:12px; line-height:1.55; }
    .manual-permission-list { display:grid; gap:7px; margin:0; padding:0; list-style:none; }
    .manual-permission-list li { display:flex; align-items:flex-start; gap:8px; padding:9px 10px; border:1px solid #e7eee9; border-radius:9px; background:#fff; color:#45584b; font-size:11px; line-height:1.45; }
    .manual-permission-list li > i { flex:0 0 auto; margin-top:2px; color:#647b6b; }
    .manual-permission-list li.is-locked { border-color:#f2dfbc; background:#fffaf1; }
    .manual-permission-list li.is-locked > i { color:#ba7917; }
    .manual-permission-status { display:block; margin-top:2px; color:#16834c; font-size:10px; font-weight:800; }
    .manual-permission-list li.is-locked .manual-permission-status { color:#a56a12; }
    .manual-access-footnote { margin:10px 0 0; color:#65776a; font-size:11px; line-height:1.5; }
    .manual-section h3 { margin:0 0 10px; color:#41564a; font-size:10px; font-weight:850; letter-spacing:.1em; text-transform:uppercase; }
    .manual-list { display:grid; gap:9px; margin:0; padding:0; list-style:none; }
    .manual-list li { display:flex; align-items:flex-start; gap:9px; color:#506057; font-size:12px; line-height:1.55; }
    .manual-list li i { flex:0 0 auto; margin-top:3px; color:#db8a1f; font-size:12px; }
    .manual-steps { counter-reset:manual-step; }
    .manual-steps li { counter-increment:manual-step; }
    .manual-steps li:before {
        display:grid;
        flex:0 0 19px;
        width:19px;
        height:19px;
        place-items:center;
        border-radius:50%;
        background:#fff3dd;
        color:#bd7414;
        content:counter(manual-step);
        font-size:10px;
        font-weight:850;
    }
    .manual-modal-actions { display:flex; justify-content:flex-end; gap:8px; margin-top:20px; }
    .manual-modal-actions a, .manual-modal-actions button {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        min-height:38px;
        padding:8px 13px;
        border:1px solid #dbe4de;
        border-radius:9px;
        background:#fff;
        color:#42604d;
        font:inherit;
        font-size:12px;
        font-weight:800;
        text-decoration:none;
        cursor:pointer;
    }
    .manual-modal-actions a { border-color:#087c43; background:#087c43; color:#fff; }
    .manual-modal-actions a:hover { background:#056435; color:#fff; }
    @media (max-width:900px) { .manual-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:620px) {
        .manual-hero { align-items:flex-start; flex-direction:column; padding:21px; }
        .manual-toolbar { align-items:stretch; flex-direction:column; }
        .manual-search { width:100%; }
        .manual-grid { grid-template-columns:1fr; }
        .manual-card { min-height:160px; }
        .manual-modal { padding:10px; }
        .manual-modal-box { max-height:88vh; }
        .manual-modal-head { padding:18px 48px 15px 18px; }
        .manual-modal-body { padding:17px 18px 19px; }
    }
</style>
@endsection

@section('page-header')
    <section class="manual-hero">
        <div>
            <span class="manual-kicker"><i class="far fa-compass"></i> Guía del sistema</span>
            <h1>Manual de usuario</h1>
            <p>Conoce qué hace cada módulo y cómo usarlo. Esta guía se adapta a las opciones disponibles para tu perfil y permisos.</p>
        </div>
        <span class="manual-role"><i class="fas fa-user-shield"></i> {{ $rolManual }}</span>
    </section>
@endsection

@section('content')
    <div class="manual-toolbar">
        <p><strong>{{ count($modulos) }} módulos</strong> · {{ $totalFunciones }} funciones descritas</p>
        <label class="manual-search">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input id="manual-search" type="search" placeholder="Buscar un módulo..." aria-label="Buscar un módulo">
        </label>
    </div>

    <section class="manual-grid" id="manual-grid" aria-label="Módulos disponibles">
        @foreach($modulos as $modulo)
            <article class="manual-card manual-card--{{ $modulo['color'] }}" data-manual-card>
                <div class="manual-card-head">
                    <span class="manual-card-icon"><i class="{{ $modulo['icono'] }}" aria-hidden="true"></i></span>
                    <span class="manual-card-tag">{{ $modulo['grupo'] }}</span>
                </div>
                <h2>{{ $modulo['titulo'] }}</h2>
                <p>{{ $modulo['descripcion'] }}</p>
                <span class="manual-card-access {{ $modulo['acceso']['requiere_solicitud'] ? 'is-request' : '' }}">
                    <i class="fas {{ $modulo['acceso']['requiere_solicitud'] ? 'fa-key' : 'fa-shield-alt' }}" aria-hidden="true"></i>
                    @if(!$modulo['acceso']['modulo_habilitado'])
                        {{ $modulo['acceso']['permiso_modulo_solicitable'] ? 'Solicita acceso' : 'Acceso restringido' }}
                    @elseif($modulo['acceso']['requiere_solicitud'])
                        Requiere permiso
                    @elseif(count($modulo['acceso']['acciones']))
                        Permisos por acción
                    @else
                        {{ $modulo['acceso']['etiqueta'] }}
                    @endif
                </span>
                <div class="manual-card-foot">
                    <span>{{ count($modulo['funciones']) }} funciones</span>
                    <button class="manual-open" type="button" data-manual-open="{{ $modulo['id'] }}" aria-haspopup="dialog">
                        Ver detalle <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </button>
                </div>
            </article>
            <template id="manual-detail-{{ $modulo['id'] }}">
                <header class="manual-modal-head">
                    <small>{{ $modulo['grupo'] }} · Manual SIPD</small>
                    <h2>{{ $modulo['titulo'] }}</h2>
                    <p>{{ $modulo['descripcion'] }}</p>
                    <button class="manual-modal-close" type="button" data-manual-close aria-label="Cerrar detalle">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </header>
                <div class="manual-modal-body">
                    @if(!empty($modulo['antes']))
                        <section class="manual-section">
                            <h3>Antes de empezar</h3>
                            <p>{{ $modulo['antes'] }}</p>
                        </section>
                    @endif
                    <section class="manual-section">
                        <h3>Funciones principales</h3>
                        <ul class="manual-list">
                            @foreach($modulo['funciones'] as $funcion)
                                <li><i class="fas fa-check-circle" aria-hidden="true"></i><span>{{ $funcion }}</span></li>
                            @endforeach
                        </ul>
                    </section>
                    <section class="manual-section">
                        <h3>Cómo se usa</h3>
                        <ol class="manual-list manual-steps">
                            @foreach($modulo['pasos'] as $paso)
                                <li><span>{{ $paso }}</span></li>
                            @endforeach
                        </ol>
                    </section>
                    <section class="manual-section">
                        <h3>Consejos</h3>
                        <ul class="manual-list">
                            @foreach($modulo['consejos'] as $consejo)
                                <li><i class="fas fa-lightbulb" aria-hidden="true"></i><span>{{ $consejo }}</span></li>
                            @endforeach
                        </ul>
                    </section>
                    <section class="manual-access-panel" aria-label="Permisos y acceso">
                        <h3><i class="fas fa-shield-alt" aria-hidden="true"></i> Acceso y permisos</h3>
                        <p class="manual-access-description">{{ $modulo['acceso']['descripcion'] }}</p>
                        @if($modulo['acceso']['permiso_modulo'])
                            <p class="manual-access-description"><strong>Acceso principal:</strong> {{ $modulo['acceso']['permiso_modulo'] }}</p>
                            @if(!$modulo['acceso']['modulo_habilitado'])
                                <p class="manual-access-description">
                                    <strong>Estado:</strong>
                                    @if($modulo['acceso']['permiso_modulo_solicitable'])
                                        Solicita este permiso desde la barra superior y espera la aprobación.
                                    @else
                                        Pide a la coordinadora que habilite este acceso en Equipo y permisos.
                                    @endif
                                </p>
                            @endif
                        @endif
                        @if(count($modulo['acceso']['acciones']))
                            <ul class="manual-permission-list">
                                @foreach($modulo['acceso']['acciones'] as $accionPermiso)
                                    @php
                                        $bloqueada = !$accionPermiso['habilitado'];
                                        $estadoPermiso = $accionPermiso['habilitado']
                                            ? 'Habilitado en tu cuenta'
                                            : ($accionPermiso['solicitable']
                                                ? 'Solicítalo a la coordinadora'
                                                : 'Pide a la coordinadora que lo habilite');
                                    @endphp
                                    <li class="{{ $bloqueada ? 'is-locked' : '' }}">
                                        <i class="fas {{ $bloqueada ? 'fa-lock' : 'fa-check-circle' }}" aria-hidden="true"></i>
                                        <span>
                                            <strong>{{ $accionPermiso['nombre'] }}.</strong> {{ $accionPermiso['detalle'] }}
                                            <small class="manual-permission-status">{{ $estadoPermiso }}</small>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if(!$esCoordinadora)
                            <p class="manual-access-footnote">Para pedir un permiso disponible, usa <strong>Solicitar permiso</strong> en la barra superior, explica para qué lo necesitas y espera la aprobación. Si el acceso principal al módulo no aparece entre las opciones, pide a la coordinadora que lo habilite en Equipo y permisos. Una solicitud pendiente no activa el acceso.</p>
                        @endif
                    </section>
                    @if(!empty($modulo['resultado']))
                        <section class="manual-section">
                            <h3>Al finalizar</h3>
                            <p>{{ $modulo['resultado'] }}</p>
                        </section>
                    @endif
                    <div class="manual-modal-actions">
                        <button type="button" data-manual-close>Cerrar</button>
                        @if($modulo['ruta'] && $modulo['acceso']['modulo_habilitado'])
                            <a href="{{ $modulo['ruta'] }}">{{ $modulo['accion'] }} <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                        @endif
                    </div>
                </div>
            </template>
        @endforeach
        <div class="manual-empty" id="manual-empty">No hay módulos que coincidan con la búsqueda.</div>
    </section>

    <div class="manual-note">
        <i class="fas fa-key" aria-hidden="true"></i>
        <span><strong>¿Te falta una acción?</strong> Abre la tarjeta del módulo y revisa “Acceso y permisos”. Las acciones solicitables se piden con <strong>Solicitar permiso</strong> en la barra superior; el acceso a algunos módulos lo habilita directamente la coordinadora desde Equipo y permisos. Espera la aprobación antes de intentar la acción; algunas autorizaciones tienen duración limitada.</span>
    </div>

    <div class="manual-modal" id="manual-modal" role="dialog" aria-modal="true" aria-labelledby="manual-modal-title" hidden>
        <div class="manual-modal-box" id="manual-modal-box"></div>
    </div>

    <script>
        (function () {
            var search = document.getElementById('manual-search');
            var cards = Array.prototype.slice.call(document.querySelectorAll('[data-manual-card]'));
            var empty = document.getElementById('manual-empty');
            var modal = document.getElementById('manual-modal');
            var modalBox = document.getElementById('manual-modal-box');
            var lastTrigger = null;

            function closeModal() {
                modal.hidden = true;
                document.body.classList.remove('manual-modal-open');
                modalBox.innerHTML = '';
                if (lastTrigger) lastTrigger.focus();
            }

            cards.forEach(function (card) {
                var button = card.querySelector('[data-manual-open]');
                button.addEventListener('click', function () {
                    var template = document.getElementById('manual-detail-' + button.dataset.manualOpen);
                    if (!template) return;
                    lastTrigger = button;
                    modalBox.innerHTML = template.innerHTML;
                    modal.hidden = false;
                    document.body.classList.add('manual-modal-open');
                    modalBox.querySelector('[data-manual-close]').focus();
                });
            });

            modal.addEventListener('click', function (event) {
                if (event.target === modal || event.target.closest('[data-manual-close]')) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.hidden) closeModal();
            });

            search.addEventListener('input', function () {
                var term = search.value.trim().toLocaleLowerCase();
                var visible = 0;
                cards.forEach(function (card) {
                    var matches = card.textContent.toLocaleLowerCase().indexOf(term) !== -1;
                    card.hidden = !matches;
                    if (matches) visible++;
                });
                empty.style.display = visible === 0 ? 'block' : 'none';
            });
        })();
    </script>
@endsection
