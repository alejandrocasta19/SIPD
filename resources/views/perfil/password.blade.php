@extends('layouts.master')

@section('page-header')
    <div class="pf-head">
        <h1>Cambiar contraseña</h1>
        <p>Ingresa tu contraseña actual y la nueva contraseña.</p>
    </div>
@endsection

@section('styles')
<style>
    .pf-head { margin-bottom: 20px; }
    .pf-head h1 { margin: 0 0 4px; font-size: var(--sipd-title); font-weight: 700; }
    .pf-head p { margin: 0; color: #94a3b8; }

    .pf-card {
        max-width: 560px;
        background: #fff;
        border-radius: 18px;
        padding: 24px;
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
    }

    .pf-card label {
        display: block;
        font-size: 13px;
        color: #64748b;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .pf-card input {
        width: 100%;
        height: 44px;
        border: 1px solid var(--cth-border);
        border-radius: 10px;
        padding: 0 12px;
        margin-bottom: 14px;
        font: inherit;
    }

    .error { color: #dc2626; margin-top: -10px; margin-bottom: 12px; font-size: 13px; }

    .btn-save {
        border: 0;
        border-radius: 999px;
        background: var(--cth-green-bright);
        color: #fff;
        font-weight: 700;
        padding: 11px 18px;
        cursor: pointer;
    }
</style>
@endsection

@section('content')

    <div class="pf-card">
        <form method="POST" action="{{ route('perfil.password.update') }}">
            @csrf
            @method('PUT')

            <label>Contraseña actual</label>
            <input type="password" name="current_password" required>
            @error('current_password') <div class="error">{{ $message }}</div> @enderror

            <label>Nueva contraseña</label>
            <input type="password" name="password" required>
            @error('password') <div class="error">{{ $message }}</div> @enderror

            <label>Confirmar nueva contraseña</label>
            <input type="password" name="password_confirmation" required>

            <button type="submit" class="btn-save">Actualizar contraseña</button>
        </form>
    </div>
@endsection
