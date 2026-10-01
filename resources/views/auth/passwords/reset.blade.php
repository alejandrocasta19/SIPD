@extends('layouts.guest')

@section('title', 'Restablecer contraseña · SIPD Cootranshuila')

@section('content')
    <h2>Restablecer contraseña</h2>
    <p class="guest-lead">Elige una contraseña nueva para tu cuenta institucional.</p>

    @if($errors->any())
        <div class="sipd-alert sipd-alert-error" role="alert">
            <div>
                <strong>No se pudo guardar la contraseña</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" id="reset-form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="guest-field">
            <label for="email">Correo electrónico <span class="req">*</span></label>
            <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}" autocomplete="username" required autofocus class="{{ $errors->has('email') ? 'is-invalid' : '' }}">
        </div>

        <div class="guest-field">
            <label for="password">Nueva contraseña <span class="req">*</span></label>
            <div class="password-wrap">
                <input id="password" type="password" name="password" autocomplete="new-password" required class="{{ $errors->has('password') ? 'is-invalid' : '' }}">
                <button type="button" class="toggle-pass" id="toggle-pass" aria-label="Mostrar contraseña">Ver</button>
            </div>
        </div>

        <div class="guest-field">
            <label for="password-confirm">Confirmar contraseña <span class="req">*</span></label>
            <input id="password-confirm" type="password" name="password_confirmation" autocomplete="new-password" required>
        </div>

        <button type="submit" class="btn-submit" id="reset-submit">Guardar contraseña</button>
    </form>

    <a class="guest-back" href="{{ route('login') }}">← Volver al acceso</a>
    <div class="guest-note">Acceso exclusivo para personal institucional de Cootranshuila.</div>
@endsection

@section('scripts')
<script>
    (function () {
        var toggle = document.getElementById('toggle-pass');
        var password = document.getElementById('password');
        var form = document.getElementById('reset-form');
        var submit = document.getElementById('reset-submit');
        if (toggle && password) {
            toggle.addEventListener('click', function () {
                var visible = password.type === 'text';
                password.type = visible ? 'password' : 'text';
                toggle.textContent = visible ? 'Ver' : 'Ocultar';
                toggle.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
            });
        }
        if (form && submit) {
            form.addEventListener('submit', function () {
                submit.disabled = true;
                submit.textContent = 'Guardando…';
            });
        }
    })();
</script>
@endsection
