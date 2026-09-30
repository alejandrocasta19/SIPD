<?php

namespace Tests\Feature;

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
        $this->makeCaso(null, ['estado' => 'Pendiente', 'nombre' => 'Conductor Sin RH']);

        $this->actingAs($coord)
            ->get(route('abogado.dashboard'))
            ->assertOk()
            ->assertSee('Coordinación de RH')
            ->assertSee('Pendientes de veredicto')
            ->assertSee('Conductor Veredicto')
            ->assertSee('Sin responsable de RH')
            ->assertSee('Conductor Sin RH')
            ->assertSee('Equipo de RH');
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
        $rh = $this->makeUser('abogado', ['name' => 'Analista RH']);
        $caso = $this->makeCaso(null, ['nombre' => 'Sin Dueño']);

        $this->actingAs($coord)
            ->put(route('coordinadora.asignar', $caso->id), [
                'user_id' => $rh->id,
            ])
            ->assertRedirect();

        $this->assertSame($rh->id, $caso->fresh()->user_id);
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
