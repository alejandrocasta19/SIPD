<?php

namespace Tests\Feature;

use App\Models\CasoAnexo;
use App\Models\ProcesoDisciplinario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnexoTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'abogado'): User
    {
        return User::factory()->create(['role' => User::normalizarRol($role)]);
    }

    private function makeCaso(User $user, array $attributes = []): ProcesoDisciplinario
    {
        return ProcesoDisciplinario::factory()->create(array_merge([
            'user_id' => $user->id,
            'nombre' => 'Conductor Anexo',
            'estado' => 'Pendiente',
        ], $attributes));
    }

    private function generalUpdatePayload(ProcesoDisciplinario $caso): array
    {
        return [
            'nombre' => $caso->nombre,
            'cedula' => $caso->cedula,
            'telefono' => '3001234567',
            'modalidad' => '',
            'cargo' => '',
            'placa' => '',
            'numero_interno' => '',
            'ruta' => $caso->ruta,
            'tipo_falta' => $caso->tipo_falta,
            'fecha_falta' => $caso->fecha_falta->format('Y-m-d'),
            'descripcion_falta' => $caso->descripcion_falta,
            'observacion' => $caso->observacion,
            'decision_final' => $caso->decision_final,
        ];
    }

    private function pdf(string $name, int $kb = 40): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kb, 'application/pdf');
    }

    private function word(string $name = 'oficio.docx'): UploadedFile
    {
        return UploadedFile::fake()->create(
            $name,
            80,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );
    }

    private function makeDescargo(User $user, ProcesoDisciplinario $caso): CasoAnexo
    {
        $ruta = 'anexos/' . $caso->id . '/descargo.pdf';
        Storage::disk('local')->put($ruta, 'contenido de prueba');

        return CasoAnexo::create([
            'caso_id' => $caso->id,
            'user_id' => $user->id,
            'tipo' => CasoAnexo::TIPO_DESCARGOS,
            'estado' => CasoAnexo::ESTADO_CARGADO,
            'nombre_original' => 'descargo.pdf',
            'nombre_almacenado' => 'descargo.pdf',
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'tamano' => 19,
            'ruta_segura' => $ruta,
        ]);
    }

    /** @test */
    public function primera_presentacion_desde_plazos_no_exige_permiso_de_edicion()
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);
        $user->permisos()->where('permiso', 'editar_casos')->delete();

        $this->assertTrue($user->puede('ver_plazos'));
        $this->assertTrue($user->puede('registrar_casos'));
        $this->assertFalse($user->puede('editar_casos'));

        $this->actingAs($user)
            ->post(route('abogado.plazos.descargos', $caso->id), [
                'descargos_presentacion' => 'virtual',
            ])
            ->assertRedirect(route('abogado.plazos'));

        $this->assertSame('virtual', $caso->fresh()->descargos_forma);
        $this->assertDatabaseMissing('caso_anexos', ['caso_id' => $caso->id]);
    }

    /** @test */
    public function primera_carga_de_anexos_requiere_permiso_de_subida_sin_edicion_de_caso()
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);
        $user->permisos()->where('permiso', 'editar_casos')->delete();
        $this->assertTrue($user->puede('subir_anexos'));

        $this->actingAs($user)
            ->post(route('abogado.plazos.descargos', $caso->id), [
                'descargos_presentacion' => 'virtual',
                'archivos_descargos' => [$this->pdf('descargo-inicial.pdf')],
            ])
            ->assertRedirect(route('abogado.plazos'));

        $this->assertSame(1, CasoAnexo::where('caso_id', $caso->id)
            ->where('tipo', CasoAnexo::TIPO_DESCARGOS)
            ->count());
        Storage::disk('local')->assertExists(CasoAnexo::where('caso_id', $caso->id)
            ->where('tipo', CasoAnexo::TIPO_DESCARGOS)
            ->firstOrFail()->ruta_segura);
    }

    /** @test */
    public function ver_plazos_por_si_solo_no_autoriza_la_primera_presentacion()
    {
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);
        $user->permisos()->whereIn('permiso', ['editar_casos', 'registrar_casos'])->delete();

        $this->assertTrue($user->puede('ver_plazos'));
        $this->assertFalse($user->puede('registrar_casos'));

        $this->actingAs($user)
            ->post(route('abogado.plazos.descargos', $caso->id), [
                'descargos_presentacion' => 'virtual',
            ])
            ->assertForbidden();

        $this->assertNull($caso->fresh()->descargos_forma);
    }

    /** @test */
    public function la_primera_presentacion_no_autoriza_anexos_sin_permiso_de_subida()
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);
        $user->permisos()->whereIn('permiso', ['editar_casos', 'subir_anexos'])->delete();

        $this->actingAs($user)
            ->post(route('abogado.plazos.descargos', $caso->id), [
                'descargos_presentacion' => 'virtual',
                'archivos_descargos' => [$this->pdf('denegado-inicial.pdf')],
            ])
            ->assertForbidden();

        $this->assertNull($caso->fresh()->descargos_forma);
        $this->assertDatabaseMissing('caso_anexos', ['caso_id' => $caso->id]);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    /** @test */
    public function primera_presentacion_desde_el_formulario_general_no_exige_edicion_de_casos()
    {
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);
        $user->permisos()->where('permiso', 'editar_casos')->delete();

        $this->actingAs($user)
            ->put(route('abogado.actualizarproceso', $caso->id), [
                'descargos' => 'Primera presentación de prueba.',
                'descargos_presentacion' => 'virtual',
            ])
            ->assertRedirect();

        $this->assertSame('Primera presentación de prueba.', $caso->fresh()->descargos);
        $this->assertSame('virtual', $caso->fresh()->descargos_forma);
    }

    /** @test */
    public function una_primera_presentacion_vacia_no_modifica_el_expediente_ni_su_fecha()
    {
        foreach ([false, true] as $puedeEditar) {
            $user = $this->makeUser();
            if ($puedeEditar) {
                $user->otorgarPermiso('editar_casos', null);
            }
            $caso = $this->makeCaso($user);
            $caso->forceFill(['updated_at' => now()->subMinute()])->saveQuietly();
            $updatedAt = $caso->fresh()->updated_at->toDateTimeString();

            foreach ([null, '', '   '] as $valorVacio) {
                $this->actingAs($user)
                    ->put(route('abogado.actualizarproceso', $caso->id), [
                        'descargos' => $valorVacio,
                    ])
                    ->assertSessionHasErrors('descargos');

                $casoActualizado = $caso->fresh();
                $this->assertNull($casoActualizado->descargos);
                $this->assertNull($casoActualizado->descargos_forma);
                $this->assertSame($updatedAt, $casoActualizado->updated_at->toDateTimeString());
            }
        }
    }

    /** @test */
    public function una_primera_presentacion_con_contenido_valido_sigue_funcionando()
    {
        foreach ([false, true] as $puedeEditar) {
            $user = $this->makeUser();
            if (!$puedeEditar) {
                $user->permisos()->where('permiso', 'editar_casos')->delete();
            } else {
                $user->otorgarPermiso('editar_casos', null);
            }
            $caso = $this->makeCaso($user);
            $payload = ['descargos' => '  Presentación válida de prueba.  '];
            if ($puedeEditar) {
                $payload = array_merge($this->generalUpdatePayload($caso), $payload);
            }

            $this->actingAs($user)
                ->put(route('abogado.actualizarproceso', $caso->id), $payload)
                ->assertRedirect();

            $this->assertSame('Presentación válida de prueba.', $caso->fresh()->descargos);
        }
    }

    /** @test */
    public function una_modalidad_valida_permanece_permitida_sin_texto_de_descargos()
    {
        $user = $this->makeUser();
        $user->otorgarPermiso('editar_casos', null);
        $caso = $this->makeCaso($user);
        $payload = array_merge($this->generalUpdatePayload($caso), [
            'descargos_presentacion' => 'virtual',
        ]);

        $this->actingAs($user)
            ->put(route('abogado.actualizarproceso', $caso->id), $payload)
            ->assertRedirect();

        $this->assertSame('virtual', $caso->fresh()->descargos_forma);
        $this->assertNull($caso->fresh()->descargos);
    }

    /** @test */
    public function editar_casos_no_sustituye_registrar_casos_en_la_primera_presentacion()
    {
        $user = $this->makeUser();
        $user->otorgarPermiso('editar_casos', null);
        $user->permisos()->where('permiso', 'registrar_casos')->delete();
        $caso = $this->makeCaso($user);
        $payload = array_merge($this->generalUpdatePayload($caso), [
            'descargos' => 'Presentación sin permiso de registro.',
        ]);

        $this->actingAs($user)
            ->put(route('abogado.actualizarproceso', $caso->id), $payload)
            ->assertForbidden();

        $this->assertNull($caso->fresh()->descargos);
        $this->assertNull($caso->fresh()->descargos_forma);
    }

    /** @test */
    public function una_actualizacion_general_sin_intencion_no_inicia_ni_limpia_descargos()
    {
        $user = $this->makeUser();
        $user->otorgarPermiso('editar_casos', null);
        $caso = $this->makeCaso($user);
        $caso->update([
            'modalidad' => null,
            'cargo' => null,
            'placa' => null,
            'numero_interno' => null,
        ]);
        $caso->update(['descargos' => null]);
        $payload = array_merge($this->generalUpdatePayload($caso), [
            'descargos' => '',
            'descargos_presentacion' => 'no_presento',
            'observacion' => 'Actualización ajena a descargos.',
        ]);

        $this->actingAs($user)
            ->put(route('abogado.actualizarproceso', $caso->id), $payload)
            ->assertRedirect();

        $this->assertSame('Actualización ajena a descargos.', $caso->fresh()->observacion);
        $this->assertNull($caso->fresh()->descargos);
        $this->assertNull($caso->fresh()->descargos_forma);
    }

    /** @test */
    public function documento_falta_requiere_permiso_de_subida_al_registrar_un_caso()
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = $this->makeUser();
        $user->permisos()->where('permiso', 'subir_anexos')->delete();
        $this->assertTrue($user->puede('registrar_casos'));
        $this->assertFalse($user->puede('subir_anexos'));
        $casosAntes = ProcesoDisciplinario::count();

        $this->actingAs($user)
            ->post(route('abogado.registro.store'), [
                'documento_falta' => $this->pdf('documento-no-autorizado.pdf'),
            ])
            ->assertForbidden();

        $this->assertSame($casosAntes, ProcesoDisciplinario::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    /** @test */
    public function documento_falta_requiere_permiso_de_subida_al_actualizar_un_caso()
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = $this->makeUser();
        $user->otorgarPermiso('editar_casos', null);
        $user->permisos()->where('permiso', 'subir_anexos')->delete();
        $this->assertTrue($user->puede('editar_casos'));
        $this->assertFalse($user->puede('subir_anexos'));
        $caso = $this->makeCaso($user);
        $updatedAt = $caso->fresh()->updated_at->toDateTimeString();

        $this->actingAs($user)
            ->put(route('abogado.actualizarproceso', $caso->id), [
                'documento_falta' => [$this->pdf('documento-no-autorizado.pdf')],
            ])
            ->assertForbidden();

        $this->assertNull($caso->fresh()->documento_falta);
        $this->assertSame($updatedAt, $caso->fresh()->updated_at->toDateTimeString());
        $this->assertDatabaseMissing('caso_evidencias', ['caso_id' => $caso->id]);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    /** @test */
    public function documento_falta_nuevo_se_guarda_en_almacenamiento_no_publico()
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = $this->makeUser();
        $user->otorgarPermiso('editar_casos', null);
        $caso = $this->makeCaso($user);
        $payload = array_merge($this->generalUpdatePayload($caso), [
            'documento_falta' => [$this->pdf('documento-confidencial.pdf')],
        ]);

        $this->actingAs($user)
            ->put(route('abogado.actualizarproceso', $caso->id), $payload)
            ->assertRedirect();

        $ruta = $caso->fresh()->documento_falta;
        $this->assertStringStartsWith('documentos/', $ruta);
        Storage::disk('local')->assertExists($ruta);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    /** @test */
    public function configuracion_no_crea_un_enlace_publico_al_almacenamiento_de_archivos()
    {
        $this->assertArrayNotHasKey(
            public_path('storage'),
            config('filesystems.links')
        );
    }

    /** @test */
    public function el_formulario_general_no_permite_presentar_descargos_con_solo_ver_plazos()
    {
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);
        $user->permisos()->whereIn('permiso', ['editar_casos', 'registrar_casos'])->delete();

        $this->actingAs($user)
            ->put(route('abogado.actualizarproceso', $caso->id), [
                'descargos' => 'Presentación no autorizada.',
                'descargos_presentacion' => 'virtual',
            ])
            ->assertForbidden();

        $this->assertNull($caso->fresh()->descargos);
        $this->assertNull($caso->fresh()->descargos_forma);
    }

    /** @test */
    public function la_repeticion_de_la_presentacion_general_exige_permiso_y_no_cambia_datos()
    {
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);
        $user->permisos()->where('permiso', 'editar_casos')->delete();

        $this->actingAs($user)
            ->put(route('abogado.actualizarproceso', $caso->id), [
                'descargos' => 'Primera presentación de prueba.',
                'descargos_presentacion' => 'virtual',
            ])
            ->assertRedirect();

        $this->put(route('abogado.actualizarproceso', $caso->id), [
            'descargos' => 'Texto repetido no autorizado.',
            'descargos_presentacion' => 'presencial',
        ])->assertForbidden();

        $this->assertSame('Primera presentación de prueba.', $caso->fresh()->descargos);
        $this->assertSame('virtual', $caso->fresh()->descargos_forma);
    }

    /** @test */
    public function la_primera_presentacion_general_no_autoriza_cambios_a_otros_campos()
    {
        $user = $this->makeUser();
        $caso = $this->makeCaso($user, ['nombre' => 'Nombre inicial de prueba']);
        $user->permisos()->where('permiso', 'editar_casos')->delete();

        $this->actingAs($user)
            ->put(route('abogado.actualizarproceso', $caso->id), [
                'nombre' => 'Nombre alterado sin permiso',
                'descargos' => 'Presentación de prueba.',
                'descargos_presentacion' => 'virtual',
            ])
            ->assertForbidden();

        $this->assertSame('Nombre inicial de prueba', $caso->fresh()->nombre);
        $this->assertNull($caso->fresh()->descargos);
        $this->assertNull($caso->fresh()->descargos_forma);
    }

    /** @test */
    public function la_primera_interaccion_repetida_no_permite_eludir_permisos_ni_alterar_datos()
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);

        $this->actingAs($user)
            ->post(route('abogado.plazos.descargos', $caso->id), [
                'descargos_presentacion' => 'virtual',
            ])
            ->assertRedirect(route('abogado.plazos'));

        $user->permisos()->whereIn('permiso', ['editar_casos', 'subir_anexos'])->delete();

        $this->actingAs($user)
            ->post(route('abogado.plazos.descargos', $caso->id), [
                'descargos_presentacion' => 'presencial',
                'archivos_descargos' => [$this->pdf('repetido.pdf')],
            ])
            ->assertForbidden();

        $this->assertSame('virtual', $caso->fresh()->descargos_forma);
        $this->assertDatabaseMissing('caso_anexos', ['caso_id' => $caso->id]);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    /** @test */
    public function un_anexo_persistido_impide_tratar_la_peticion_como_primera_interaccion()
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);
        $anexoExistente = $this->makeDescargo($user, $caso);

        $this->actingAs($user)
            ->post(route('abogado.plazos.descargos', $caso->id), [
                'descargos_presentacion' => 'virtual',
            ])
            ->assertForbidden();

        $this->assertNull($caso->fresh()->descargos_forma);
        $this->assertSame(1, CasoAnexo::where('caso_id', $caso->id)
            ->where('tipo', CasoAnexo::TIPO_DESCARGOS)
            ->count());
        Storage::disk('local')->assertExists($anexoExistente->ruta_segura);
    }

    /** @test */
    public function descargos_persistidos_en_el_caso_impiden_eludir_permisos_de_edicion()
    {
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);
        $caso->update(['descargos' => 'Texto previo de prueba.']);

        $this->actingAs($user)
            ->post(route('abogado.plazos.descargos', $caso->id), [
                'descargos_presentacion' => 'virtual',
            ])
            ->assertForbidden();

        $this->assertSame('Texto previo de prueba.', $caso->fresh()->descargos);
        $this->assertNull($caso->fresh()->descargos_forma);
    }

    /** @test */
    public function segunda_interaccion_sin_permiso_de_edicion_se_deniega_sin_cambios_parciales()
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);

        $this->actingAs($user)
            ->post(route('abogado.plazos.descargos', $caso->id), [
                'descargos_presentacion' => 'virtual',
            ])
            ->assertRedirect(route('abogado.plazos'));

        $user->permisos()->where('permiso', 'editar_casos')->delete();
        $this->actingAs($user)
            ->post(route('abogado.plazos.descargos', $caso->id), [
                'descargos_presentacion' => 'presencial',
            ])
            ->assertForbidden();

        $this->assertSame('virtual', $caso->fresh()->descargos_forma);
        $this->assertDatabaseMissing('caso_anexos', ['caso_id' => $caso->id]);
    }

    /** @test */
    public function segunda_interaccion_autorizada_puede_editar_y_adjuntar_descargos()
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);

        $this->actingAs($user)
            ->post(route('abogado.plazos.descargos', $caso->id), [
                'descargos_presentacion' => 'virtual',
            ])
            ->assertRedirect(route('abogado.plazos'));

        $user->otorgarPermiso('editar_casos', null);
        $user->otorgarPermiso('subir_anexos', null);

        $this->actingAs($user)
            ->post(route('abogado.plazos.descargos', $caso->id), [
                'descargos_presentacion' => 'presencial',
                'archivos_descargos' => [$this->pdf('descargo-posterior.pdf')],
            ])
            ->assertRedirect(route('abogado.plazos'));

        $this->assertSame('presencial', $caso->fresh()->descargos_forma);
        $anexo = CasoAnexo::where('caso_id', $caso->id)
            ->where('tipo', CasoAnexo::TIPO_DESCARGOS)
            ->firstOrFail();
        Storage::disk('local')->assertExists($anexo->ruta_segura);
    }

    /** @test */
    public function segunda_interaccion_sin_permiso_de_subida_no_guarda_archivo_ni_cambia_descargos()
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);

        $this->actingAs($user)
            ->post(route('abogado.plazos.descargos', $caso->id), [
                'descargos_presentacion' => 'virtual',
            ])
            ->assertRedirect(route('abogado.plazos'));

        $user->otorgarPermiso('editar_casos', null);
        $user->permisos()->where('permiso', 'subir_anexos')->delete();

        $this->actingAs($user)
            ->post(route('abogado.plazos.descargos', $caso->id), [
                'descargos_presentacion' => 'presencial',
                'archivos_descargos' => [$this->pdf('denegado.pdf')],
            ])
            ->assertForbidden();

        $this->assertSame('virtual', $caso->fresh()->descargos_forma);
        $this->assertDatabaseMissing('caso_anexos', ['caso_id' => $caso->id]);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    /** @test */
    public function descargar_descargos_requiere_permiso_de_descarga_de_anexos()
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);
        $anexo = $this->makeDescargo($user, $caso);

        $this->assertFalse($user->puede('descargar_anexos'));

        $this->actingAs($user)
            ->get(route('abogado.plazos.descargos.download', $anexo->id))
            ->assertForbidden();
    }

    /** @test */
    public function descarga_autorizada_de_descargos_sigue_limitada_a_casos_propios()
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $user->otorgarPermiso('descargar_anexos', null);
        $otro = $this->makeUser();
        $casoPropio = $this->makeCaso($user);
        $casoAjeno = $this->makeCaso($otro);
        $anexoPropio = $this->makeDescargo($user, $casoPropio);
        $anexoAjeno = $this->makeDescargo($otro, $casoAjeno);

        $this->actingAs($user)
            ->get(route('abogado.plazos.descargos.download', $anexoPropio->id))
            ->assertOk();

        $this->get(route('abogado.plazos.descargos.download', $anexoAjeno->id))
            ->assertNotFound();
    }

    /** @test */
    public function documento_falta_no_se_descarga_solo_con_permiso_de_visualizacion()
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = $this->makeUser();
        $user->otorgarPermiso('ver_casos', null);
        $user->permisos()->where('permiso', 'descargar_anexos')->delete();
        $caso = $this->makeCaso($user);
        $ruta = 'documentos/documento-falta-sin-permiso.pdf';
        Storage::disk('local')->put($ruta, 'documento de prueba');
        $caso->update(['documento_falta' => $ruta]);

        $this->assertTrue($user->puede('ver_casos'));
        $this->assertFalse($user->puede('descargar_anexos'));

        $this->actingAs($user)
            ->get(route('abogado.documento-falta', $caso->id))
            ->assertForbidden();
    }

    /** @test */
    public function documento_falta_se_descarga_con_permiso_y_acceso_al_expediente()
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = $this->makeUser();
        $user->otorgarPermiso('ver_casos', null);
        $user->otorgarPermiso('descargar_anexos', null);
        $caso = $this->makeCaso($user);
        $ruta = 'documentos/documento-falta-autorizado.pdf';
        Storage::disk('local')->put($ruta, 'documento de prueba');
        $caso->update(['documento_falta' => $ruta]);

        $this->actingAs($user)
            ->get(route('abogado.documento-falta', $caso->id))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    /** @test */
    public function permiso_de_descarga_no_permite_obtener_documento_de_un_expediente_ajeno()
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = $this->makeUser();
        $user->otorgarPermiso('ver_casos', null);
        $user->otorgarPermiso('descargar_anexos', null);
        $otro = $this->makeUser();
        $casoAjeno = $this->makeCaso($otro);
        $ruta = 'documentos/documento-falta-ajeno.pdf';
        Storage::disk('local')->put($ruta, 'documento de prueba');
        $casoAjeno->update(['documento_falta' => $ruta]);

        $this->actingAs($user)
            ->get(route('abogado.documento-falta', $casoAjeno->id))
            ->assertNotFound();
    }

    /** @test */
    public function documento_falta_rechaza_rutas_persistidas_fuera_del_directorio_esperado()
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = $this->makeUser();
        $user->otorgarPermiso('descargar_anexos', null);
        $caso = $this->makeCaso($user);
        Storage::disk('local')->put('outside.pdf', 'contenido fuera del directorio');
        Storage::disk('local')->put('otra-carpeta/archivo.pdf', 'contenido fuera del directorio');

        foreach ([
            '../outside.pdf',
            Storage::disk('local')->path('outside.pdf'),
            'otra-carpeta/archivo.pdf',
        ] as $rutaInvalida) {
            $caso->update(['documento_falta' => $rutaInvalida]);

            $this->actingAs($user)
                ->get(route('abogado.documento-falta', $caso->id))
                ->assertNotFound();
        }
    }

    /** @test */
    public function documento_falta_valido_inexistente_devuelve_404_controlado()
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = $this->makeUser();
        $user->otorgarPermiso('descargar_anexos', null);
        $caso = $this->makeCaso($user, [
            'documento_falta' => 'documentos/no-existe.pdf',
        ]);

        $this->actingAs($user)
            ->get(route('abogado.documento-falta', $caso->id))
            ->assertNotFound();
    }

    /** @test */
    public function partes_redirige_a_anexos()
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get('/abogado/partes')
            ->assertRedirect(route('abogado.anexos'));
    }

    /** @test */
    public function abogado_ve_el_modulo_de_anexos()
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get(route('abogado.anexos'))
            ->assertOk()
            ->assertSee('Anexos escaneados')
            ->assertSee('5. Terminación por justas causas')
            ->assertSee('1.1 GA-FT-045')
            ->assertSee('2. Acta de cargos y descargos')
            ->assertSee('Decisión de archivo')
            ->assertDontSee('Hay que firmarlo')
            ->assertDontSee('Título (opcional)')
            ->assertDontSee('Partes involucradas');
    }

    /** @test */
    public function abogado_carga_un_formato_oficial_en_su_caso()
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);

        $this->actingAs($user)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'acta',
                'archivo' => $this->word('oficio.docx'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('caso_anexos', [
            'caso_id' => $caso->id,
            'tipo' => 'acta',
            'estado' => 'cargado',
            'titulo' => '2. Acta de cargos y descargos (grabación)',
        ]);
    }

    /** @test */
    public function anexo_aparece_en_detalle_y_documentos_del_caso()
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);

        $this->actingAs($user)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'acta',
                'archivo' => $this->word('oficio.docx'),
            ]);

        $this->actingAs($user)
            ->get(route('abogado.detalleproceso', $caso->id))
            ->assertOk()
            ->assertSee('Anexos del expediente')
            ->assertSee('oficio.docx')
            ->assertSee('2. Acta de cargos y descargos (grabación)');

        $this->actingAs($user)
            ->get(route('documentos.index', $caso->id))
            ->assertOk()
            ->assertSee('Anexos del expediente')
            ->assertSee('oficio.docx');

        $this->actingAs($user)
            ->get(route('documentos.edit', [$caso->id, 'disciplinario']))
            ->assertOk()
            ->assertSee('oficio.docx');

        $this->actingAs($user)
            ->get(route('abogado.consultarproceso'))
            ->assertOk()
            ->assertSee('1 anexo');

        $this->actingAs($user)
            ->get(route('documentos.hub'))
            ->assertOk()
            ->assertSee('1 anexo');
    }

    /** @test */
    public function terminacion_se_carga_ya_firmada()
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);

        $this->actingAs($user)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'terminacion',
                'archivo' => $this->word('terminacion.docx'),
            ])
            ->assertStatus(422);

        $this->actingAs($user)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'terminacion',
                'archivo' => $this->pdf('terminacion-firmada.pdf', 90),
            ])
            ->assertRedirect();

        $anexo = CasoAnexo::first();
        $this->assertSame('firmado', $anexo->estado);
        $this->assertSame('terminacion', $anexo->tipo);
        $this->assertSame('5. Terminación por justas causas', $anexo->titulo);
        $this->assertSame('terminacion-firmada.pdf', $anexo->nombre_original);
        $this->assertNull($anexo->ruta_firmada);
        $this->assertNotNull($anexo->firmado_at);
        Storage::disk('local')->assertExists($anexo->ruta_segura);

        $this->actingAs($user)
            ->get(route('abogado.detalleproceso', $caso->id))
            ->assertOk()
            ->assertSee('5. Terminación por justas causas')
            ->assertSee('terminacion-firmada.pdf');

        $user->otorgarPermiso('descargar_anexos', null);

        $this->actingAs($user)
            ->get(route('abogado.anexos', ['filtro' => 'resolucion']))
            ->assertOk()
            ->assertSee('Descargar')
            ->assertSee('Editar')
            ->assertSee('Eliminar')
            ->assertDontSee('Original')
            ->assertDontSee('Imprimir')
            ->assertDontSee('Cargar firmado');
    }

    /** @test */
    public function no_se_carga_firmado_sobre_un_archivo_previo()
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);

        $this->actingAs($user)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'acta',
                'archivo' => $this->pdf('oficio.pdf'),
            ]);

        $anexo = CasoAnexo::first();
        $this->actingAs($user)
            ->put(route('abogado.anexos.firmar', $anexo->id), [
                'archivo' => $this->pdf('firmado.pdf'),
            ])
            ->assertStatus(422);
    }

    /** @test */
    public function abogado_no_puede_cargar_anexo_en_caso_ajeno()
    {
        Storage::fake('local');
        $abogado = $this->makeUser();
        $otro = $this->makeUser();
        $caso = $this->makeCaso($otro);

        $this->actingAs($abogado)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'acta',
                'archivo' => $this->pdf('secreto.pdf'),
            ])
            ->assertStatus(404);
    }

    /** @test */
    public function rh_ve_editar_eliminar_y_descargar_y_pide_permiso_si_no_los_tiene()
    {
        Storage::fake('local');
        $rh = $this->makeUser();
        $coord = $this->makeUser('coordinadora');
        $caso = $this->makeCaso($rh);

        $this->actingAs($coord)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'acta',
                'archivo' => $this->word('acta-coord.docx'),
            ]);

        $this->actingAs($rh)
            ->get(route('abogado.anexos'))
            ->assertOk()
            ->assertSee('Editar')
            ->assertSee('Eliminar')
            ->assertSee('Descargar')
            ->assertSee("SIPD_abrirPermiso('editar_anexos')", false)
            ->assertSee("SIPD_abrirPermiso('eliminar_anexos')", false)
            ->assertSee("SIPD_abrirPermiso('descargar_anexos')", false)
            ->assertDontSee('<h3>Editar anexo</h3>', false);

        $rh->otorgarPermiso('editar_anexos', null);
        $rh->otorgarPermiso('eliminar_anexos', null);
        $rh->otorgarPermiso('descargar_anexos', null);

        $this->actingAs($rh)
            ->get(route('abogado.anexos'))
            ->assertOk()
            ->assertSee('anexo-editar')
            ->assertSee('Editar anexo')
            ->assertSee(route('abogado.anexos.download', CasoAnexo::first()->id), false)
            ->assertDontSee("SIPD_abrirPermiso('editar_anexos')", false);
    }

    /** @test */
    public function rh_sin_permiso_no_edita_ni_borra_anexo()
    {
        Storage::fake('local');
        $rh = $this->makeUser();
        $coord = $this->makeUser('coordinadora');
        $caso = $this->makeCaso($rh);

        $this->actingAs($coord)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'acta',
                'archivo' => $this->word('acta-coord.docx'),
            ]);

        $anexo = CasoAnexo::first();

        $this->actingAs($rh)
            ->put(route('abogado.anexos.update', $anexo->id), [
                'tipo' => 'sancion',
            ])
            ->assertRedirect(route('abogado.dashboard'));

        $this->actingAs($rh)
            ->delete(route('abogado.anexos.destroy', $anexo->id))
            ->assertRedirect(route('abogado.dashboard'));

        $this->assertDatabaseHas('caso_anexos', ['id' => $anexo->id, 'tipo' => 'acta']);
    }

    /** @test */
    public function rh_edita_tipo_y_archivo_de_anexo_de_su_caso()
    {
        Storage::fake('local');
        $rh = $this->makeUser();
        $coord = $this->makeUser('coordinadora');
        $caso = $this->makeCaso($rh);

        $this->actingAs($coord)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'acta',
                'archivo' => $this->word('acta-coord.docx'),
            ]);

        $anexo = CasoAnexo::first();
        $rutaVieja = $anexo->ruta_segura;
        $rh->otorgarPermiso('editar_anexos', null);

        $this->actingAs($rh)
            ->put(route('abogado.anexos.update', $anexo->id), [
                'tipo' => 'sancion',
                'archivo' => $this->pdf('sancion.pdf'),
            ])
            ->assertRedirect();

        $anexo->refresh();
        $this->assertSame('sancion', $anexo->tipo);
        $this->assertSame('sancion.pdf', $anexo->nombre_original);
        Storage::disk('local')->assertMissing($rutaVieja);
        Storage::disk('local')->assertExists($anexo->ruta_segura);
    }

    /** @test */
    public function rh_con_permiso_elimina_anexo_de_su_caso()
    {
        Storage::fake('local');
        $rh = $this->makeUser();
        $coord = $this->makeUser('coordinadora');
        $caso = $this->makeCaso($rh);

        $this->actingAs($coord)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'acta',
                'archivo' => $this->word('acta-coord.docx'),
            ]);

        $anexo = CasoAnexo::first();
        $rh->otorgarPermiso('eliminar_anexos', null);

        $this->actingAs($rh)
            ->delete(route('abogado.anexos.destroy', $anexo->id))
            ->assertRedirect(route('abogado.anexos'));

        $this->assertDatabaseMissing('caso_anexos', ['id' => $anexo->id]);
    }

    /** @test */
    public function reportes_incluyen_conteo_de_anexos()
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $caso = $this->makeCaso($user);

        $this->actingAs($user)
            ->post(route('abogado.anexos.store'), [
                'caso_id' => $caso->id,
                'tipo' => 'acta',
                'archivo' => $this->word('oficio.docx'),
            ]);

        $this->actingAs($user)
            ->get(route('abogado.reportes.datos'))
            ->assertOk()
            ->assertJsonPath('anexos.total', 1)
            ->assertJsonPath('anexos.archivo_previo', 1)
            ->assertJsonPath('anexos.pendiente_firma', 0)
            ->assertJsonPath('anexos.firmado', 0)
            ->assertJsonStructure(['by_modalidad', 'reincidencias', 'pending_faults', 'monthly', 'states']);

        $this->actingAs($user)
            ->get(route('abogado.reportes'))
            ->assertOk()
            ->assertSee('Archivo previo')
            ->assertSee('Firmados')
            ->assertSee('Anexos')
            ->assertSee('Modalidad y cargo')
            ->assertSee('Tipo de falta')
            ->assertSee('Ampliar vista')
            ->assertDontSee('Pendiente firma')
            ->assertDontSee('Por estado')
            ->assertDontSee('Faltas activas');

        $this->actingAs($user)
            ->get(route('abogado.estadistica'))
            ->assertRedirect(route('abogado.reportes'));
    }
}
