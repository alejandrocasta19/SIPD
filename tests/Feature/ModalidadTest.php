<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Modalidades;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModalidadTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $cargo): User
    {
        return User::factory()->create([
            'role' => User::normalizarRol($role),
            'cargo' => $cargo,
        ]);
    }

    /** @test */
    public function la_coordinadora_ve_todas_las_modalidades_activas()
    {
        $user = $this->makeUser('coordinadora', 'Coordinadora de RH');

        $this->actingAs($user)
            ->get(route('abogado.registro'))
            ->assertOk()
            ->assertSee('Selecciona una modalidad para continuar.')
            ->assertSee('Asistente de Ventas')
            ->assertSee('Encomiendas')
            ->assertSee('Doble Yo')
            ->assertSee('Premium')
            ->assertSee('Platino Express')
            ->assertSee('id="expediente-rest" hidden', false);

        $this->actingAs($user)
            ->get(route('abogado.registro', ['modalidad' => 'Urbanos']))
            ->assertOk()
            ->assertSee('value="Urbanos" checked', false)
            ->assertDontSee('id="expediente-rest" hidden', false);
    }

    /** @test */
    public function el_equipo_solo_activa_las_modalidades_de_su_cargo()
    {
        $kelly = $this->makeUser('equipo', 'Asesora jurídica');

        $this->actingAs($kelly)
            ->get(route('abogado.registro'))
            ->assertOk()
            ->assertSee('Administrativos')
            ->assertSee('Estaciones')
            ->assertSee('value="Recursos humanos"', false)
            ->assertSee('value="Estación toma"', false)
            ->assertSee('value="Asistente de Ventas"', false)
            ->assertSee('value="Call center"', false)
            ->assertSee('value="Premium PQR y correos"', false)
            ->assertSee('value="Urbanos"', false)
            ->assertSee('value="Despacho"', false)
            ->assertDontSee('value="Terminal"', false)
            ->assertDontSee('value="Doble Yo"', false)
            ->assertDontSee('value="Encomiendas"', false);
    }

    /** @test */
    public function jefe_de_personal_y_asesor_juridico_ven_su_propio_grupo()
    {
        $marshall = $this->makeUser('equipo', 'Jefe de personal');
        $this->actingAs($marshall)
            ->get(route('abogado.registro'))
            ->assertOk()
            ->assertSee('value="Doble Yo"', false)
            ->assertSee('value="Premium"', false)
            ->assertSee('value="Platino Express"', false)
            ->assertDontSee('value="Despacho"', false);

        $jorge = $this->makeUser('equipo', 'Asesor jurídico');
        $this->actingAs($jorge)
            ->get(route('abogado.registro'))
            ->assertOk()
            ->assertSee('value="Terminal"', false)
            ->assertSee('value="Platino Express PQR y correos"', false)
            ->assertSee('value="Encomiendas"', false)
            ->assertSee('value="Mixto"', false)
            ->assertSee('value="Doble Yo PQR y correos"', false)
            ->assertSee('value="Platino Jet"', false)
            ->assertSee('value="Inspectores viales"', false)
            ->assertDontSee('value="Recursos humanos"', false)
            ->assertDontSee('value="Doble Yo"', false)
            ->assertDontSee('value="Call center"', false);

        $this->actingAs($jorge)
            ->get(route('abogado.registro', ['modalidad' => 'Terminal']))
            ->assertOk()
            ->assertSee('value="Terminal" checked', false)
            ->assertDontSee('id="placa-field" style="margin-top:14px;" hidden', false)
            ->assertDontSee('id="cargo-field" style="margin-top:14px;" hidden', false)
            ->assertDontSee('id="expediente-rest" hidden', false);

        $this->actingAs($marshall)
            ->get(route('abogado.registro', ['modalidad' => 'Doble Yo']))
            ->assertOk()
            ->assertDontSee('id="placa-field" style="margin-top:14px;" hidden', false)
            ->assertDontSee('id="cargo-field" style="margin-top:14px;" hidden', false);
    }

    /** @test */
    public function el_equipo_no_puede_registrar_una_modalidad_ajena()
    {
        $kelly = $this->makeUser('equipo', 'Asesora jurídica');

        $this->actingAs($kelly)
            ->from(route('abogado.registro'))
            ->post(route('abogado.registro.store'), [
                'tipo_proceso' => 'disciplinario',
                'nombre' => 'Conductor Demo',
                'modalidad' => 'Encomiendas',
            ])
            ->assertRedirect(route('abogado.registro'))
            ->assertSessionHasErrors('modalidad');

        $this->assertDatabaseCount('disciplinario', 0);
    }

    /** @test */
    public function el_equipo_registra_la_modalidad_de_su_cargo_y_la_coordinadora_cualquiera()
    {
        $kelly = $this->makeUser('equipo', 'Asesora jurídica');

        $this->actingAs($kelly)
            ->post(route('abogado.registro.store'), [
                'tipo_proceso' => 'disciplinario',
                'nombre' => 'Conductor Demo',
                'modalidad' => 'Recursos humanos',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('disciplinario', [
            'nombre' => 'Conductor Demo',
            'modalidad' => 'Administrativos',
            'cargo' => 'Recursos humanos',
        ]);

        $jorge = $this->makeUser('equipo', 'Asesor jurídico');

        $this->actingAs($jorge)
            ->post(route('abogado.registro.store'), [
                'tipo_proceso' => 'disciplinario',
                'nombre' => 'Caso Terminal',
                'modalidad' => 'Terminal',
                'placa' => 'GRK206',
                'cargo' => 'Conductor',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('disciplinario', [
            'nombre' => 'Caso Terminal',
            'modalidad' => 'Terminal',
            'placa' => 'GRK206',
            'cargo' => 'Conductor',
            'placa' => 'GRK-206',
            'cargo' => 'Conductor',
        ]);

        $this->actingAs($kelly)
            ->post(route('abogado.registro.store'), [
                'tipo_proceso' => 'disciplinario',
                'nombre' => 'Conductor Urbano',
                'cedula' => '1075123458',
                'modalidad' => 'Urbanos',
                'placa' => 'URB100',
                'cargo' => 'Conductor',
            ])
            ->assertRedirect();

        $this->actingAs($jorge)
            ->post(route('abogado.registro.store'), [
                'tipo_proceso' => 'disciplinario',
                'nombre' => 'Conductor Encomienda',
                'cedula' => '1075123459',
                'modalidad' => 'Encomiendas',
                'placa' => 'ENC200',
                'cargo' => 'Conductor',
            ])
            ->assertRedirect();

        $this->actingAs($kelly)
            ->get(route('abogado.consultarproceso'))
            ->assertOk()
            ->assertSee('Urbanos')
            ->assertSee('Conductor Urbano')
            ->assertDontSee('Conductor Encomienda');

        $this->actingAs($jorge)
            ->get(route('abogado.consultarproceso'))
            ->assertOk()
            ->assertSee('Encomiendas')
            ->assertSee('Conductor Encomienda')
            ->assertSee('Terminal')
            ->assertDontSee('Conductor Urbano');
    }

    /** @test */
    public function los_permisos_de_nuevo_proceso_listan_todas_y_solo_se_ven_las_activas()
    {
        $coord = $this->makeUser('coordinadora', 'Coordinadora de RH');
        $kelly = $this->makeUser('equipo', 'Asesora jurídica');

        $this->actingAs($coord)
            ->get(route('coordinadora.abogados'))
            ->assertOk()
            ->assertSee('Registrar procesos')
            ->assertSee('Editar datos del expediente')
            ->assertSee('Eliminar procesos')
            ->assertSee('class="perm-chip"', false)
            ->assertSee('value="modalidad_doble_yo"', false)
            ->assertSee('value="modalidad_urbanos"', false)
            ->assertSee('value="modalidad_inspectores_viales"', false)
            ->assertDontSee('name="duracion[modalidad_urbanos]"', false);

        $kelly->sincronizarPermisos(['registrar_casos', 'ver_casos', 'modalidad_urbanos'], []);

        $this->actingAs($kelly->fresh())
            ->get(route('abogado.registro'))
            ->assertOk()
            ->assertSee('value="Urbanos"', false)
            ->assertDontSee('value="Despacho"', false)
            ->assertDontSee('value="Doble Yo"', false);
    }

    /** @test */
    public function el_catalogo_cubre_los_tres_cargos_del_excel()
    {
        $this->assertSame(
            ['Doble Yo', 'Premium', 'Platino Express'],
            Modalidades::permitidas($this->makeUser('equipo', 'Jefe de personal'))
        );
        $this->assertSame(
            [
                'Asistente de Ventas',
                'Call center',
                'Administrativos',
                'Premium PQR y correos',
                'Urbanos',
                'Despacho',
                'Estaciones',
            ],
            Modalidades::permitidas($this->makeUser('equipo', 'Asesora jurídica'))
        );
        $this->assertSame(
            [
                'Platino Express PQR y correos',
                'Encomiendas',
                'Mixto',
                'Doble Yo PQR y correos',
                'Platino Jet',
                'Inspectores viales',
                'Terminal',
            ],
            Modalidades::permitidas($this->makeUser('equipo', 'Asesor jurídico'))
        );
        $this->assertCount(18, Modalidades::permitidas($this->makeUser('coordinadora', 'Coordinadora de RH')));
    }

    /** @test */
    public function la_placa_solo_aplica_en_modalidades_de_vehiculo()
    {
        $this->assertFalse(Modalidades::usaPlaca('Estación toma'));
        $this->assertTrue(Modalidades::usaPlaca('Terminal'));
        $this->assertTrue(Modalidades::usaPlaca('Doble Yo'));
        $this->assertFalse(Modalidades::usaPlaca('Administrativos'));
        $this->assertFalse(Modalidades::usaPlaca('Recursos humanos'));
        $this->assertSame('N/A', Modalidades::textoPlaca('Administrativos', 'ABC123'));
        $this->assertSame('N/A', Modalidades::textoPlaca('Estación toma', 'GRK206'));

        $kelly = $this->makeUser('equipo', 'Asesora jurídica');

        $this->actingAs($kelly)
            ->get(route('abogado.registro', ['modalidad' => 'Estación toma']))
            ->assertOk()
            ->assertSee('Placa del vehículo')
            ->assertSee('field-label">Cargo', false)
            ->assertSee('id="placa-field" style="margin-top:14px;" hidden', false)
            ->assertDontSee('id="cargo-field" style="margin-top:14px;" hidden', false);

        $this->actingAs($kelly)
            ->get(route('abogado.registro', ['modalidad' => 'Recursos humanos']))
            ->assertOk()
            ->assertSee('id="placa-field" style="margin-top:14px;" hidden', false)
            ->assertSee('id="cargo-field" style="margin-top:14px;" hidden', false);

        $this->actingAs($kelly)
            ->post(route('abogado.registro.store'), [
                'tipo_proceso' => 'disciplinario',
                'nombre' => 'Oficina',
                'cedula' => '1075000001',
                'modalidad' => 'Recursos humanos',
                'placa' => 'ABC123',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('disciplinario', [
            'nombre' => 'Oficina',
            'modalidad' => 'Administrativos',
            'cargo' => 'Recursos humanos',
            'placa' => null,
        ]);

        $this->actingAs($kelly)
            ->post(route('abogado.registro.store'), [
                'tipo_proceso' => 'disciplinario',
                'nombre' => 'Conductor Toma',
                'cedula' => '1075000002',
                'modalidad' => 'Estación toma',
                'placa' => 'GRK206',
                'cargo' => 'Conductor',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('disciplinario', [
            'nombre' => 'Conductor Toma',
            'modalidad' => 'Estación toma',
            'placa' => null,