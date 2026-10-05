<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ProcesoDisciplinario;
use App\Models\CasoDocumentoEstado;
use App\Models\Aviso;

class DemoKellySeeder extends Seeder
{
    public function run()
    {
        $kelly = User::where('name', 'like', '%Kelly%')->first();
        $admin = User::where('role', 'admin')->first();

        if (!$kelly || !$admin) {
            echo "No se encontró Kelly o Admin.\n";
            return;
        }

        // Limpiar datos previos de demo
        Aviso::where('user_id', $kelly->id)->delete();
        ProcesoDisciplinario::where('user_id', $kelly->id)->delete();

        // ──────────────────────────────────────────────────────────────────────
        // PROCESO 1: "Pendiente" — recién abierto, sin documentos
        // ──────────────────────────────────────────────────────────────────────
        ProcesoDisciplinario::create([
            'user_id'          => $kelly->id,
            'nombre'           => 'Carlos López',
            'cedula'           => '12345678',
            'cargo'            => 'Conductor',
            'modalidad'        => 'Conducción',
            'tipo_proceso'     => 'disciplinario',
            'tipo_falta'       => 'Leve',
            'descripcion_falta'=> 'Retraso injustificado',
            'fecha_falta'      => now()->subDays(5)->format('Y-m-d'),
            'estado'           => 'Pendiente',   // abierto sin docs = Pendiente
        ]);

        // ──────────────────────────────────────────────────────────────────────
        // PROCESO 2: "En Proceso" — tiene documento descargado
        // ──────────────────────────────────────────────────────────────────────
        $p2 = ProcesoDisciplinario::create([
            'user_id'          => $kelly->id,
            'nombre'           => 'Diana Suárez',
            'cedula'           => '98765432',
            'cargo'            => 'Auxiliar',
            'modalidad'        => 'Administrativos',
            'tipo_proceso'     => 'disciplinario',
            'tipo_falta'       => 'Grave',
            'descripcion_falta'=> 'Falta de EPP',
            'fecha_falta'      => now()->subDays(20)->format('Y-m-d'),
            'estado'           => 'Pendiente',   // estado BD = Pendiente
        ]);
        // Se marca el documento de apertura como descargado → sistema lo ve "En proceso"
        CasoDocumentoEstado::create([
            'caso_id'        => $p2->id,
            'tipo_documento' => 'disciplinario',
            'estado'         => 'completo',
            'descargado_en'  => now()->subDays(18),
            'generado_en'    => now()->subDays(19),
        ]);

        // ──────────────────────────────────────────────────────────────────────
        // PROCESO 3: "Sancionado" — cerrado por coordinadora
        // ──────────────────────────────────────────────────────────────────────
        ProcesoDisciplinario::create([
            'user_id'          => $kelly->id,
            'nombre'           => 'Mateo Ríos',
            'cedula'           => '11223344',
            'cargo'            => 'Mecánico',
            'modalidad'        => 'Administrativos',
            'tipo_proceso'     => 'disciplinario',
            'tipo_falta'       => 'Grave',
            'descripcion_falta'=> 'Negligencia en el mantenimiento de vehículo',
            'fecha_falta'      => now()->subDays(45)->format('Y-m-d'),
            'estado'           => 'Sancionado',
        ]);

        // ──────────────────────────────────────────────────────────────────────
        // NOTIFICACIONES DEMO (todos tipos semánticos)
        // ──────────────────────────────────────────────────────────────────────

        // 🔴 Veredicto urgente (rojo)
        Aviso::create([
            'user_id'      => $kelly->id,
            'remitente_id' => $admin->id,
            'tipo'         => Aviso::TIPO_VEREDICTO,
            'titulo'       => 'Se requiere tu veredicto',
            'motivo'       => 'El expediente de Diana Suárez está listo para cierre. Por favor ingresa y emite tu decisión final.',
            'proceso_id'   => $p2->id,
        ]);

        // 🟡 Solicitud de permiso (ámbar)
        Aviso::create([
            'user_id'      => $kelly->id,
            'remitente_id' => $admin->id,
            'tipo'         => Aviso::TIPO_SOLICITUD,
            'titulo'       => 'Solicitud de revisión de anexos',
            'motivo'       => 'Se solicita tu revisión y aprobación de los documentos adjuntos al expediente de Carlos López.',
        ]);

        // 🟢 Permiso respondido (verde)
        Aviso::create([
            'user_id'      => $kelly->id,
            'remitente_id' => $admin->id,
            'tipo'         => Aviso::TIPO_PERMISO,
            'titulo'       => 'Permiso de edición aprobado',
            'motivo'       => 'La coordinadora aprobó tu solicitud: puedes editar el acta del proceso de Mateo Ríos durante las próximas 5 horas.',
        ]);

        // 🟣 Aviso de coordinación (violeta)
        Aviso::create([
            'user_id'      => $kelly->id,
            'remitente_id' => $admin->id,
            'tipo'         => Aviso::TIPO_AVISO,
            'titulo'       => 'Aviso: reunión de seguimiento',
            'motivo'       => 'Recuerda que mañana a las 10:00 a.m. hay reunión de seguimiento de expedientes abiertos. Por favor ten listo el reporte de tus 3 casos activos.',
        ]);

        echo "Datos demo de Kelly generados correctamente!\n";
        echo "  → 1 Pendiente\n";
        echo "  → 1 En proceso (con documento)\n";
        echo "  → 1 Sancionado\n";
        echo "  → 4 notificaciones (todos los colores)\n";
    }
}
