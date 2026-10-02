<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ProcesoDisciplinarioController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ConsultaPublicaController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\AnexoController;
use App\Http\Controllers\AvisoController;
use App\Http\Controllers\CoordinadoraController;
use App\Http\Controllers\Auth\SesionInactividadController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect('/login');
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Auth::routes(['register' => false]);

Route::get('/consultar-caso', [ConsultaPublicaController::class, 'create'])
    ->name('consulta.publica');
Route::post('/consultar-caso', [ConsultaPublicaController::class, 'buscar'])
    ->name('consulta.publica.buscar');

/*
|--------------------------------------------------------------------------
| PANEL PRINCIPAL
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,equipo'])->group(function () {

    Route::get('/abogado', [ProcesoDisciplinarioController::class, 'dashboard'])
        ->name('abogado.dashboard');

    Route::post('/sesion/actividad', [SesionInactividadController::class, 'actividad'])
        ->name('sesion.actividad');

    Route::post('/sesion/expirar', [SesionInactividadController::class, 'expirar'])
        ->name('sesion.expirar');

});

/*
|--------------------------------------------------------------------------
| ESTADÍSTICAS
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,equipo'])->group(function () {

    Route::get('/abogado/reportes', [ProcesoDisciplinarioController::class, 'reportes'])
        ->name('abogado.reportes');

    Route::get('/abogado/estadistica', function () {
        return redirect()->route('abogado.reportes', request()->query());
    })->name('abogado.estadistica');

    Route::get('/abogado/reportes/datos',
        [ProcesoDisciplinarioController::class, 'reportesData']
    )->name('abogado.reportes.datos');

    Route::get('/abogado/estadisticas/datos', function () {
        return redirect()->route('abogado.reportes.datos', request()->query());
    })->name('abogado.estadisticas.datos');

    Route::match(['GET', 'POST'], '/abogado/reportes/global/{format}',
        [ProcesoDisciplinarioController::class, 'reportesGlobales']
    )->name('abogado.reportes.global');

});

/*
|--------------------------------------------------------------------------
| REGISTRO DE PROCESOS
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,equipo'])->group(function () {

    Route::get('/abogado/registro',
        [ProcesoDisciplinarioController::class, 'create']
    )->name('abogado.registro');

    Route::get('/abogado/registro/plantilla',
        [ProcesoDisciplinarioController::class, 'plantillaRegistro']
    )->name('abogado.registro.plantilla');

    Route::post('/abogado/registro',
        [ProcesoDisciplinarioController::class, 'store']
    )->name('abogado.registro.store');

    Route::get('/abogado/reincidencias',
        [ProcesoDisciplinarioController::class, 'reincidencias']
    )->name('abogado.reincidencias');

    Route::get('/abogado/trabajadores',
        [ProcesoDisciplinarioController::class, 'workerSearch']
    )->name('abogado.trabajadores');

});

/*
|--------------------------------------------------------------------------
| CONSULTAR PROCESOS
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,equipo'])->group(function () {

    Route::get('/abogado/consultarproceso',
        [ProcesoDisciplinarioController::class, 'index']
    )->name('abogado.consultarproceso');

    Route::get('/abogado/mis-casos',
        [ProcesoDisciplinarioController::class, 'misCasos']
    )->name('abogado.mis-casos');



    Route::post('/abogado/reportes/casos/{format}',
        [ProcesoDisciplinarioController::class, 'exportarCasos']
    )->name('abogado.reportes.casos.export');

    Route::get('/abogado/plazos',
        [ProcesoDisciplinarioController::class, 'plazos']
    )->name('abogado.plazos');

    Route::put('/abogado/plazos/{id}/descargos',
        [ProcesoDisciplinarioController::class, 'actualizarDescargosPresentacion']
    )->name('abogado.plazos.descargos');

    Route::get('/abogado/anexos',
        [AnexoController::class, 'index']
    )->name('abogado.anexos');

    Route::post('/abogado/anexos',
        [AnexoController::class, 'store']
    )->name('abogado.anexos.store');

    Route::get('/abogado/anexos/{id}/descargar',
        [AnexoController::class, 'download']
    )->name('abogado.anexos.download');

    Route::put('/abogado/anexos/{id}/firmar',
        [AnexoController::class, 'firmar']
    )->name('abogado.anexos.firmar');

    Route::put('/abogado/anexos/{id}',
        [AnexoController::class, 'update']
    )->name('abogado.anexos.update');

    Route::delete('/abogado/anexos/{id}',
        [AnexoController::class, 'destroy']
    )->name('abogado.anexos.destroy');

    Route::get('/abogado/partes', function () {
        return redirect()->route('abogado.anexos');
    })->name('abogado.partes');

    Route::get('/abogado/resoluciones',
        [ProcesoDisciplinarioController::class, 'resoluciones']
    )->name('abogado.resoluciones');

});

/*
|--------------------------------------------------------------------------
| DETALLE PROCESO
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,equipo'])->group(function () {

    Route::get('/abogado/detalleproceso/{id}',
        [ProcesoDisciplinarioController::class, 'show']
    )->name('abogado.detalleproceso');

    Route::get('/abogado/detalleproceso/{id}/documento-falta',
        [ProcesoDisciplinarioController::class, 'downloadSourceDocument']
    )->name('abogado.documento-falta');

});

/*
|--------------------------------------------------------------------------
| ACTUALIZAR PROCESO
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,equipo'])->group(function () {

    Route::put('/abogado/actualizarproceso/{id}',
        [ProcesoDisciplinarioController::class, 'update']
    )->name('abogado.actualizarproceso');

    Route::put('/abogado/actualizarestado/{id}',
        [ProcesoDisciplinarioController::class, 'updateStatus']
    )->name('abogado.actualizarestado');

    Route::put('/abogado/solicitar-veredicto/{id}',
        [ProcesoDisciplinarioController::class, 'solicitarVeredicto']
    )->name('abogado.solicitar_veredicto');

});
Route::middleware(['auth', 'role:admin,equipo'])->group(function () {
    Route::delete('/abogado/eliminarproceso/{id}',
        [ProcesoDisciplinarioController::class, 'destroy']
    )->name('abogado.eliminarproceso');
});

//ABOGADS
Route::middleware(['auth', 'role:admin'])->group(function () {

    Route::get('/coordinadora/abogados',
        [ProcesoDisciplinarioController::class, 'abogados']
    )->name('coordinadora.abogados');

});
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::delete('/coordinadora/abogados/{id}', [ProcesoDisciplinarioController::class, 'eliminarAbogado'])
        ->name('coordinadora.abogados.eliminar');

    Route::post('/coordinadora/abogados/guardar', [ProcesoDisciplinarioController::class, 'guardarAbogado'])
        ->name('coordinadora.abogados.guardar');
    Route::put('/coordinadora/abogados/editar/{id}',
        [ProcesoDisciplinarioController::class, 'editarAbogado'])
        ->name('coordinadora.abogados.editar');

    Route::put('/coordinadora/procesos/{id}/asignar',
        [ProcesoDisciplinarioController::class, 'asignarProceso'])
        ->name('coordinadora.asignar');

    Route::get('/coordinadora/veredictos', [CoordinadoraController::class, 'veredictos'])
        ->name('coordinadora.veredictos');
    Route::put('/coordinadora/abogados/{id}/permisos', [CoordinadoraController::class, 'guardarPermisos'])
        ->name('coordinadora.abogados.permisos');
    Route::get('/coordinadora/solicitudes', [CoordinadoraController::class, 'solicitudes'])
        ->name('coordinadora.solicitudes');
    Route::put('/coordinadora/solicitudes/{id}', [CoordinadoraController::class, 'responderSolicitud'])
        ->name('coordinadora.solicitudes.responder');
    Route::delete('/coordinadora/solicitudes/historial', [CoordinadoraController::class, 'vaciarHistorial'])
        ->name('coordinadora.solicitudes.historial');
    Route::delete('/coordinadora/solicitudes/{id}', [CoordinadoraController::class, 'eliminarSolicitud'])
        ->name('coordinadora.solicitudes.destroy');
    Route::get('/coordinadora/avisos', [CoordinadoraController::class, 'notificarForm'])
        ->name('coordinadora.notificar');
    Route::post('/coordinadora/avisos', [CoordinadoraController::class, 'notificarEquipo'])
        ->name('coordinadora.notificar.enviar');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/perfil', [PerfilController::class, 'show'])->name('perfil.show');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');
    Route::get('/perfil/contrasena', [PerfilController::class, 'password'])->name('perfil.password');
    Route::put('/perfil/contrasena', [PerfilController::class, 'updatePassword'])->name('perfil.password.update');
    Route::get('/notificaciones', [AvisoController::class, 'index'])->name('notificaciones.index');
    Route::delete('/notificaciones/leidas', [AvisoController::class, 'destroyLeidas'])->name('notificaciones.leidas');
    Route::delete('/notificaciones/alertas/{tipo}', [AvisoController::class, 'silenciarAlerta'])->name('notificaciones.alertas.silenciar');
    Route::delete('/notificaciones/alertas', [AvisoController::class, 'silenciarAlertas'])->name('notificaciones.alertas.silenciar-todas');
    Route::get('/notificaciones/{id}', [AvisoController::class, 'leer'])->name('notificaciones.leer');
    Route::delete('/notificaciones/{id}', [AvisoController::class, 'destroy'])->name('notificaciones.destroy');
    Route::post('/permisos/solicitar', [AvisoController::class, 'solicitar'])->name('permisos.solicitar');
});

/*
|--------------------------------------------------------------------------
| MÓDULO DOCUMENTOS — Submódulos por tipo de documento oficial
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,equipo'])->prefix('documentos')->name('documentos.')->group(function () {

    // Hub de documentos
    Route::get('/', [DocumentoController::class, 'hub'])
        ->name('hub');

    // Índice: documentos del caso
    Route::get('/{id}', [DocumentoController::class, 'index'])
        ->name('index');

    // Formulario de diligenciamiento (split-screen)
    Route::get('/{id}/{tipo}/editar', [DocumentoController::class, 'edit'])
        ->name('edit');

    Route::post('/{id}/variante', [DocumentoController::class, 'elegirVariante'])
        ->name('variante');

    // Guardar bloques amarillos
    Route::put('/{id}/{tipo}/guardar', [DocumentoController::class, 'save'])
        ->name('save');

    // Previsualización en iframe (JSON o PDF inline)
    Route::get('/{id}/{tipo}/previsualizar', [DocumentoController::class, 'preview'])
        ->name('preview');

    // Descargar DOCX oficial
    Route::get('/{id}/{tipo}/descargar', [DocumentoController::class, 'download'])
        ->name('download');

    // Cargar evidencia
    Route::post('/{id}/evidencias', [DocumentoController::class, 'storeEvidencia'])
        ->name('evidencias.store');

    // Descargar evidencia (segura)
    Route::get('/{id}/evidencias/{evidencia}/descargar', [DocumentoController::class, 'downloadEvidencia'])
        ->name('evidencias.download');

    // Eliminar evidencia
    Route::delete('/{id}/evidencias/{evidencia}', [DocumentoController::class, 'destroyEvidencia'])
        ->name('evidencias.destroy');
});
