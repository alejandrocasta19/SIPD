<?php

namespace Tests\Feature;

use App\Models\CasoDocumentoEstado;
use App\Models\CasoEvidencia;
use App\Models\CasoAnexo;
use App\Models\ProcesoDisciplinario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultaPublicaTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function muestra_el_formulario_de_consulta()
    {
        $this->get(route('consulta.publica'))
            ->assertOk()
            ->assertSee('Consultar mi caso')
            ->assertSee('Número de cédula');
    }

    /** @test */
    public function consulta_por_cedula_muestra_avance_sin_datos_reservados()
    {
        $secreto = 'El conductor golpeó el vehículo de placas ABC123 y amenazó al pasajero.';
        $descargos = 'Niego los hechos y pido copias de las pruebas internas.';
        $decision = 'Sanción de 15 días y descuento de $2.500.000';

        $proceso = ProcesoDisciplinario::factory()->create([
            'nombre' => 'Carlos Pérez',
            'cedula' => '1075123456',
            'placa' => 'XYZ987',
            'telefono' => '3001112233',
            'tipo_falta' => 'Falta grave por agresión',
            'descripcion_falta' => $secreto,
            'observacion' => 'Anotación reservada de RH',
            'descargos' => $descargos,
            'decision_final' => $decision,
            'estado' => 'En Proceso',
            'tipo_proceso' => 'disciplinario',
        ]);

        CasoDocumentoEstado::create([
            'caso_id' => $proceso->id,
            'tipo_documento' => 'disciplinario',
            'estado' => 'generado',
            'generado_en' => now()->subDays(2),
        ]);

        CasoEvidencia::create([
            'caso_id' => $proceso->id,
            'user_id' => User::factory()->create()->id,
            'nombre_original' => 'video-interno-camara.mp4',
            'nombre_almacenado' => 'oculto.bin',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'tamano' => 1024,
            'descripcion' => 'Grabación confidencial',
            'ruta_segura' => 'evidencias/oculto.bin',
        ]);

        CasoAnexo::create([
            'caso_id' => $proceso->id,
            'user_id' => User::factory()->create()->id,
            'tipo' => CasoAnexo::TIPO_ARCHIVO_PREVIO,
            'estado' => CasoAnexo::ESTADO_CARGADO,
            'titulo' => 'carta-despido-reservada.docx',
            'nombre_original' => 'carta-despido-reservada.docx',
            'nombre_almacenado' => 'ax_oculto.docx',
            'extension' => 'docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'tamano' => 2048,
            'ruta_segura' => 'anexos/oculto.docx',
        ]);

        $this->from(route('consulta.publica'))
            ->post(route('consulta.publica.buscar'), ['cedula' => '1075123456'])
            ->assertRedirect(route('consulta.publica'));

        $this->get(route('consulta.publica'))
            ->assertOk()
            ->assertSee('PRO-' . str_pad($proceso->id, 3, '0', STR_PAD_LEFT))
            ->assertSee('Carlos Pérez')
            ->assertSee('Proceso disciplinario')
            ->assertSee('En Proceso')
            ->assertSee('Ver trámite')
            ->assertSee('Línea de tiempo')
            ->assertSee('Apertura')
            ->assertSee('Descargos')
            ->assertSee('Se incorporó un elemento de prueba al expediente.')
            ->assertSee('Se incorporó un documento al expediente.')
            ->assertSee('1.075.123.456')
            ->assertDontSee($secreto)
            ->assertDontSee($descargos)
            ->assertDontSee($decision)
            ->assertDontSee('Anotación reservada de RH')
            ->assertDontSee('Falta grave por agresión')
            ->assertDontSee('XYZ987')
            ->assertDontSee('3001112233')
            ->assertDontSee('video-interno-camara.mp4')
            ->assertDontSee('Grabación confidencial')
            ->assertDontSee('carta-despido-reservada.docx');
    }

    /** @test */
    public function consulta_sin_resultados_no_expone_casos_ajenos()
    {
        ProcesoDisciplinario::factory()->create([
            'cedula' => '1111111111',
            'nombre' => 'Conductor Ajeno',
            'descripcion_falta' => 'Hecho reservado de otra persona',
        ]);

        $this->post(route('consulta.publica.buscar'), ['cedula' => '9999999999'])
            ->assertRedirect(route('consulta.publica'));

        $this->get(route('consulta.publica'))
            ->assertOk()
            ->assertSee('No se encontraron procesos con esa cédula.')
            ->assertDontSee('Conductor Ajeno')
            ->assertDontSee('Hecho reservado de otra persona');
    }

    /** @test */
    public function consulta_acepta_cedula_con_puntos()
    {
        $proceso = ProcesoDisciplinario::factory()->create([
            'nombre' => 'Ana Consulta',
            'cedula' => '1075123456',
        ]);

        $this->from(route('consulta.publica'))
            ->post(route('consulta.publica.buscar'), ['cedula' => '1.075.123.456'])
            ->assertRedirect(route('consulta.publica'));

        $this->get(route('consulta.publica'))
            ->assertOk()
            ->assertSee('Ana Consulta')
            ->assertSee('PRO-' . str_pad($proceso->id, 3, '0', STR_PAD_LEFT));
    }

    /** @test */
    public function login_muestra_acceso_institucional_y_consulta_publica()
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Acceso al sistema')
            ->assertSee('Consultar mi caso')
            ->assertSee('class="btn-public"', false)
            ->assertSee('ACCESO PÚBLICO')
            ->assertSee('Cootranshuila')
            ->assertSee('logo-sipd.png', false)
            ->assertDontSee('logo-sipd.svg', false)
            ->assertSee('Acceso rápido')
            ->assertSee('Coordinadora de RH')
            ->assertSee('Asesora jurídica')
            ->assertSee('Jefe de personal')
            ->assertSee('Asesor jurídico')
            ->assertSee('kellyrodriguez@sipd.co', false)
            ->assertSee('marshallrincon@sipd.co', false)
            ->assertSee('jorgerosado@sipd.co', false)
            ->assertSee('Kelly Rodriguez')
            ->assertSee('Marshall Rincón')
            ->assertSee('Jorge Rosado')
            ->assertDontSee('rh@sipd.co')
            ->assertDontSee('kelly.johanna.rodriguez@pendiente.local', false);
    }

    /** @test */
    public function seeder_crea_equipo_rh_con_correo_y_clave_del_nombre()
    {
        User::factory()->create([
            'name' => 'Equipo de RH',
            'email' => 'rh@sipd.co',
            'role' => User::ROLE_EQUIPO,
            'cargo' => 'Equipo de RH',
        ]);

        $this->seed();

        $kelly = User::where('email', 'kellyrodriguez@sipd.co')->first();
        $marshall = User::where('email', 'marshallrincon@sipd.co')->first();
        $jorge = User::where('email', 'jorgerosado@sipd.co')->first();

        $this->assertNotNull($kelly);
        $this->assertSame('equipo', $kelly->role);
        $this->assertSame('Asesora jurídica', $kelly->cargo);
        $this->assertSame('Kelly Rodriguez', $kelly->nombreCorto());
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('kelly123', $kelly->password));

        $this->assertNotNull($marshall);
        $this->assertSame('equipo', $marshall->role);
        $this->assertSame('Jefe de personal', $marshall->cargo);
        $this->assertSame('Marshall Rincón', $marshall->nombreCorto());
        $this->assertSame('MR', $marshall->inicialesCortas());
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('marshall123', $marshall->password));

        $this->assertNotNull($jorge);
        $this->assertSame('equipo', $jorge->role);
        $this->assertSame('Asesor jurídico', $jorge->cargo);
        $this->assertSame('Jorge Rosado', $jorge->nombreCorto());
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('jorge123', $jorge->password));

        $this->assertNull(User::where('email', 'rh@sipd.co')->first());
        $this->assertSame(3, User::where('role', User::ROLE_EQUIPO)->count());
    }
}
