<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use App\Models\ProcesoDisciplinario;
use App\Models\Aviso;
use App\Models\CasoEvidencia;
use App\Models\CasoDocumentoEstado;
use App\Support\Paginacion;
use App\Services\OfficialDocumentService;
use App\Services\FirmaDocumentoService;
use Throwable;

class DocumentoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permiso:ver_documentos')->only(['hub', 'index', 'edit', 'preview']);
        $this->middleware('permiso:editar_documentos')->only(['elegirVariante']);
        // download() maneja su propio control: primera descarga libre, las siguientes requieren permiso
        $this->middleware('permiso:subir_anexos')->only(['storeEvidencia']);
        $this->middleware('permiso:eliminar_anexos')->only(['destroyEvidencia']);
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS DE ACCESO
    // ─────────────────────────────────────────────────────────────

    /**
     * Devuelve la query base de casos visibles para el usuario autenticado.
     */
    private function visibleProcesses()
    {
        $query = ProcesoDisciplinario::query();
        if (!auth()->user()->esCoordinadora()) {
            $query->where('user_id', auth()->id());
        }
        return $query;
    }

    /**
     * Obtiene el caso o aborta 403/404 si no es accesible.
     */
    private function accessibleCase($id): ProcesoDisciplinario
    {
        return $this->visibleProcesses()->with(['evidencias.user', 'documentoEstados', 'anexos.user'])->findOrFail($id);
    }

    /**
     * Valida que $tipo sea uno de los documentos oficiales.
     */
    private function assertValidTipo(string $tipo): void
    {
        abort_unless(in_array($tipo, CasoDocumentoEstado::TIPOS, true), 404);
    }

    private function notificarFirmaPendiente(ProcesoDisciplinario $caso, string $tipo): void
    {
        if (!in_array($tipo, FirmaDocumentoService::TIPOS_FIRMA_COORDINADORA, true)
            || auth()->user()->esCoordinadora()) {
            return;
        }

        $codigo = 'PRO-' . str_pad((string) $caso->id, 3, '0', STR_PAD_LEFT);
        Aviso::aCoordinadoras([
            'remitente_id' => auth()->id(),
            'tipo' => Aviso::TIPO_FIRMA,
            'titulo' => 'Documento pendiente de firma: ' . CasoDocumentoEstado::etiqueta($tipo),
            'cuerpo' => $codigo . ' · ' . $caso->nombre . ' fue generado por ' . auth()->user()->name
                . ' y requiere la firma de la coordinadora.',
            'proceso_id' => $caso->id,
            'tipo_documento' => $tipo,
        ], auth()->id());
    }

    // ─────────────────────────────────────────────────────────────
    // ÍNDICE — DOCUMENTOS DEL CASO
    // ─────────────────────────────────────────────────────────────

    /**
     * GET /documentos
     * Central hub para gestionar todos los documentos oficiales agrupados por caso.
     */
    public function hub(Request $request)
    {
        $query = $this->visibleProcesses()->with(['documentoEstados', 'user'])->withCount('anexos');
        
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('nombre', 'like', '%' . $q . '%')
                    ->orWhere('cedula', 'like', '%' . $q . '%')
                    ->orWhere('placa', 'like', '%' . $q . '%');
                
                $id = preg_replace('/\D+/', '', $q);
                if ($id !== '') {
                    $sub->orWhere('id', $id);
                }
            });
        }

        $casos = Paginacion::deQuery($query->latest());

        return view('documents.hub', [
            'pageTitle' => 'Generar documentos',
            'casos' => $casos,
        ]);
    }

    /**
     * GET /documentos/{id}
     * Muestra los cuatro espacios documentales del caso con su estado.
     */
    public function index(int $id)
    {
        $caso = $this->accessibleCase($id);

        $estados = [];
        foreach (CasoDocumentoEstado::TIPOS as $tipo) {
            $estados[$tipo] = $caso->estadoDocumento($tipo);
        }

        return view('documents.index', [
            'pageTitle' => 'Documentos del Caso',
            'caso'      => $caso,
            'estados'   => $estados,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // EDICIÓN — FORMULARIO SPLIT-SCREEN POR SUBMÓDULO
    // ─────────────────────────────────────────────────────────────

    /**
     * GET /documentos/{id}/{tipo}/editar
     * Muestra el formulario de diligenciamiento de un subdocumento.
     */
    public function edit(
        int $id,
        string $tipo,
        OfficialDocumentService $documents,
        FirmaDocumentoService $firmas
    )
    {
        $this->assertValidTipo($tipo);
        $caso   = $this->accessibleCase($id);
        $bloqueado = $caso->soloLoManejaCoordinadora();
        $slot   = CasoDocumentoEstado::slotDe($tipo);
        if ($slot && !$bloqueado) {
            $caso->guardarVarianteSlot($slot, $tipo);
        }
        $estadoDoc = $caso->estadoDocumento($tipo);

        $definitions  = $documents->blockDefinitions($tipo);
        $sections     = $documents->fieldSections($tipo);
        $bloques      = $caso->datos_oficiales['yellow_blocks'][$tipo] ?? [];
        $defaultTexts = $documents->defaultTexts($tipo);

        $yaGenerado = $estadoDoc->estado === 'generado';

        return view('documents.edit', [
            'pageTitle'    => 'Diligenciar · ' . $this->labelTipo($tipo),
            'caso'         => $caso,
            'tipo'         => $tipo,
            'slot'         => $slot,
            'labelTipo'    => $this->labelTipo($tipo),
            'estadoDoc'    => $estadoDoc,
            'yaGenerado'   => $yaGenerado,
            'puedeEscribir' => $yaGenerado
                ? auth()->user()->puede('editar_generados')
                : auth()->user()->puede('editar_documentos'),
            'definitions'  => $definitions,
            'sections'     => $sections,
            'bloques'      => $bloques,
            'defaultTexts' => $defaultTexts,
            'interactiveHtml' => $documents->getInteractiveDocumentHtml($tipo, $bloques, $caso),
            'profileUser'  => auth()->user(),
            'requiereFirmaGerente' => $documents->requiresGerentePrint($tipo),
            'puedeSubirFirma' => $firmas->puedeCargarFirma($tipo, auth()->user())
                && auth()->user()->puede($yaGenerado ? 'editar_generados' : 'editar_documentos')
                && (!$bloqueado || auth()->user()->esCoordinadora()),
            'firmasGuardadas' => $firmas->rutasParaDocumento($tipo, $caso, auth()->user()),
        ]);
    }

    public function storeFirmas(Request $request, int $id, string $tipo, FirmaDocumentoService $firmas)
    {
        $this->assertValidTipo($tipo);
        $caso = $this->accessibleCase($id);
        $user = auth()->user();
        $bloqueado = $caso->soloLoManejaCoordinadora();

        abort_unless(!$bloqueado || $user->esCoordinadora(), 403);
        $estadoDoc = $caso->estadoDocumento($tipo);
        $user->exigir($estadoDoc->estado === 'generado' ? 'editar_generados' : 'editar_documentos');

        if (!$request->hasFile('firma_cuenta') && !$request->hasFile('firma_trabajador')) {
            throw ValidationException::withMessages([
                'firma_cuenta' => 'Selecciona al menos una firma para cargar.',
            ]);
        }

        $request->validate([
            'firma_cuenta' => 'sometimes|required|image|mimes:png,jpg,jpeg|max:2048',
            'firma_trabajador' => 'sometimes|required|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        if ($request->hasFile('firma_cuenta')) {
            abort_unless($firmas->puedeCargarFirma($tipo, $user), 403, 'No tienes permiso para cargar esta firma.');
            $archivo = $request->file('firma_cuenta');
            abort_unless(
                in_array($archivo->getMimeType(), ['image/png', 'image/jpeg'], true),
                422,
                'La firma debe ser una imagen PNG o JPEG válida.'
            );
            $firmas->guardarFirmaCuenta($archivo, $user);
        }

        if ($request->hasFile('firma_trabajador')) {
            abort_unless($tipo === 'acta', 422, 'La firma del trabajador solo se puede cargar para el acta.');
            $archivo = $request->file('firma_trabajador');
            abort_unless(
                in_array($archivo->getMimeType(), ['image/png', 'image/jpeg'], true),
                422,
                'La firma debe ser una imagen PNG o JPEG válida.'
            );
            $firmas->guardarFirmaTrabajador($archivo, $caso);
        }

        return back()->with('success', 'Firma(s) guardada(s) de forma privada para este expediente.');
    }

    // ─────────────────────────────────────────────────────────────
    // GUARDAR BLOQUES
    // ─────────────────────────────────────────────────────────────

    /**
     * PUT /documentos/{id}/{tipo}/guardar
     * Persiste los bloques amarillos y actualiza el estado del subdocumento.
     */
    public function save(Request $request, int $id, string $tipo, OfficialDocumentService $documents)
    {
        $this->assertValidTipo($tipo);
        $formato = $request->input('formato');
        $quiereGenerar = in_array($formato, ['docx', 'pdf', 'generar'], true);

        $caso = $this->accessibleCase($id);
        $redireccionBloqueado = $caso->redireccionSiBloqueado();
        if ($redireccionBloqueado) {
            return $redireccionBloqueado;
        }

        $slot = CasoDocumentoEstado::slotDe($tipo);
        if ($slot) {
            $caso->guardarVarianteSlot($slot, $tipo);
        }

        $estadoDoc = $caso->estadoDocumento($tipo);
        $yaGenerado = $estadoDoc->estado === 'generado';
        if ($yaGenerado) {
            auth()->user()->exigir('editar_generados');
            if ($quiereGenerar) {
                auth()->user()->exigir('generar_documentos');
            }
        } else {
            auth()->user()->exigir($quiereGenerar ? 'generar_documentos' : 'editar_documentos');
        }

        $definitions = $documents->blockDefinitions($tipo);

        $bloques = $this->bloquesDesdeRequest($request, $tipo, count($definitions));

        $data = $caso->datos_oficiales ?: [];
        $data['yellow_blocks'][$tipo] = $bloques;
        if ($tipo === 'sancion') {
            $data['optional_clauses'][$tipo]['descargos_fuera_de_termino'] = OfficialDocumentService::normalizeClauseMode(
                $request->input('optional_clauses.sancion.descargos_fuera_de_termino')
            );
        }
        $caso->update(['datos_oficiales' => $data]);

        $allFilled = collect($bloques)->every(fn ($v) => trim((string) $v) !== '');
        $nuevoEstado = $allFilled ? 'completo' : 'en_diligenciamiento';

        if ($quiereGenerar) {
            $estadoDoc->update([
                'estado' => 'generado',
                'generado_en' => $estadoDoc->generado_en ?? now(),
            ]);

            if (!$yaGenerado) {
                $this->notificarFirmaPendiente($caso, $tipo);
            }

            $mensaje = $tipo === 'terminacion'
                ? 'Documento generado. Descárgalo en Generar documentos, imprímelo para firma del gerente y sube el escaneo en Anexos.'
                : 'Documento generado. Descárgalo en Generar documentos.';

            return redirect()
                ->route('documentos.hub')
                ->with('success', $mensaje);
        }

        if ($estadoDoc->estado !== 'generado') {
            $estadoDoc->update(['estado' => $nuevoEstado]);
        }

        $redirect = redirect()->route('documentos.edit', [$id, $tipo]);

        if ($yaGenerado) {
            return $redirect->with('success', 'Edición guardada.');
        }

        if ($tipo === 'terminacion' || $allFilled) {
            return $redirect->with('success', 'Borrador guardado. Genera y descárgalo en Generar documentos.');
        }

        return $redirect->with('success', 'Borrador guardado. Los campos amarillos vacíos no se incluyen al generar.');
    }

    // ─────────────────────────────────────────────────────────────
    // PREVISUALIZACIÓN
    // ─────────────────────────────────────────────────────────────

    /**
     * GET /documentos/{id}/{tipo}/previsualizar
     * Genera un PDF temporal y lo devuelve para incrustar en iframe.
     */
    public function preview(int $id, string $tipo, OfficialDocumentService $documents)
    {
        $this->assertValidTipo($tipo);
        $caso = $this->accessibleCase($id);

        try {
            return $documents->previewPdf($caso, $tipo);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Existen campos sin completar. Guarda el formulario primero.',
            ], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'error' => 'No fue posible generar la previsualización.',
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // GENERACIÓN OFICIAL (DESCARGA)
    // ─────────────────────────────────────────────────────────────

    /**
     * GET /documentos/{id}/{tipo}/descargar
     * Genera y descarga el DOCX oficial.
     */
    public function download(int $id, string $tipo, OfficialDocumentService $documents)
    {
        $this->assertValidTipo($tipo);
        $caso = $this->accessibleCase($id);

        // Verificar acceso: la primera descarga de un documento es libre.
        // Si ya fue descargado antes, se necesita el permiso descargar_documentos.
        $estadoDocPrev = $caso->estadoDocumento($tipo);
        $yaGenerado = $estadoDocPrev->estado === 'generado';
        $esPrimeraDescarga = $estadoDocPrev->descargado_en === null;
        if (!$esPrimeraDescarga) {
            auth()->user()->exigir('descargar_documentos');
        }

        try {
            $filename = match ($tipo) {
                'disciplinario' => 'apertura-disciplinario',
                'comprobacion'  => 'apertura-comprobacion',
                'acta'          => 'acta-cargos-descargos',
                'sancion'       => 'sancion',
                'llamado'       => 'llamado-de-atencion',
                'terminacion'   => 'terminacion-por-justas-causas',
                'archivo'       => 'decision-de-archivo',
                default         => $tipo,
            };

            if (request('format') === 'pdf') {
                $response = $documents->downloadOfficialPdf($caso, $tipo, $filename);
            } else {
                $response = $documents->downloadOfficial($caso, $tipo, $filename);
            }

        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->with('error', 'Completa todos los bloques obligatorios antes de generar el documento.');
        } catch (Throwable $e) {
            report($e);
            return back()->with('error', 'No fue posible generar el documento institucional.');
        }

        $estadoDoc = $caso->estadoDocumento($tipo);
        $payload = ['descargado_en' => now()];
        if (!$yaGenerado) {
            $payload['estado'] = 'generado';
            $payload['generado_en'] = $estadoDoc->generado_en ?? now();
        }
        $estadoDoc->update($payload);

        if (!$yaGenerado) {
            $this->notificarFirmaPendiente($caso, $tipo);
        }

        return $response;
    }

    // ─────────────────────────────────────────────────────────────
    // EVIDENCIAS
    // ─────────────────────────────────────────────────────────────

    /**
     * POST /documentos/{id}/evidencias
     * Carga una evidencia al caso.
     */
    public function storeEvidencia(Request $request, int $id)
    {
        $caso = $this->accessibleCase($id);

        $validated = $request->validate([
            'archivo'     => [
                'required',
                'file',
                'max:10240',                         // 10 MB
                'mimes:pdf,jpg,jpeg,png',             // frontend filter
            ],
            'descripcion' => 'nullable|string|max:500',
        ]);

        $archivo    = $request->file('archivo');
        $extension  = strtolower($archivo->getClientOriginalExtension());
        $mime       = $archivo->getMimeType();

        // Validación adicional real en servidor (no confiar solo en mimes:)
        abort_unless(
            in_array($extension, CasoEvidencia::ALLOWED_EXTENSIONS, true) &&
            in_array($mime, CasoEvidencia::ALLOWED_MIMES, true),
            422,
            'Tipo de archivo no permitido. Solo se aceptan PDF, JPG, JPEG o PNG.'
        );

        $nombreAlmacenado = 'ev_' . $caso->id . '_' . uniqid() . '.' . $extension;
        $ruta = $archivo->storeAs('evidencias/' . $caso->id, $nombreAlmacenado, 'local');

        CasoEvidencia::create([
            'caso_id'           => $caso->id,
            'user_id'           => auth()->id(),
            'nombre_original'   => $archivo->getClientOriginalName(),
            'nombre_almacenado' => $nombreAlmacenado,
            'extension'         => $extension,
            'mime_type'         => $mime,
            'tamano'            => $archivo->getSize(),
            'descripcion'       => $validated['descripcion'] ?? null,
            'ruta_segura'       => $ruta,
        ]);

        return redirect()
            ->route('documentos.index', $id)
            ->with('success', 'Evidencia cargada correctamente.');
    }

    /**
     * GET /documentos/{id}/evidencias/{evidencia}/descargar
     * Descarga segura de una evidencia.
     */
    public function downloadEvidencia(int $id, int $evidenciaId)
    {
        $caso      = $this->accessibleCase($id);
        $evidencia = $caso->evidencias()->findOrFail($evidenciaId);

        $ruta = storage_path('app/' . $evidencia->ruta_segura);
        abort_unless(file_exists($ruta), 404);

        return response()->download($ruta, $evidencia->nombre_original);
    }

    /**
     * DELETE /documentos/{id}/evidencias/{evidencia}
     * Elimina una evidencia (solo admin/coordinadora o el usuario que la cargó).
     */
    public function destroyEvidencia(int $id, int $evidenciaId)
    {
        try {
            $caso      = $this->accessibleCase($id);
            $evidencia = $caso->evidencias()->findOrFail($evidenciaId);

            $canDelete = auth()->user()->esCoordinadora()
                || (auth()->user()->puede('eliminar_anexos') && $evidencia->user_id === auth()->id());

            abort_unless($canDelete, 403, 'No tienes permiso para eliminar esta evidencia.');

            Storage::disk('local')->delete($evidencia->ruta_segura);
            $evidencia->delete();

            return redirect()
                ->route('documentos.index', $id)
                ->with('success', 'Evidencia eliminada.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al eliminar la evidencia: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS PRIVADOS
    // ─────────────────────────────────────────────────────────────

    private function labelTipo(string $tipo): string
    {
        return CasoDocumentoEstado::etiqueta($tipo);
    }

    public function elegirVariante(Request $request, int $id)
    {
        $caso = $this->accessibleCase($id);
        $tipo = $request->validate([
            'tipo' => 'required|in:' . implode(',', CasoDocumentoEstado::TIPOS),
        ])['tipo'];

        $slot = CasoDocumentoEstado::slotDe($tipo);
        abort_unless($slot, 404);
        $caso->guardarVarianteSlot($slot, $tipo);

        return redirect()->route('documentos.edit', [$id, $tipo]);
    }

    private function bloquesDesdeRequest(Request $request, string $tipo, int $esperados): array
    {
        $bloques = $request->input('yellow_blocks');
        if (!is_array($bloques)) {
            $bloques = $request->input('yellow_blocks_' . $tipo, []);
        }

        $bloques = array_values((array) $bloques);
        while (count($bloques) < $esperados) {
            $bloques[] = '';
        }

        return array_slice($bloques, 0, $esperados);
    }
}
