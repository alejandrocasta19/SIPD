@extends('layouts.master')

@section('page-header')
    <div class="pf-head">
        <h1>Mi perfil</h1>
        <p>Actualiza tus datos de acceso y contacto.</p>
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

    .pf-card input,
    .pf-card .readonly {
        width: 100%;
        height: 44px;
        border: 1px solid var(--cth-border);
        border-radius: 10px;
        padding: 0 12px;
        margin-bottom: 14px;
        font: inherit;
        background: #fff;
    }

    .pf-card .readonly {
        display: flex;
        align-items: center;
        background: #f8fafc;
        color: #64748b;
        text-transform: capitalize;
    }

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
        <form method="POST" action="{{ route('perfil.update') }}">
            @csrf
            @method('PUT')

            <label>Nombre</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
            @error('name') <div style="color:#dc2626;margin-top:-10px;margin-bottom:12px;font-size:13px;">{{ $message }}</div> @enderror

            <label>Correo</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
            @error('email') <div style="color:#dc2626;margin-top:-10px;margin-bottom:12px;font-size:13px;">{{ $message }}</div> @enderror

            <label>Cargo</label>
            <input type="text" name="cargo" value="{{ old('cargo', $user->cargo) }}" placeholder="Cargo">

            <label>Rol</label>
            <div class="readonly">{{ $user->role }}</div>

            <button type="submit" class="btn-save">Guardar cambios</button>
        </form>
    </div>
@endsection
