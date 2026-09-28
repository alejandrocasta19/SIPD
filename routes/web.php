<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ProcesoDisciplinarioController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ConsultaPublicaController;
use App\Http\Controllers\DocumentoController;

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

Route::middleware(['auth', 'role:admin,coordinadora,abogado'])->group(function () {

    Route::get('/abogado', [ProcesoDisciplinarioController::class, 'dashboard'])
        ->name('abogado.dashboard');

});

/*
|--------------------------------------------------------------------------
| ESTADÍSTICAS
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,coordinadora,abogado'])->group(function () {

    Route::get('/abogado/estadistica', [ProcesoDisciplinarioController::class, 'reportes'])
        ->name('abogado.estadistica');

    Route::get('/abogado/reportes', [ProcesoDisciplinarioController::class, 'reportes'])
        ->name('abogado.reportes');

    Route::get('/abogado/reportes/datos',
        [ProcesoDisciplinarioController::class, 'reportesData']
    )->name('abogado.reportes.datos');

    Route::get('/abogado/estadisticas/datos',
        [ProcesoDisciplinarioController::class, 'reportesData']
    )->name('abogado.estadisticas.datos');

    Route::match(['GET', 'POST'], '/abogado/reportes/global/{format}',
        [ProcesoDisciplinarioController::class, 'reportesGlobales']
    )->name('abogado.reportes.global');

});

/*
|--------------------------------------------------------------------------
| REGISTRO DE PROCESOS
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,coordinadora,abogado'])->group(function () {

    Route::get('/abogado/registro',
        [ProcesoDisciplinarioController::class, 'create']
    )->name('abogado.registro');

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

Route::middleware(['auth', 'role:admin,coordinadora,abogado'])->group(function () {

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

    Route::get('/abogado/partes',
        [ProcesoDisciplinarioController::class, 'partes']
    )->name('abogado.partes');

    Route::get('/abogado/resoluciones',
        [ProcesoDisciplinarioController::class, 'resoluciones']
    )->name('abogado.resoluciones');

});

/*
|--------------------------------------------------------------------------
| DETALLE PROCESO
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,coordinadora,abogado'])->group(function () {

    Route::get('/abogado/detalleproceso/{id}',
        [ProcesoDisciplinarioController::class, 'show']
    )->name('abogado.detalleproceso');

    Route::get('/abogado/detalleproceso/{id}/documento-falta',
        [ProcesoDisciplinarioController::class, 'downloadSourceDocument']
    )->name('abogado.documento-falta');

    Route::get('/abogado/detalleproceso/{id}/oficial/{template}',
        [ProcesoDisciplinarioController::class, 'downloadOfficial']
    )->name('abogado.documento.oficial');

    Route::put('/abogado/detalleproceso/{id}/oficial/{template}',
        [ProcesoDisciplinarioController::class, 'saveOfficialBlocks']
    )->name('abogado.documento.oficial.guardar');

});

/*
|--------------------------------------------------------------------------
| ACTUALIZAR PROCESO
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,coordinadora,abogado'])->group(function () {

    Route::put('/abogado/actualizarproceso/{id}',
        [ProcesoDisciplinarioController::class, 'update']
    )->name('abogado.actualizarproceso');

});
Route::middleware(['auth', 'role:admin,coordinadora'])->group(function () {
    Route::delete('/abogado/eliminarproceso/{id}',
        [ProcesoDisciplinarioController::class, 'destroy']
    )->name('abogado.eliminarproceso');
});

//ABOGADS
Route::middleware(['auth', 'role:admin,coordinadora'])->group(function () {

    Route::get('/coordinadora/abogados',
        [ProcesoDisciplinarioController::class, 'abogados']
    )->name('coordinadora.abogados');

});
Route::middleware(['auth', 'role:admin,coordinadora'])->group(function () {
    Route::delete('/coordinadora/abogados/{id}', [ProcesoDisciplinarioController::class, 'eliminarAbogado'])
        ->name('coordinadora.abogados.eliminar');

    Route::post('/coordinadora/abogados/guardar', [ProcesoDisciplinarioController::class, 'guardarAbogado'])
        ->name('coordinadora.abogados.guardar');
    Route::put('/coordinadora/abogados/editar/{id}',
        [ProcesoDisciplinarioController::class, 'editarAbogado'])
        ->name('coordinadora.abogados.editar');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/perfil', [PerfilController::class, 'show'])->name('perfil.show');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');
    Route::get('/perfil/contrasena', [PerfilController::class, 'password'])->name('perfil.password');
    Route::put('/perfil/contrasena', [PerfilController::class, 'updatePassword'])->name('perfil.password.update');
});

/*
|--------------------------------------------------------------------------
| MÓDULO DOCUMENTOS — Submódulos por tipo de documento oficial
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,coordinadora,abogado'])->prefix('documentos')->name('documentos.')->group(function () {

    // Hub de documentos
    Route::get('/', [DocumentoController::class, 'hub'])
        ->name('hub');

    // Índice: documentos del caso
    Route::get('/{id}', [DocumentoController::class, 'index'])
        ->name('index');

    // Formulario de diligenciamiento (split-screen)
    Route::get('/{id}/{tipo}/editar', [DocumentoController::class, 'edit'])
        ->name('edit');

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