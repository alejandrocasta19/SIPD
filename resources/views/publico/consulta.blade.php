<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('images/logo-sipd.svg') }}" type="image/svg+xml">
    <title>Consultar mi caso · SIPD Cootranshuila</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ rtrim(request()->root(), '/') }}/css/sipd-theme.css?v=16">
</head>
<body class="consulta-page">
    <div class="consulta-hero">
        <div class="consulta-inner">
            <div class="consulta-top">
                <a class="consulta-brand" href="{{ route('login') }}">
                    <img src="{{ rtrim(request()->root(), '/') }}/images/logo-cootranshuila-claro.png" alt="Cootranshuila">
                    <span>
                        <span class="guest-sipd-row">
                            <img class="guest-sipd-mark" src="{{ rtrim(request()->root(), '/') }}/images/logo-sipd.svg" alt="">
                            <strong>SIPD</strong>
                        </span>
                        <small>Sistema de procesos disciplinarios</small>
                    </span>
                </a>
                <a class="consulta-back" href="{{ route('login') }}">← Volver al acceso</a>
            </div>
            <div class="guest-badge">ACCESO PÚBLICO</div>
            <h1>Consultar mi caso</h1>
            <p>Ingresa tu cédula para ver el avance del trámite. Esta consulta no muestra documentos, pruebas ni detalles reservados del expediente.</p>
        </div>
    </div>

    <div class="consulta-wrap">
        <div class="consulta-card">
            <form method="POST" action="{{ route('consulta.publica.buscar') }}" id="consulta-form">
                @csrf
                <div class="guest-field">
                    <label for="cedula">Número de cédula</label>
                    <input id="cedula" type="text" name="cedula" value="{{ old('cedula', $cedulaFormato ?? $cedula ?? '') }}" placeholder="Ej. 1.075.123.456" inputmode="numeric" autocomplete="off" maxlength="19" required class="{{ $errors->has('cedula') ? 'is-invalid' : '' }}">
                    @error('cedula')
                        <div class="guest-error">{{ $message }}</div>
                    @enderror
                    <p class="consulta-hint">Puedes escribirla con o sin puntos. Solo se usa para localizar tu expediente.</p>
                </div>
                <div class="consulta-actions">
                    <button type="submit" class="btn-submit consulta-submit" id="consulta-submit">Consultar</button>
                    @if(!empty($consultado))
                        <a class="consulta-reset" href="{{ route('consulta.publica', ['nueva' => 1]) }}">Nueva consulta</a>
                    @endif
                </div>
            </form>
        </div>

        @if(!empty($consultado))
            @if($seguimientos->isNotEmpty())
                <p class="consulta-found">
                    {{ $seguimientos->count() === 1 ? 'Se encontró 1 proceso' : 'Se encontraron ' . $seguimientos->count() . ' procesos' }}
                    para la cédula {{ $cedulaFormato ?? $cedula }}.
                </p>
            @endif

            @forelse($seguimientos as $caso)
                <article class="consulta-card consulta-result is-{{ $caso['estado_clase'] }}">
                    <div class="consulta-result-top">
                        <div>
                            <div class="consulta-code">{{ $caso['codigo'] }}</div>
                            <div class="consulta-exp">Expediente {{ $caso['expediente'] }}</div>
                        </div>
                        <div class="consulta-estado consulta-estado--{{ $caso['estado_clase'] }}">{{ $caso['estado'] }}</div>
                    </div>

                    <div class="consulta-meta">
                        <div class="consulta-name">{{ $caso['nombre'] }}</div>
                        <div>{{ $caso['tipo'] }}@if(!empty($caso['abierto_el'])) · Abierto el {{ $caso['abierto_el']['texto'] }}@endif</div>
                    </div>

                    <p class="consulta-status-msg">{{ $caso['mensaje'] }}</p>

                    <ol class="consulta-steps" aria-label="Avance del trámite">
                        @foreach($caso['etapas'] as $etapa)
                            <li class="consulta-step consulta-step--{{ $etapa['estado'] }}">
                                <span class="consulta-step-dot" aria-hidden="true">{{ $etapa['paso'] ?? '' }}</span>
                                <strong>{{ $etapa['titulo'] }}</strong>
                                <small>{{ $etapa['detalle'] }}</small>
                            </li>
                        @endforeach
                    </ol>

                    <div class="consulta-timeline-wrap">
                        <h2>Línea de tiempo</h2>
                        <ol class="consulta-timeline">
                            @foreach($caso['linea_tiempo'] as $evento)
                                <li class="{{ !empty($evento['actual']) ? 'is-current' : '' }}">
                                    <div class="consulta-timeline-date">
                                        {{ $evento['fecha']['texto'] ?? 'En curso' }}
                                    </div>
                                    <div class="consulta-timeline-body">
                                        <strong>{{ $evento['titulo'] }}</strong>
                                        <p>{{ $evento['detalle'] }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </article>
            @empty
                <div class="consulta-card consulta-empty">
                    <strong>No se encontraron procesos con esa cédula.</strong>
                    <p>Verifica el número e inténtalo de nuevo.</p>
                </div>
            @endforelse

            @if($seguimientos->isNotEmpty())
                <p class="consulta-privacy">Solo se muestra el estado del trámite y las actuaciones ya realizadas. No se publican faltas, descargos, decisiones ni archivos del caso.</p>
            @endif
        @endif
    </div>
    <script>
        (function () {
            var input = document.getElementById('cedula');
            var form = document.getElementById('consulta-form');
            var submit = document.getElementById('consulta-submit');
            if (!input) return;

            function formatCedula(value) {
                var digits = String(value || '').replace(/\D+/g, '').slice(0, 15);
                return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            input.addEventListener('input', function () {
                var start = input.selectionStart;
                var before = input.value.length;
                input.value = formatCedula(input.value);
                var diff = input.value.length - before;
                if (document.activeElement === input && start != null) {
                    input.setSelectionRange(start + diff, start + diff);
                }
            });

            form.addEventListener('submit', function () {
                submit.disabled = true;
                submit.textContent = 'Consultando…';
            });
        })();
    </script>
</body>
</html>
