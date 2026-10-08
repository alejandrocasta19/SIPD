@extends('layouts.guest')

@section('title', 'Recuperar contraseña · SIPD Cootranshuila')

@section('content')
    <h2>Recuperar contraseña</h2>
    <p class="guest-lead">Ingresa el correo de tu cuenta institucional. La coordinadora revisará la solicitud y, si la aprueba, recibirás un enlace para crear una contraseña nueva.</p>

    @if(session('status'))
        <div class="sipd-alert sipd-alert-success" role="status">
            Si el correo pertenece a una cuenta activa del equipo, la solicitud fue enviada a coordinación. Si se aprueba, llegará un enlace de un solo uso al correo registrado.
        </div>
    @endif

    @if($errors->any())
        <div class="sipd-alert sipd-alert-error" role="alert">
            <div>
                <strong>No se pudo enviar la solicitud</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" id="forgot-form">
        @csrf
        <div class="guest-field">
            <label for="email">Correo electrónico <span class="req">*</span></label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="usuario@cootranshuila.com" autocomplete="username" required autofocus class="{{ $errors->has('email') ? 'is-invalid' : '' }}">
        </div>
        <button type="submit" class="btn-submit" id="forgot-submit">Solicitar recuperación</button>
    </form>

    <a class="guest-back" href="{{ route('login') }}">← Volver al acceso</a>
    <div class="guest-note">Acceso exclusivo para personal institucional de Cootranshuila.</div>
@endsection

@section('scripts')
<script>
    (function () {
        var form = document.getElementById('forgot-form');
        var submit = document.getElementById('forgot-submit');
        if (!form || !submit) return;
        form.addEventListener('submit', function () {
            submit.disabled = true;
            submit.textContent = 'Enviando solicitud…';
        });
    })();
</script>
@endsection
