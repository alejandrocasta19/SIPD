<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/images/logo-sipd.png?v=colibri" type="image/png">
    <title>Consultar mi caso · SIPD Cootranshuila</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ rtrim(request()->root(), '/') }}/css/sipd-theme.css?v=55">
    <link rel="stylesheet" href="{{ rtrim(request()->root(), '/') }}/css/sipd-halloween.css?v=2">
</head>
<body class="consulta-page">
    @include('partials.halloween', ['hwMode' => 'full'])
    <div class="consulta-hero">
        <div class="consulta-inner">
            <div class="consulta-top">
                <a class="consulta-brand" href="{{ route('login') }}">
                    <img src="/images/logo-cootranshuila.png?v=marca2" alt="Cootranshuila">
                    <span>
                        <span class="guest-sipd-row">
                            <img class="guest-sipd-mark" src="/images/logo-sipd.png?v=colibri" alt="">
                            <strong>SIPD</strong>
                        </span>
                        <small>Sistema de procesos disciplinarios</small>
                    </span>
                </a>
                <a class="consulta-back" href="{{ route('login') }}">← Volver al acceso</a>
            </div>
            <div class="guest-pills">
                <div class="guest-badge">ACCESO PÚBLICO</div>
                <span class="hw-season">HALLOWEEN</span>
            </div>
            <h1>Consultar mi caso</h1>
            <p>Esta consulta pública no confirma la existencia de expedientes ni muestra información disciplinaria sin una verificación segura de identidad.</p>
        </div>
    </div>

    <div class="consulta-wrap">
        <div class="consulta-card">
            <form method="POST" action="{{ route('consulta.publica.buscar') }}" id="consulta-form">
                @csrf
                <div class="guest-field">
                    <label for="cedula">Número de cédula</label>
                    <input id="cedula" type="text" name="cedula" value="" placeholder="Ej. 1.075.123.456" inputmode="numeric" autocomplete="off" maxlength="19" required class="{{ $errors->has('cedula') ? 'is-invalid' : '' }}">
                    @error('cedula')
                        <div class="guest-error">{{ $message }}</div>
                    @enderror
                    <p class="consulta-hint">Puedes escribirla con o sin puntos. El dato no se conserva ni se utiliza para mostrar resultados.</p>
                </div>
                <div class="consulta-actions">
                    <button type="submit" class="btn-submit consulta-submit" id="consulta-submit">Consultar</button>
                </div>
            </form>
        </div>

        @if(!empty($consultaIntentada))
            <div class="consulta-card consulta-empty" role="status">
                <strong>Por seguridad, no podemos confirmar si existe un expediente con esos datos.</strong>
                <p>Para solicitar información, utiliza los canales institucionales de atención.</p>
            </div>
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
