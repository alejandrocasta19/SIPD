@extends('layouts.master')

@php
    $pageTitle = 'Gestión de RH';
    $inicialesDe = function ($nombre) {
        return collect(preg_split('/\s+/', trim($nombre)))
            ->filter()
            ->take(2)
            ->map(function ($part) { return strtoupper(substr($part, 0, 1)); })
            ->implode('');
    };
@endphp

@section('page-header')
    <div class="abg-head">
        <div>
            <h1>Gestión de RH</h1>
            <p>Administra el catálogo de personal de RH del sistema.</p>
        </div>
        <button type="button" class="btn-add" id="btnAgregar">
            <i class="fas fa-plus"></i> Agregar RH
        </button>
    </div>
@endsection

@section('styles')
<style>
    .abg-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 20px;
    }

    .abg-head h1 {
        margin: 0 0 4px;
        font-size: 28px;
        font-weight: 700;
        letter-spacing: -.03em;
    }

    .abg-head p {
        margin: 0;
        color: #94a3b8;
        font-size: 14px;
    }

    .btn-add {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #22c55e;
        color: #fff;
        border: 0;
        border-radius: 999px;
        padding: 11px 18px;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        white-space: nowrap;
    }

    .btn-add:hover { filter: brightness(1.05); }

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
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0 12px;
        font: inherit;
        font-size: 14px;
        outline: none;
    }

    .form-grid input:focus { border-color: #22c55e; }

    .btn-save {
        height: 42px;
        border: 0;
        border-radius: 10px;
        background: #16a34a;
        color: #fff;
        font-weight: 700;
        padding: 0 18px;
        cursor: pointer;
    }

    .table-card {
        background: #fff;
        border-radius: 18px;
        overflow: hidden;
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
        border-bottom: 1px solid #f1f5f9;
    }

    table.abg td {
        padding: 16px 18px;
        border-bottom: 1px solid #f8fafc;
        color: #334155;
        font-size: 14px;
        vertical-align: middle;
    }

    table.abg tr:last-child td { border-bottom: 0; }

    .who {
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
        color: #0f172a;
    }

    .ava {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #ecfdf5;
        color: #166534;
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
        color: #166534;
    }

    .st i { font-size: 8px; color: #22c55e; }

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

    .acts button:hover { background: #f1f5f9; color: #0f172a; }
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
        border: 1px solid #e2e8f0;
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
    .btn-ok { background: #16a34a; color: #fff; }

    @media (max-width: 900px) {
        .abg-head { flex-direction: column; }
        .form-grid { grid-template-columns: 1fr; }
        .table-card { overflow-x: auto; }
    }
</style>
@endsection

@section('content')
@if(session('success'))
<script>
    Swal.fire({
        icon: 'success',
        title: 'Éxito',
        text: '{{ session('success') }}',
        confirmButtonColor: '#16a34a'
    });
</script>
@endif

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
                    <th>ESTADO</th>
                    <th>FECHA REGISTRO</th>
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
                        <td>{{ $abogado->cargo ?: '—' }}</td>
                        <td>
                            <span class="st"><i class="fas fa-circle"></i> Activo</span>
                        </td>
                        <td>{{ optional($abogado->created_at)->format('Y-m-d') }}</td>
                        <td>
                            <div class="acts">
                                <button type="button" title="Editar"
                                    onclick="abrirModalEditar('{{ $abogado->id }}', @json($abogado->name), @json($abogado->email), @json($abogado->cargo))">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <form action="{{ route('coordinadora.abogados.eliminar', $abogado->id) }}" method="POST"
                                      onsubmit="return confirm('¿Deseas eliminar este registro de RH?')">
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

    <div class="modal-bg" id="modalEditar">
        <div class="modal">
            <h3>Editar RH</h3>
            <form id="formEditar" method="POST">
                @csrf
                @method('PUT')
                <label>Nombre</label>
                <input type="text" name="name" id="editName" required>
                <label>Correo</label>
                <input type="email" name="email" id="editEmail" required>
                <label>Cargo</label>
                <input type="text" name="cargo" id="editCargo" required>
                <div class="modal-actions">
                    <button type="button" class="btn-ghost" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn-ok">Guardar</button>
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

    function abrirModalEditar(id, nombre, email, cargo) {
        document.getElementById('modalEditar').style.display = 'flex';
        document.getElementById('editName').value = nombre;
        document.getElementById('editEmail').value = email;
        document.getElementById('editCargo').value = cargo;
        document.getElementById('formEditar').action = '/coordinadora/abogados/editar/' + id;
    }

    function cerrarModal() {
        document.getElementById('modalEditar').style.display = 'none';
    }
</script>
@endsection
