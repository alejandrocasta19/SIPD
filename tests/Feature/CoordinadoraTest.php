<?php

namespace Tests\Feature;

use App\Models\PermisoSolicitud;
use App\Models\ProcesoDisciplinario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoordinadoraTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'abogado', array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role,
            'cargo' => $role === 'coordinadora' ? 'Coordinadora de RH' : 'Equipo de RH',
        ], $attrs));
    }

    /** @test */
    public function coordinadora_ve_panel_de_supervision()
    {
        $coord = $this->makeUser('coordinadora');
        $rh = $this->makeUser('abogado', ['name' => 'Kelly RH']);
        $this->makeCaso($rh, ['estado' => 'En Proceso', 'nombre' => 'Conductor Veredicto']);

        $this->actingAs($coord)
            ->get(route('abogado.dashboard'))
            ->assertOk()
            ->assertSee('Coordinación de RH')
            ->assertSee('Pendientes de veredicto')
            ->assertSee('Conductor Veredicto')
            ->assertSee('Carga del equipo')
            ->assertSee('Kelly RH')
            ->assertDontSee('Sin responsable de RH');
    }

    /** @test */
    public function equipo_rh_no_ve_panel_de_coordinacion()
    {
        $rh = $this->makeUser('abogado');

        $this->actingAs($rh)
            ->get(route('abogado.dashboard'))
            ->assertOk()
            ->assertDontSee('Coordinación de RH')
            ->assertDontSee('Pendientes de veredicto')
            ->assertSee('Registrar proceso');
    }

    /** @test */
    public function coordinadora_puede_asignar_un_proceso()
    {
        $coord = $this->makeUser('coordinadora');
        $origen = $this->makeUser('abogado', ['name' => 'Kelly RH']);
        $destino = $this->makeUser('abogado', ['name' => 'Analista RH']);
        $caso = $this->makeCaso($origen, ['nombre' => 'Caso de Kelly']);

        $this->actingAs($coord)
            ->put(route('coordinadora.asignar', $caso->id), [
                'user_id' => $destino->id,
            ])
            ->assertRedirect();

        $this->assertSame($destino->id, $caso->fresh()->user_id);
    }

    /** @test */
    public function equipo_rh_no_puede_asignar_procesos()
    {
        $rh = $this->makeUser('abogado');
        $otro = $this->makeUser('abogado');
        $caso = $this->makeCaso($rh);

        $this->actingAs($rh)
            ->put(route('coordinadora.asignar', $caso->id), [
                'user_id' => $otro->id,
            ])
            ->assertRedirect('/');
    }

    /** @test */
    public function rh_no_edita_procesos_ni_perfil_sin_permiso()
    {
        $rh = $this->makeUser('abogado');
        $caso = $this->makeCaso($rh);
        $original = $caso->descripcion_falta;

        $this->assertFalse($rh->fresh()->puede('editar_casos'));
        $this->assertFalse($rh->fresh()->puede('editar_perfil'));

        $this->actingAs($rh)
            ->put(route('abogado.actualizarproceso', $caso->id), [
                'nombre' => $caso->nombre,
                'descripcion_falta' => 'Cambiado',
            ])
            ->assertRedirect(route('abogado.dashboard'));

        $this->assertSame($original, $caso->fresh()->descripcion_falta);

        $this->actingAs($rh)
            ->put(route('perfil.update'), [
                'name' => 'Nombre Nuevo',
                'email' => $rh->email,
            ])
            ->assertSessionHas('error');

        $this->assertSame($rh->name, $rh->fresh()->name);

        $this->actingAs($rh)
            ->put(route('perfil.password.update'), [
                'current_password' => 'password',
                'password' => 'nueva123',
                'password_confirmation' => 'nueva123',
            ])
            ->assertSessionHas('error');

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password', $rh->fresh()->password));
    }

    /** @test */
    public function rh_con_permiso_de_perfil_si_cambia_contrasena()
    {
        $rh = $this->makeUser('abogado');
        $rh->otorgarPermiso('editar_perfil', 1);

        $this->actingAs($rh)
            ->put(route('perfil.password.update'), [
                'current_password' => 'password',
                'password' => 'nueva123',
                'password_confirmation' => 'nueva123',
            ])
            ->assertSessionHas('password_success');

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('nueva123', $rh->fresh()->password));
    }

    /** @test */
    public function rh_solicita_permiso_y_coordinadora_lo_otorga_por_horas()
    {
        $coord = $this->makeUser('coordinadora');
        $rh = $this->makeUser('abogado', ['name' => 'Kelly RH']);

        $this->actingAs($rh)
            ->post(route('permisos.solicitar'), [
                'permiso' => 'editar_casos',
                'que_hara' => 'Corregir los hechos del expediente PRO-001',
                'motivo' => 'Quedó mal la fecha de la falta',
                'duracion' => '3',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('permiso_solicitudes', [
            'user_id' => $rh->id,
            'permiso' => 'editar_casos',
            'estado' => 'pendiente',
            'horas' => 3,
        ]);
        $this->assertDatabaseHas('sipd_avisos', [
            'user_id' => $coord->id,
            'tipo' => 'solicitud',
        ]);

        $solicitud = \App\Models\PermisoSolicitud::first();
        $this->actingAs($coord)
            ->put(route('coordinadora.solicitudes.responder', $solicitud->id), [
                'accion' => 'otorgar',
                'permiso' => 'editar_casos',
                'duracion' => '1',
            ])
            ->assertRedirect();

        $this->assertTrue($rh->fresh()->puede('editar_casos'));
        $this->assertDatabaseHas('sipd_avisos', [
            'user_id' => $rh->id,
            'tipo' => 'permiso_respuesta',
        ]);
    }

    /** @test */
    public function coordinadora_elimina_solicitudes_del_historial()
    {
        $coord = $this->makeUser('coordinadora');
        $rh = $this->makeUser('abogado');
        $resuelta = PermisoSolicitud::create([
            'user_id' => $rh->id,
            'permiso' => 'editar_casos',
            'que_hara' => 'Corregir un dato',
            'motivo' => 'Quedó mal',
            'horas' => 1,
            'estado' => PermisoSolicitud::OTORGADA,
            'responded_by' => $coord->id,
            'responded_at' => now(),
        ]);
        $pendiente = PermisoSolicitud::create([
            'user_id' => $rh->id,
            'permiso' => 'editar_perfil',
            'que_hara' => 'Cambiar contraseña',
            'motivo' => 'La olvidó',
            'horas' => 1,
            'estado' => PermisoSolicitud::PENDIENTE,
        ]);

        $this->actingAs($coord)
            ->from(route('coordinadora.solicitudes'))
            ->delete(route('coordinadora.solicitudes.destroy', $resuelta->id))
            ->assertRedirect(route('coordinadora.solicitudes'));

        $this->assertDatabaseMissing('permiso_solicitudes', ['id' => $resuelta->id]);
        $this->assertDatabaseHas('permiso_solicitudes', ['id' => $pendiente->id]);

        $this->actingAs($coord)
            ->from(route('coordinadora.solicitudes'))
            ->delete(route('coordinadora.solicitudes.destroy', $pendiente->id))
            ->assertStatus(422);
    }

    /** @test */
    public function coordinadora_vacia_el_historial_sin_tocar_pendientes()
    {
        $coord = $this->makeUser('coordinadora');
        $rh = $this->makeUser('abogado');
        PermisoSolicitud::create([
            'user_id' => $rh->id,
            'permiso' => 'editar_casos',
            'que_hara' => 'Corregir',
            'motivo' => 'Error',
            'horas' => 1,
            'estado' => PermisoSolicitud::OTORGADA,
            'responded_by' => $coord->id,
            'responded_at' => now(),
        ]);
        $pendiente = PermisoSolicitud::create([
            'user_id' => $rh->id,
            'permiso' => 'editar_perfil',
            'que_hara' => 'Perfil',
            'motivo' => 'Cambio',
            'horas' => 1,
            'estado' => PermisoSolicitud::PENDIENTE,
        ]);

        $this->actingAs($coord)
            ->from(route('coordinadora.solicitudes'))
            ->delete(route('coordinadora.solicitudes.historial'))
            ->assertRedirect(route('coordinadora.solicitudes'));

        $this->assertDatabaseMissing('permiso_solicitudes', ['estado' => PermisoSolicitud::OTORGADA]);
        $this->assertDatabaseHas('permiso_solicitudes', ['id' => $pendiente->id]);
    }

    /** @test */
    public function coordinadora_avisa_a_integrantes_seleccionados()
    {
        $coord = $this->makeUser('coordinadora');
        $kelly = $this->makeUser('abogado', ['name' => 'Kelly']);
        $otro = $this->makeUser('abogado', ['name' => 'Otro RH']);

        $this->actingAs($coord)
            ->post(route('coordinadora.notificar.enviar'), [
                'destinatarios' => [$kelly->id],
                'titulo' => 'Revisar descargos',
                'motivo' => 'El conductor ya respondió',
                'cuerpo' => 'Revisa el expediente hoy.',
            ])
            ->assertRedirect(route('coordinadora.notificar'));

        $this->assertDatabaseHas('sipd_avisos', [
            'user_id' => $kelly->id,
            'titulo' => 'Revisar descargos',
            'tipo' => 'aviso',
        ]);
        $this->assertDatabaseMissing('sipd_avisos', [
            'user_id' => $otro->id,
            'titulo' => 'Revisar descargos',
        ]);

        $this->actingAs($kelly)
            ->get(route('notificaciones.index'))
            ->assertOk()
            ->assertSee('Aviso de coordinación')
            ->assertSee('Revisar descargos')
            ->assertSee('El conductor ya respondió');
    }

    /** @test */
    public function rh_no_borra_notificaciones_sin_permiso()
    {
        $coord = $this->makeUser('coordinadora');
        $rh = $this->makeUser('abogado');
        $aviso = \App\Models\Aviso::enviar($rh, [
            'remitente_id' => $coord->id,
            'tipo' => \App\Models\Aviso::TIPO_AVISO,
            'titulo' => 'Mensaje coord',
            'motivo' => 'Motivo',
        ]);

        $this->actingAs($rh)
            ->from(route('notificaciones.index'))
            ->delete(route('notificaciones.destroy', $aviso->id))
            ->assertRedirect(route('notificaciones.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('sipd_avisos', ['id' => $aviso->id]);
    }

    /** @test */
    public function rh_solicita_borrar_notificaciones_y_coordinadora_otorga()
    {
        $coord = $this->makeUser('coordinadora');
        $rh = $this->makeUser('abogado', ['name' => 'Kelly RH']);
        $aviso = \App\Models\Aviso::enviar($rh, [
            'remitente_id' => $coord->id,
            'tipo' => \App\Models\Aviso::TIPO_AVISO,
            'titulo' => 'Para borrar',
            'motivo' => 'Motivo',
        ]);

        $this->actingAs($rh)
            ->post(route('permisos.solicitar'), [
                'permiso' => 'eliminar_notificaciones',
                'que_hara' => 'Borrar avisos de la bandeja',
                'motivo' => 'Ya los atendí',
                'duracion' => '1',
            ])
            ->assertRedirect();

        $solicitud = \App\Models\PermisoSolicitud::first();
        $this->actingAs($coord)
            ->put(route('coordinadora.solicitudes.responder', $solicitud->id), [
                'accion' => 'otorgar',
                'permiso' => 'eliminar_notificaciones',
                'duracion' => '1',
            ])
            ->assertRedirect();

        $this->assertTrue($rh->fresh()->puede('eliminar_notificaciones'));

        $this->actingAs($rh)
            ->from(route('notificaciones.index'))
            ->delete(route('notificaciones.destroy', $aviso->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('sipd_avisos', ['id' => $aviso->id]);
    }

    /** @test */
    public function coordinadora_borra_notificaciones_sin_solicitar()
    {
        $coord = $this->makeUser('coordinadora');
        $aviso = \App\Models\Aviso::enviar($coord, [
            'tipo' => \App\Models\Aviso::TIPO_SOLICITUD,
            'titulo' => 'Solicitud de permiso',
            'cuerpo' => 'Kelly pide editar',
        ]);

        $this->actingAs($coord)
            ->from(route('notificaciones.index'))
            ->delete(route('notificaciones.destroy', $aviso->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('sipd_avisos', ['id' => $aviso->id]);
    }

    /** @test */
    public function coordinadora_puede_quitar_alertas_del_sistema_y_vuelven_si_hay_caso_nuevo()
    {
        $coord = $this->makeUser('coordinadora');
        $this->makeCaso($coord, [
            'estado' => 'En Proceso',
            'nombre' => 'Caso En Trámite',
            'created_at' => now()->subDays(8),
            'descargos' => null,
        ]);

        $this->actingAs($coord)
            ->get(route('notificaciones.index'))
            ->assertOk()
            ->assertSee('Plazos vencidos')
            ->assertSee('Pendientes de veredicto')
            ->assertSee('Descargos pendientes')
            ->assertSee('Quitar alertas de ahora');

        $this->actingAs($coord)
            ->from(route('notificaciones.index'))
            ->delete(route('notificaciones.alertas.silenciar', 'veredictos'))
            ->assertRedirect(route('notificaciones.index'))
            ->assertSessionHas('success');

        $this->actingAs($coord)
            ->get(route('notificaciones.index'))
            ->assertOk()
            ->assertDontSee('Pendientes de veredicto')
            ->assertSee('Plazos vencidos')
            ->assertSee('Descargos pendientes');

        $this->makeCaso($coord, [
            'estado' => 'En Proceso',
            'nombre' => 'Nuevo En Trámite',
        ]);

        $this->actingAs($coord)
            ->get(route('notificaciones.index'))
            ->assertOk()
            ->assertSee('Pendientes de veredicto')
            ->assertSee('1 expediente en proceso');
    }

    /** @test */
    public function coordinadora_quita_todas_las_alertas_de_ahora()
    {
        $coord = $this->makeUser('coordinadora');
        $this->makeCaso($coord, [
            'estado' => 'En Proceso',
            'created_at' => now()->subDays(8),
            'descargos' => null,
        ]);

        $this->actingAs($coord)
            ->from(route('notificaciones.index'))
            ->delete(route('notificaciones.alertas.silenciar-todas'))
            ->assertRedirect(route('notificaciones.index'));

        $this->actingAs($coord)
            ->get(route('notificaciones.index'))
            ->assertOk()
            ->assertDontSee('Plazos vencidos')
            ->assertDontSee('Pendientes de veredicto')
            ->assertDontSee('Descargos pendientes')
            ->assertSee('No hay notificaciones');
    }

    /** @test */
    public function coordinadora_ve_permisos_divididos_por_modulo_y_funcion()
    {
        $coord = $this->makeUser('coordinadora');
        $this->makeUser('abogado', ['name' => 'Kelly RH']);

        $this->actingAs($coord)
            ->get(route('coordinadora.abogados'))
            ->assertOk()
            ->assertSee('Generar documentos')
            ->assertSee('Descargar Word/PDF')
            ->assertSee('Guardar borrador')
            ->assertSee('Generar documento')
            ->assertSee('Descargar anexos')
            ->assertSee('Exportar reportes')
            ->assertSee('Tiempo del módulo')
            ->assertSee('name="duracion[expedientes]"', false)
            ->assertSee('name="duracion[documentos]"', false)
            ->assertDontSee('name="duracion[ver_casos]"', false)
            ->assertDontSee('name="duracion[editar_casos]"', false)
            ->assertDontSee('Ver autos y actas')
            ->assertDontSee('Generar y editar documentos');
    }

    /** @test */
    public function rh_sin_permiso_no_descarga_documentos()
    {
        $rh = $this->makeUser('abogado');
        $caso = $this->makeCaso($rh);

        $this->assertFalse($rh->puede('descargar_documentos'));

        $this->actingAs($rh)
            ->get(route('documentos.download', [$caso->id, 'disciplinario']))
            ->assertRedirect(route('abogado.dashboard'))
            ->assertSessionHas('error');
    }

    /** @test */
    public function coordinadora_otorga_solo_descarga_de_documentos()
    {
        $coord = $this->makeUser('coordinadora');
        $rh = $this->makeUser('abogado', ['name' => 'Kelly RH']);
        $caso = $this->makeCaso($rh);

        $this->actingAs($coord)
            ->put(route('coordinadora.abogados.permisos', $rh->id), [
                'permisos' => ['ver_casos', 'ver_documentos', 'descargar_documentos'],
                'duracion' => [
                    'expedientes' => 'permanente',
                    'documentos' => 'permanente',
                ],
            ])
            ->assertRedirect(route('coordinadora.abogados'));

        $rh = $rh->fresh();
        $this->assertTrue($rh->puede('descargar_documentos'));
        $this->assertTrue($rh->puede('ver_documentos'));
        $this->assertFalse($rh->puede('generar_documentos'));
        $this->assertFalse($rh->puede('editar_documentos'));

        $this->actingAs($rh)
            ->get(route('documentos.download', [$caso->id, 'disciplinario']))
            ->assertOk();
    }

    /** @test */
    public function marcar_descarga_incluye_ver_el_modulo()
    {
        $coord = $this->makeUser('coordinadora');
        $rh = $this->makeUser('abogado');

        $this->actingAs($coord)
            ->put(route('coordinadora.abogados.permisos', $rh->id), [
                'permisos' => ['descargar_documentos'],
                'duracion' => ['documentos' => '3'],
            ])
            ->assertRedirect();

        $rh = $rh->fresh();
        $this->assertTrue($rh->puede('descargar_documentos'));
        $this->assertTrue($rh->puede('ver_documentos'));
        $this->assertFalse($rh->puede('generar_documentos'));
    }

    /** @test */
    public function el_tiempo_del_modulo_cubre_ver_editar_y_eliminar_por_separado()
    {
        $coord = $this->makeUser('coordinadora');
        $rh = $this->makeUser('abogado');

        $this->actingAs($coord)
            ->put(route('coordinadora.abogados.permisos', $rh->id), [
                'permisos' => ['editar_casos', 'eliminar_casos'],
                'duracion' => ['expedientes' => '3'],
            ])
            ->assertRedirect();

        $rh = $rh->fresh()->load('permisos');
        $this->assertTrue($rh->puede('ver_casos'));
        $this->assertTrue($rh->puede('editar_casos'));
        $this->assertTrue($rh->puede('eliminar_casos'));
        $this->assertFalse($rh->puede('registrar_casos'));

        foreach (['ver_casos', 'editar_casos', 'eliminar_casos'] as $clave) {
            $grant = $rh->permisos->firstWhere('permiso', $clave);
            $this->assertNotNull($grant);
            $this->assertNotNull($grant->expires_at);
            $this->assertTrue($grant->expires_at->between(now()->addHours(2), now()->addHours(4)));
        }
    }

    private function makeCaso(?User $user, array $attrs = []): ProcesoDisciplinario
    {
        return ProcesoDisciplinario::factory()->create(array_merge([
            'user_id' => $user?->id,
            'nombre' => 'Test Trabajador',
            'estado' => 'Pendiente',
            'tipo_proceso' => 'disciplinario',
        ], $attrs));
    }
}
