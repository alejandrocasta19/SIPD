<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ProcesoDisciplinario;
use App\Models\CasoDocumentoEstado;
use App\Models\Aviso;
use App\Models\PermisoSolicitud;
use RuntimeException;

class DemoFullSeeder extends Seeder
{
    public function run()
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DemoFullSeeder no puede ejecutarse en producción.');
        }

        $admin    = User::where('role', 'admin')->first();
        $marshall = User::where('name', 'like', '%Marshall%')->first();
        $jorge    = User::where('name', 'like', '%Jorge%')->first();
        $kelly    = User::where('name', 'like', '%Kelly%')->first();

        if (!$admin || !$marshall || !$jorge || !$kelly) {
            echo "Faltan usuarios en el sistema.\n";
            return;
        }

        $equipoIds = [$marshall->id, $jorge->id, $kelly->id];
        Aviso::whereIn('user_id', array_merge([$admin->id], $equipoIds))->delete();
        PermisoSolicitud::whereIn('user_id', $equipoIds)->delete();
        ProcesoDisciplinario::whereIn('user_id', $equipoIds)->delete();

        // ====================================================================
        // KELLY — 5 casos (Modalidades: Administrativos, Estaciones, Despacho, etc.)
        // ====================================================================

        // 1. Pendiente (sin documentos)
        ProcesoDisciplinario::create([
            'user_id' => $kelly->id, 'nombre' => 'Martina Herrera', 'cedula' => '10101010',
            'cargo' => 'Recursos humanos', 'modalidad' => 'Administrativos',
            'tipo_proceso' => 'disciplinario', 'tipo_falta' => 'Retraso en ruta',
            'descripcion_falta' => 'Llegada tarde reiterada sin justificación',
            'fecha_falta' => now()->subDays(2)->format('Y-m-d'), 'estado' => 'Pendiente',
        ]);

        // 2. En Proceso (documento descargado)
        $pK2 = ProcesoDisciplinario::create([
            'user_id' => $kelly->id, 'nombre' => 'Luis Fernando Gómez', 'cedula' => '20202020',
            'cargo' => 'Despachador', 'modalidad' => 'Despacho',
            'tipo_proceso' => 'disciplinario', 'tipo_falta' => 'Desobediencia a instrucciones',
            'descripcion_falta' => 'Negligencia en asignación de rutas',
            'fecha_falta' => now()->subDays(10)->format('Y-m-d'), 'estado' => 'Pendiente',
        ]);
        CasoDocumentoEstado::create([
            'caso_id' => $pK2->id, 'tipo_documento' => 'disciplinario',
            'estado' => 'completo', 'descargado_en' => now()->subDays(7), 'generado_en' => now()->subDays(8),
        ]);

        // 3. En Proceso (otro caso avanzado)
        $pK3 = ProcesoDisciplinario::create([
            'user_id' => $kelly->id, 'nombre' => 'Paola Rojas', 'cedula' => '30303011',
            'cargo' => 'Estación toma', 'modalidad' => 'Estación toma',
            'tipo_proceso' => 'disciplinario', 'tipo_falta' => 'Abandono de ruta',
            'descripcion_falta' => 'Abandono de puesto sin autorización',
            'fecha_falta' => now()->subDays(18)->format('Y-m-d'), 'estado' => 'Pendiente',
        ]);
        CasoDocumentoEstado::create([
            'caso_id' => $pK3->id, 'tipo_documento' => 'disciplinario',
            'estado' => 'completo', 'descargado_en' => now()->subDays(14), 'generado_en' => now()->subDays(15),
        ]);

        // 4. Sancionado
        ProcesoDisciplinario::create([
            'user_id' => $kelly->id, 'nombre' => 'Diego Salcedo', 'cedula' => '40404011',
            'cargo' => 'Auxiliar call center', 'modalidad' => 'Call center',
            'tipo_proceso' => 'disciplinario', 'tipo_falta' => 'Maltrato al pasajero',
            'descripcion_falta' => 'Mal trato reiterado al usuario reportado por supervisor',
            'fecha_falta' => now()->subDays(50)->format('Y-m-d'), 'estado' => 'Sancionado',
        ]);

        // 5. Pendiente reciente
        ProcesoDisciplinario::create([
            'user_id' => $kelly->id, 'nombre' => 'Sofía Vargas', 'cedula' => '50505011',
            'cargo' => 'Asistente de ventas', 'modalidad' => 'Asistente de Ventas',
            'tipo_proceso' => 'disciplinario', 'tipo_falta' => 'Incumplimiento de horario',
            'descripcion_falta' => 'Incumplimiento de protocolo de atención',
            'fecha_falta' => now()->subDays(1)->format('Y-m-d'), 'estado' => 'Pendiente',
        ]);

        // 6. En Proceso con descargos
        $pK6 = ProcesoDisciplinario::create([
            'user_id' => $kelly->id, 'nombre' => 'Manuel Castañeda', 'cedula' => '60606011',
            'cargo' => 'Auxiliar call center', 'modalidad' => 'Call center',
            'tipo_proceso' => 'disciplinario', 'tipo_falta' => 'Agresión verbal o física',
            'descripcion_falta' => 'Agresión verbal a un compañero en horario laboral',
            'fecha_falta' => now()->subDays(25)->format('Y-m-d'), 'estado' => 'Pendiente',
        ]);
        CasoDocumentoEstado::create([
            'caso_id' => $pK6->id, 'tipo_documento' => 'fallo',
            'estado' => 'borrador',
        ]);

        // 7. Archivado
        ProcesoDisciplinario::create([
            'user_id' => $kelly->id, 'nombre' => 'Lucía Mendoza', 'cedula' => '70707011',
            'cargo' => 'Asistente de ventas', 'modalidad' => 'Asistente de Ventas',
            'tipo_proceso' => 'disciplinario', 'tipo_falta' => 'Incumplimiento de la resolución',
            'descripcion_falta' => 'Se verificó que los argumentos no constituían falta disciplinaria',
            'fecha_falta' => now()->subDays(60)->format('Y-m-d'), 'estado' => 'Archivado',
        ]);

        // ====================================================================
        // JORGE — 7 casos (Modalidades: Terminal, Mixto, Inspectores viales, etc.)
        // ====================================================================

        // 1. Sancionado
        ProcesoDisciplinario::create([
            'user_id' => $jorge->id, 'nombre' => 'Fernando Ruiz', 'cedula' => '10000001',
            'cargo' => 'Conductor', 'modalidad' => 'Mixto',
            'tipo_proceso' => 'disciplinario', 'tipo_falta' => 'Conducción negligente',
            'descripcion_falta' => 'Consumo de alcohol en horas laborales comprobado con alcotest',
            'fecha_falta' => now()->subDays(40)->format('Y-m-d'), 'estado' => 'Sancionado',
        ]);

        // 2. Pendiente
        ProcesoDisciplinario::create([
            'user_id' => $jorge->id, 'nombre' => 'Camila Ortiz', 'cedula' => '20000002',
            'cargo' => 'Inspectora', 'modalidad' => 'Inspectores viales',
            'tipo_proceso' => 'disciplinario', 'tipo_falta' => 'Porte indebido del uniforme',
            'descripcion_falta' => 'Uso indebido de dotación institucional',
            'fecha_falta' => now()->subDays(5)->format('Y-m-d'), 'estado' => 'Pendiente',
        ]);

        // 3. En Proceso
        $pJ3 = ProcesoDisciplinario::create([
            'user_id' => $jorge->id, 'nombre' => 'Ramiro Peña', 'cedula' => '30000003',
            'cargo' => 'Taquillero terminal', 'modalidad' => 'Terminal',
            'tipo_proceso' => 'disciplinario', 'tipo_falta' => 'Faltante de dinero',
            'descripcion_falta' => 'Descuadre en caja mensual por tercer mes consecutivo',
            'fecha_falta' => now()->subDays(22)->format('Y-m-d'), 'estado' => 'Pendiente',
        ]);
        CasoDocumentoEstado::create([
            'caso_id' => $pJ3->id, 'tipo_documento' => 'disciplinario',
            'estado' => 'completo', 'descargado_en' => now()->subDays(12), 'generado_en' => now()->subDays(13),
        ]);

        // 4. Sancionado
        ProcesoDisciplinario::create([
            'user_id' => $jorge->id, 'nombre' => 'Beatriz Luna', 'cedula' => '40000004',
            'cargo' => 'Conductor encomiendas', 'modalidad' => 'Encomiendas',
            'tipo_proceso' => 'disciplinario', 'tipo_falta' => 'Hurto o apropiación de recursos',
            'descripcion_falta' => 'Pérdida de paquete de valor con cliente',
            'fecha_falta' => now()->subDays(60)->format('Y-m-d'), 'estado' => 'Sancionado',
        ]);

        // 5. En Proceso (listo para veredicto)
        $pJ5 = ProcesoDisciplinario::create([
            'user_id' => $jorge->id, 'nombre' => 'Ernesto Cárdenas', 'cedula' => '50000005',
            'cargo' => 'Conductor', 'modalidad' => 'Doble Yo PQR y correos',
            'tipo_proceso' => 'disciplinario', 'tipo_falta' => 'Agresión verbal o física',
            'descripcion_falta' => 'Reporte de acoso verbal a pasajero',
            'fecha_falta' => now()->subDays(30)->format('Y-m-d'), 'estado' => 'Pendiente',
        ]);
        CasoDocumentoEstado::create([
            'caso_id' => $pJ5->id, 'tipo_documento' => 'fallo',
            'estado' => 'completo', 'descargado_en' => now()->subDays(3), 'generado_en' => now()->subDays(4),
        ]);

        // 6. Pendiente con evidencia
        ProcesoDisciplinario::create([
            'user_id' => $jorge->id, 'nombre' => 'Gustavo Alarcón', 'cedula' => '60000006',
            'cargo' => 'Conductor', 'modalidad' => 'Doble Yo PQR y correos',
            'tipo_proceso' => 'disciplinario', 'tipo_falta' => 'Retraso en ruta',
            'descripcion_falta' => 'Múltiples retrasos en la semana sin reportar novedades',
            'fecha_falta' => now()->subDays(2)->format('Y-m-d'), 'estado' => 'Pendiente',
        ]);

        // 7. En Proceso (fase descargos)
        $pJ7 = ProcesoDisciplinario::create([
            'user_id' => $jorge->id, 'nombre' => 'Natalia Ospina', 'cedula' => '70000007',
            'cargo' => 'Inspectora', 'modalidad' => 'Inspectores viales',
            'tipo_proceso' => 'disciplinario', 'tipo_falta' => 'Ausencia sin justificación',
            'descripcion_falta' => 'No se presentó a laborar y no responde el celular',
            'fecha_falta' => now()->subDays(15)->format('Y-m-d'), 'estado' => 'Pendiente',
        ]);
        CasoDocumentoEstado::create([
            'caso_id' => $pJ7->id, 'tipo_documento' => 'fallo',
            'estado' => 'completo', 'descargado_en' => now()->subDays(1),
        ]);

        // ====================================================================
        // MARSHALL — 7 casos (Modalidades: Premium, Doble Yo, Platino Express, etc.)
        // ====================================================================

        // 1. Pendiente
        ProcesoDisciplinario::create([
            'user_id' => $marshall->id, 'nombre' => 'Roberto Sánchez', 'cedula' => '10000010',
            'cargo' => 'Conductor', 'modalidad' => 'Premium',
            'tipo_proceso' => 'disciplinario', 'tipo_falta' => 'Uso inadecuado del vehículo',
            'descripcion_falta' => 'Exceso de velocidad registrado por GPS',
            'fecha_falta' => now()->subDays(1)->format('Y-m-d'), 'estado' => 'Pendiente',
        ]);

        // 2. En Proceso (listo para veredicto)
        $pM2 = ProcesoDisciplinario::create([
            'user_id' => $marshall->id, 'nombre' => 'Héctor Jiménez', 'cedula' => '20000020',
            'cargo' => 'Conductor', 'modalidad' => 'Premium',
            'tipo_proceso' => 'disciplinario', 'tipo_falta' => 'Abandono de ruta',
            'descripcion_falta' => 'Desvío de ruta no autorizado con pasajeros a bordo',
            'fecha_falta' => now()->subDays(20)->format('Y-m-d'), 'estado' => 'Pendiente',
        ]);
        CasoDocumentoEstado::create([
            'caso_id' => $pM2->id, 'tipo_documento' => 'fallo',
            'estado' => 'completo', 'descargado_en' => now()->subDays(2), 'generado_en' => now()->subDays(3),
        ]);

        // 3. Sancionado
        ProcesoDisciplinario::create([
            'user_id' => $marshall->id, 'nombre' => 'Gladys Medina', 'cedula' => '30000030',
            'cargo' => 'Auxiliar de cabina', 'modalidad' => 'Doble Yo',
            'tipo_proceso' => 'disciplinario', 'tipo_falta' => 'Hurto o apropiación de recursos',
            'descripcion_falta' => 'Hurto de pertenencias de pasajero documentado con cámaras',
            'fecha_falta' => now()->subDays(75)->format('Y-m-d'), 'estado' => 'Sancionado',
        ]);

        // 4. En Proceso
        $pM4 = ProcesoDisciplinario::create([
            'user_id' => $marshall->id, 'nombre' => 'Carlos Espinosa', 'cedula' => '40000040',
            'cargo' => 'Conductor', 'modalidad' => 'Platino Express',
            'tipo_proceso' => 'disciplinario', 'tipo_falta' => 'Accidente con vehículo',
            'descripcion_falta' => 'Accidente de tránsito con responsabilidad del conductor',
            'fecha_falta' => now()->subDays(35)->format('Y-m-d'), 'estado' => 'Pendiente',
        ]);
        CasoDocumentoEstado::create([
            'caso_id' => $pM4->id, 'tipo_documento' => 'disciplinario',
            'estado' => 'completo', 'descargado_en' => now()->subDays(20), 'generado_en' => now()->subDays(21),
        ]);

        // 5. Pendiente reciente
        ProcesoDisciplinario::create([
            'user_id' => $marshall->id, 'nombre' => 'Jaime Suárez', 'cedula' => '50000050',
            'cargo' => 'Conductor', 'modalidad' => 'Platino Jet',
            'tipo_proceso' => 'disciplinario', 'tipo_falta' => 'Ausencia sin justificación',
            'descripcion_falta' => 'Incumplimiento horario de salida sin aviso',
            'fecha_falta' => now()->subDays(3)->format('Y-m-d'), 'estado' => 'Pendiente',
        ]);

        // 6. Archivado
        ProcesoDisciplinario::create([
            'user_id' => $marshall->id, 'nombre' => 'Julián Ríos', 'cedula' => '60000060',
            'cargo' => 'Conductor', 'modalidad' => 'Platino Jet',
            'tipo_proceso' => 'disciplinario', 'tipo_falta' => 'Uso inadecuado del vehículo',
            'descripcion_falta' => 'Se determinó que el sistema GPS presentó un error de lectura',
            'fecha_falta' => now()->subDays(80)->format('Y-m-d'), 'estado' => 'Archivado',
        ]);

        // 7. En Proceso (inicial)
        $pM7 = ProcesoDisciplinario::create([
            'user_id' => $marshall->id, 'nombre' => 'Carmen Buitrago', 'cedula' => '70000070',
            'cargo' => 'Auxiliar de cabina', 'modalidad' => 'Doble Yo',
            'tipo_proceso' => 'disciplinario', 'tipo_falta' => 'Maltrato al pasajero',
            'descripcion_falta' => 'Queja escrita recibida en PQR por mala atención',
            'fecha_falta' => now()->subDays(8)->format('Y-m-d'), 'estado' => 'Pendiente',
        ]);
        CasoDocumentoEstado::create([
            'caso_id' => $pM7->id, 'tipo_documento' => 'disciplinario',
            'estado' => 'borrador',
        ]);

        // ====================================================================
        // NOTIFICACIONES Y SOLICITUDES
        // ====================================================================

        // Solicitud de edición (Jorge → Coordinadora)
        $sol1 = PermisoSolicitud::create([
            'user_id' => $jorge->id, 'permiso' => 'editar_casos',
            'que_hara' => 'Corregir nombre del conductor en el expediente',
            'motivo' => 'El formato original llegó como Fernando pero su cédula dice Hernando. Quiero actualizar el expediente.',
            'horas' => 5, 'estado' => PermisoSolicitud::PENDIENTE,
        ]);
        Aviso::create([
            'user_id' => $admin->id, 'remitente_id' => $jorge->id,
            'tipo' => Aviso::TIPO_SOLICITUD,
            'titulo' => 'Solicitud de edición (Jorge)',
            'motivo' => 'Jorge solicita permiso de edición para corregir datos del expediente.',
            'solicitud_id' => $sol1->id,
        ]);

        // Solicitud de eliminación (Marshall → Coordinadora)
        $sol2 = PermisoSolicitud::create([
            'user_id' => $marshall->id, 'permiso' => 'descargar_documentos',
            'que_hara' => 'Descargar el acta de descargo firmada del expediente de Héctor',
            'motivo' => 'Necesito el PDF final para radicarlo ante la gerencia esta semana.',
            'horas' => 1, 'estado' => PermisoSolicitud::PENDIENTE,
        ]);
        Aviso::create([
            'user_id' => $admin->id, 'remitente_id' => $marshall->id,
            'tipo' => Aviso::TIPO_SOLICITUD,
            'titulo' => 'Solicitud de descarga (Marshall)',
            'motivo' => 'Marshall solicita permiso para descargar el PDF final del caso Héctor Jiménez.',
            'solicitud_id' => $sol2->id,
        ]);

        // Veredicto urgente (Marshall → Coordinadora)
        Aviso::create([
            'user_id' => $admin->id, 'remitente_id' => $marshall->id,
            'tipo' => Aviso::TIPO_VEREDICTO,
            'titulo' => 'Veredicto urgente: Premium',
            'motivo' => 'El expediente de Héctor Jiménez está completo y requiere tu veredicto final antes de viernes.',
            'proceso_id' => $pM2->id,
        ]);

        // Veredicto (Jorge → Coordinadora)
        Aviso::create([
            'user_id' => $admin->id, 'remitente_id' => $jorge->id,
            'tipo' => Aviso::TIPO_VEREDICTO,
            'titulo' => 'Veredicto: PQR y correos',
            'motivo' => 'El caso de Ernesto Cárdenas ya está en fase de fallo. Requiere tu firma para proceder con la sanción.',
            'proceso_id' => $pJ5->id,
        ]);

        // Aviso reminder (Coordinadora → Kelly)
        Aviso::create([
            'user_id' => $kelly->id, 'remitente_id' => $admin->id,
            'tipo' => Aviso::TIPO_AVISO,
            'titulo' => 'Recordatorio: término próximo',
            'motivo' => 'El expediente de Luis Fernando Gómez vence en 3 días. Asegúrate de tener listos los anexos.',
            'proceso_id' => $pK2->id,
        ]);

        // Aviso coordinator a Jorge
        Aviso::create([
            'user_id' => $jorge->id, 'remitente_id' => $admin->id,
            'tipo' => Aviso::TIPO_AVISO,
            'titulo' => 'Aviso: documentos pendientes',
            'motivo' => 'Camila Ortiz (Inspectores Viales) lleva 5 días sin movimiento. Revisa si necesita apoyo para continuar.',
        ]);

        // Aviso coordinator a Marshall
        Aviso::create([
            'user_id' => $marshall->id, 'remitente_id' => $admin->id,
            'tipo' => Aviso::TIPO_PERMISO,
            'titulo' => 'Permiso de descarga aprobado',
            'motivo' => 'Te otorgo acceso para descargar el acta del caso Héctor Jiménez. Tienes 1 hora desde este aviso.',
        ]);

        echo "✅ 21 casos cargados en el sistema.\n";
        echo "   → Kelly:    7 casos (2 Pendientes, 3 En Proceso, 1 Sancionado, 1 Archivado)\n";
        echo "   → Jorge:    7 casos (2 Pendientes, 3 En Proceso, 2 Sancionados)\n";
        echo "   → Marshall: 7 casos (2 Pendientes, 3 En Proceso, 1 Sancionado, 1 Archivado)\n";
        echo "   → Coordinadora: ve los 21 globales + 4 notificaciones + 2 solicitudes pendientes\n";
    }
}
