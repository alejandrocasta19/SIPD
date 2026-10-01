@extends('layouts.guest')

@section('title', 'Confirmar contraseña · SIPD Cootranshuila')

@section('content')
    <h2>Confirmar contraseña</h2>
    <p class="guest-lead">Vuelve a escribir tu contraseña para continuar.</p>

    @if($errors->any())
        <div class="sipd-alert sipd-alert-error" role="alert">
            <div>
                <strong>No se pudo confirmar</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <div class="guest-field">
            <label for="password">Contraseña <span class="req">*</span></label>
            <div class="password-wrap">
                <input id="password" type="password" name="password" required autocomplete="current-password" class="{{ $errors->has('password') ? 'is-invalid' : '' }}">
                <button type="button" class="toggle-pass" id="toggle-pass" aria-label="Mostrar contraseña">Ver</button>
            </div>
        </div>
        <button type="submit" class="btn-submit">Confirmar</button>
    </form>

    <a class="guest-back" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
@endsection

@section('scripts')
<script>
    (function () {
        var toggle = document.getElementById('toggle-pass');
        var password = document.getElementById('password');
        if (!toggle || !password) return;
        toggle.addEventListener('click', function () {
            var visible = password.type === 'text';
            password.type = visible ? 'password' : 'text';
            toggle.textContent = visible ? 'Ver' : 'Ocultar';
        });
    })();
</script>
@endsection
