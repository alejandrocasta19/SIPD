<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use App\Models\ProcesoDisciplinario;
use App\Models\CasoEvidencia;
use App\Models\CasoDocumentoEstado;
use App\Services\OfficialDocumentService;
use Throwable;

class DocumentoController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // HELPERS DE ACCESO
    // ─────────────────────────────────────────────────────────────

    /**
     * Devuelve la query base de casos visibles para el usuario autenticado.
     */
    private function visibleProcesses()
    {
        $query = ProcesoDisciplinario::query();
        if (!in_array(auth()->user()->role, ['admin', 'coordinadora'], true)) {
            $query->where('user_id', auth()->id());
        }
        return $query;
    }

    /**
     * Obtiene el caso o aborta 403/404 si no es accesible.
     */
    private function accessibleCase($id): ProcesoDisciplinario
    {
        return $this->visibleProcesses()->with(['evidencias.user', 'documentoEstados'])->findOrFail($id);
    }

    /**
     * Valida que $tipo sea uno de los tres tipos de documento.
     */
    private function assertValidTipo(string $tipo): void
    {
        abort_unless(in_array($tipo, CasoDocumentoEstado::TIPOS, true), 404);
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
        $query = $this->visibleProcesses()->with(['documentoEstados', 'user']);
        
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

        $casos = $query->latest()->paginate(15);
        $casos->withQueryString();

        return view('documents.hub', [
            'pageTitle' => 'Docs. Oficiales',
            'casos' => $casos,
        ]);
    }

    /**
     * GET /documentos/{id}
     * Muestra los tres subdocumentos del caso con su estado.
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
    public function edit(int $id, string $tipo, OfficialDocumentService $documents)
    {
        $this->assertValidTipo($tipo);
        $caso   = $this->accessibleCase($id);
        $estadoDoc = $caso->estadoDocumento($tipo);

        $definitions  = $documents->blockDefinitions($tipo);
        $sections     = $documents->fieldSections($tipo);
        $bloques      = $caso->datos_oficiales['yellow_blocks'][$tipo] ?? [];
        $defaultTexts = $documents->defaultTexts($tipo);

        return view('documents.edit', [
            'pageTitle'    => 'Diligenciar · ' . $this->labelTipo($tipo),
            'caso'         => $caso,
            'tipo'         => $tipo,
            'labelTipo'    => $this->labelTipo($tipo),
            'estadoDoc'    => $estadoDoc,
            'definitions'  => $definitions,
            'sections'     => $sections,
            'bloques'      => $bloques,
            'defaultTexts' => $defaultTexts,
            'interactiveHtml' => $documents->getInteractiveDocumentHtml($tipo, $bloques),
            'profileUser'  => auth()->user(),
        ]);
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
        $caso        = $this->accessibleCase($id);
        $definitions = $documents->blockDefinitions($tipo);

        $validated = $request->validate([
            'yellow_blocks'   => 'required|array|size:' . count($definitions),
            'yellow_blocks.*' => 'nullable|string|max:30000',
        ]);

        // Persistir bloques
        $data = $caso->datos_oficiales ?: [];
        $data['yellow_blocks'][$tipo] = array_values($validated['yellow_blocks']);
        $caso->update(['datos_oficiales' => $data]);

        // Determinar si todos los bloques tienen valor
        $allFilled = collect($validated['yellow_blocks'])->every(fn($v) => trim((string)$v) !== '');
        $nuevoEstado = $allFilled ? 'completo' : 'en_diligenciamiento';

        // Actualizar estado del subdocumento
        $estadoDoc = $caso->estadoDocumento($tipo);
        if ($estadoDoc->estado !== 'generado') {
            $estadoDoc->update(['estado' => $nuevoEstado]);
        }

        return redirect()
            ->route('documentos.edit', [$id, $tipo])
            ->with('success', 'Información guardada correctamente.');
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

        try {
            $filename = match ($tipo) {
                'disciplinario' => 'apertura-disciplinario',
                'comprobacion'  => 'apertura-comprobacion',
                'acta'          => 'acta-cargos-descargos',
            };

            if (request('format') === 'pdf') {
                $response = $documents->downloadOfficialPdf($caso, $tipo, $filename);
            } else {
                $response = $documents->downloadOfficial($caso, $tipo, $filename);
            }

            // Actualizar estado
            $estadoDoc = $caso->estadoDocumento($tipo);
            $estadoDoc->update([
                'estado'      => 'generado',
                'generado_en' => now(),
            ]);

            return $response;
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->with('error', 'Completa todos los bloques obligatorios antes de generar el documento.');
        } catch (Throwable $e) {
            report($e);
            return back()->with('error', 'No fue posible generar el documento institucional.');
        }
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
        $caso      = $this->accessibleCase($id);
        $evidencia = $caso->evidencias()->findOrFail($evidenciaId);

        $canDelete = in_array(auth()->user()->role, ['admin', 'coordinadora'], true)
            || $evidencia->user_id === auth()->id();

        abort_unless($canDelete, 403, 'No tienes permiso para eliminar esta evidencia.');

        Storage::disk('local')->delete($evidencia->ruta_segura);
        $evidencia->delete();

        return redirect()
            ->route('documentos.index', $id)
            ->with('success', 'Evidencia eliminada.');
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS PRIVADOS
    // ─────────────────────────────────────────────────────────────

    private function labelTipo(string $tipo): string
    {
        return match ($tipo) {
            'disciplinario' => 'Apertura Proceso Disciplinario',
            'comprobacion'  => 'Apertura Proceso de Comprobación',
            'acta'          => 'Acta de Cargos y Descargos',
            default         => ucfirst($tipo),
        };
    }
}
