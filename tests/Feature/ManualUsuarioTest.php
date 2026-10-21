<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualUsuarioTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function manual_muestra_los_modulos_de_la_coordinadora()
    {
        $coordinadora = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($coordinadora)
            ->get(route('manual.index'))
            ->assertOk()
            ->assertSee('Coordinadora de RH')
            ->assertSee('Veredictos')
            ->assertSee('Solicitudes de permisos')
            ->assertSee('Equipo y permisos')
            ->assertSee('Recuperación de contraseñas')
            ->assertSee('Seguimiento de descargos')
            ->assertSee('Avisar al equipo')
            ->assertDontSee('manual-detail-mis-casos');
    }

    /** @test */
    public function manual_del_equipo_muestra_sus_modulos_y_respeta_sus_permisos()
    {
        $equipo = User::factory()->create(['role' => User::ROLE_EQUIPO]);
        $equipo->permisos()->where('permiso', 'ver_reincidencias')->delete();
        $equipo->unsetRelation('permisos');

        $this->actingAs($equipo)
            ->get(route('manual.index'))
            ->assertOk()
            ->assertSee('Equipo de RH')
            ->assertSee('Mis casos')
            ->assertSee('Generar documentos')
            ->assertSee('Seguimiento de descargos')
            ->assertSee('Solicitar permiso')
            ->assertSee('Registrar procesos para la primera presentación')
            ->assertSee('Subir anexos para adjuntar evidencia')
            ->assertSee('Editar perfil y contraseña')
            ->assertSee('Ver el perfil no requiere permiso')
            ->assertSee('Solicitar permiso en la barra superior y elige Editar perfil y contraseña')
            ->assertSee('Acceso y permisos')
            ->assertSee('Editar datos del expediente')
            ->assertSee('Editar documento generado')
            ->assertSee('Exportar reportes')
            ->assertSee('Solicítalo a la coordinadora')
            ->assertSee('Permisos por acción')
            ->assertSee('Antes de empezar')
            ->assertSee('Al finalizar')
            ->assertDontSee('Veredictos')
            ->assertDontSee('Administrar equipo')
            ->assertSee('Reincidencias')
            ->assertSee('Acceso restringido')
            ->assertDontSee(route('abogado.reincidencias'));
    }

    /** @test */
    public function manual_requiere_autenticacion_y_perfil_de_la_aplicacion()
    {
        $this->get(route('manual.index'))->assertRedirect(route('login'));

        $usuarioSinRol = User::factory()->create(['role' => 'otro']);
        $this->actingAs($usuarioSinRol)
            ->get(route('manual.index'))
            ->assertRedirect('/');
    }
}
