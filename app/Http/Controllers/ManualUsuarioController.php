<?php

namespace App\Http\Controllers;

use App\Support\RhPermisos;

class ManualUsuarioController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $esCoordinadora = $user->esCoordinadora();

        $modulos = [
            [
                'id' => 'inicio',
                'titulo' => 'Inicio',
                'descripcion' => 'Resumen del trabajo y accesos rápidos según tu perfil.',
                'icono' => 'fas fa-home',
                'color' => 'green',
                'grupo' => 'General',
                'ruta' => route('abogado.dashboard'),
                'accion' => 'Ir al inicio',
                'antes' => 'Usa este panel como punto de partida al iniciar la jornada. Revisa las cifras y tareas pendientes antes de abrir un expediente.',
                'funciones' => [
                    'Los indicadores muestran expedientes Pendientes, En proceso, Sancionados y Archivados.',
                    'Requieren atención destaca casos abiertos y tareas como plazos próximos o vencidos.',
                    'Los accesos rápidos llevan a los módulos disponibles en tu perfil.',
                    'La coordinadora ve además supervisión del equipo, solicitudes y casos en espera de veredicto.',
                ],
                'pasos' => [
                    'Abre Inicio al entrar al sistema y confirma que estás en la cuenta correcta.',
                    'Revisa primero los plazos y las filas de Requieren atención.',
                    'Selecciona un indicador para abrir la lista filtrada o una fila para abrir su expediente.',
                    'Continúa en el módulo indicado y vuelve a Inicio para revisar lo demás.',
                ],
                'consejos' => [
                    'Las cifras dependen de tu perfil y de los expedientes que puedes consultar.',
                    'Un indicador solo abre una lista; no cambia por sí mismo el estado del caso.',
                    'La cola de veredictos de coordinación contiene expedientes En proceso.',
                ],
                'resultado' => 'Identificarás qué casos requieren atención y desde qué módulo continuar.',
            ],
            [
                'id' => 'bandeja',
                'titulo' => 'Bandeja de mensajes',
                'descripcion' => 'Consulta avisos, solicitudes y comunicaciones asociadas a tu cuenta.',
                'icono' => 'far fa-comments',
                'color' => 'blue',
                'grupo' => 'General',
                'ruta' => route('notificaciones.index'),
                'accion' => 'Abrir bandeja',
                'antes' => 'Revisa el contador de mensajes. La bandeja es personal y contiene avisos dirigidos a tu cuenta.',
                'funciones' => [
                    'Consultar avisos de coordinación, resultados de veredictos, respuestas a permisos y otros eventos de tu cuenta.',
                    'Abrir el expediente relacionado cuando el aviso incluye una referencia.',
                    'Marcar mensajes como leídos, eliminar un aviso o borrar en grupo los que ya leíste.',
                    'Silenciar alertas del sistema sin borrar el evento ni el expediente.',
                ],
                'pasos' => [
                    'Abre Bandeja de mensajes desde el menú lateral o el icono superior.',
                    'Lee el título, remitente, fecha y número de proceso para identificar el motivo.',
                    'Selecciona el aviso para consultar su contexto; al abrirlo puede quedar como leído.',
                    'Usa borrar o silenciar solo si necesitas retirar ese aviso de tu vista.',
                ],
                'consejos' => [
                    'Silenciar una alerta oculta la alerta actual; un evento nuevo puede volver a generarla.',
                    'Eliminar un aviso no elimina el expediente, documento ni solicitud relacionados.',
                    'Puedes eliminar tus avisos libremente; solo se borran de tu propia bandeja y no se elimina el expediente relacionado.',
                ],
                'resultado' => 'Atenderás las comunicaciones sin confundir quitar un aviso con borrar el registro asociado.',
            ],
            [
                'id' => 'perfil',
                'titulo' => 'Mi perfil y contraseña',
                'descripcion' => 'Consulta tus datos de cuenta y gestiona las opciones habilitadas.',
                'icono' => 'far fa-user',
                'color' => 'purple',
                'grupo' => 'General',
                'ruta' => route('perfil.show'),
                'accion' => 'Abrir mi perfil',
                'antes' => 'El nombre y cargo de la cuenta identifican al usuario activo y pueden utilizarse en documentos oficiales.',
                'funciones' => [
                    'Consultar los datos de tu cuenta.',
                    'Guardar cambios del perfil o cambiar la contraseña requiere el permiso Editar perfil y contraseña para el equipo.',
                ],
                'pasos' => [
                    'Abre el menú de usuario en la esquina superior derecha.',
                    'Selecciona Mi perfil para ver o editar datos, o Cambiar contraseña para abrir el formulario de contraseña.',
                    'Si te falta permiso, pulsa Solicitar permiso en la barra superior y elige Editar perfil y contraseña.',
                    'Tras la aprobación, modifica solo los datos necesarios y guarda. Para la contraseña, escribe la actual y confirma la nueva.',
                ],
                'consejos' => [
                    'Ver el perfil no requiere permiso; guardar datos o cambiar la contraseña sí lo requiere para el equipo RH.',
                    'Espera la aprobación antes de guardar cambios. No compartas tu contraseña actual ni la nueva.',
                ],
                'resultado' => 'Los datos o la contraseña quedarán actualizados solo después de guardar correctamente.',
            ],
            [
                'id' => 'buscar-procesos',
                'titulo' => 'Buscar procesos',
                'descripcion' => 'La coordinadora consulta expedientes del sistema y filtra sus resultados.',
                'icono' => 'fas fa-search',
                'color' => 'blue',
                'grupo' => 'Coordinadora',
                'ruta' => route('abogado.consultarproceso'),
                'accion' => 'Buscar procesos',
                'solo_coordinadora' => true,
                'antes' => 'Ten un dato confiable del expediente: nombre o identificación, placa, modalidad o número de proceso.',
                'funciones' => [
                    'Localizar expedientes del sistema por trabajador, identificación, placa, modalidad o número de proceso.',
                    'Acotar resultados con filtros disponibles, como estado y modalidad.',
                    'Abrir el detalle para consultar información, documentos y anexos asociados.',
                ],
                'pasos' => [
                    'Abre Buscar procesos desde el menú lateral.',
                    'Escribe el dato en la búsqueda y aplica filtros si necesitas reducir resultados.',
                    'Confirma nombre, identificación y código antes de seleccionar el resultado.',
                    'Abre el expediente para revisar sus antecedentes y continuar.',
                ],
                'consejos' => [
                    'Si no aparece, limpia filtros y comprueba cómo se registró el dato.',
                    'Verifica que sea el expediente correcto antes de modificarlo o registrar un veredicto.',
                    'Consultar un caso no concede automáticamente permiso para editar o descargar archivos.',
                ],
                'resultado' => 'Llegarás al expediente correcto con sus datos y antecedentes a la vista.',
            ],
            [
                'id' => 'mis-casos',
                'titulo' => 'Mis casos',
                'descripcion' => 'El equipo consulta y continúa los expedientes a los que tiene acceso.',
                'icono' => 'fas fa-folder-open',
                'color' => 'green',
                'grupo' => 'Equipo de RH',
                'ruta' => route('abogado.mis-casos'),
                'accion' => 'Abrir mis casos',
                'solo_equipo' => true,
                'permiso' => 'ver_casos',
                'antes' => 'Mis casos muestra expedientes asignados a tu cuenta. Si necesitas acceder a uno de otra persona, consulta a la coordinadora sobre su asignación.',
                'funciones' => [
                    'Buscar expedientes por datos del trabajador y filtrar por estado o modalidad.',
                    'Reconocer el avance: Pendiente, En proceso, Sancionado o Archivado.',
                    'Abrir el detalle para consultar datos, descargos, documentos y anexos.',
                    'Usar acciones como editar o eliminar solo si están habilitadas para tu cuenta.',
                ],
                'pasos' => [
                    'Abre Mis casos desde el menú o desde un indicador de Inicio.',
                    'Busca por nombre, cédula u otro dato y aplica un filtro si hay muchos resultados.',
                    'Revisa el estado y confirma que identificaste al trabajador correcto.',
                    'Abre el caso usando su código o el botón de detalle.',
                    'Continúa desde las acciones disponibles dentro del expediente.',
                ],
                'consejos' => [
                    'El listado se limita a los casos asignados a tu usuario y puede tener varias páginas.',
                    'Editar y eliminar son permisos diferentes; pide solo la acción que necesitas.',
                    'Los expedientes cerrados pueden tener restricciones adicionales.',
                ],
                'resultado' => 'Encontrarás el caso asignado y reconocerás el siguiente paso disponible.',
            ],
            [
                'id' => 'nuevo-proceso',
                'titulo' => 'Nuevo proceso',
                'descripcion' => 'Registra un expediente y deja preparado su borrador inicial.',
                'icono' => 'fas fa-plus-circle',
                'color' => 'orange',
                'grupo' => 'Procesos',
                'ruta' => route('abogado.registro'),
                'accion' => 'Registrar proceso',
                'permiso' => 'registrar_casos',
                'antes' => 'Reúne la identificación del trabajador, modalidad, fecha y descripción de los hechos. Confirma que no exista ya un expediente del mismo evento.',
                'funciones' => [
                    'Crear un expediente con los datos del trabajador y la información del hecho.',
                    'Seleccionar una modalidad habilitada y completar los campos adicionales que correspondan.',
                    'Consultar reincidencias antes del registro cuando necesites verificar antecedentes.',
                ],
                'pasos' => [
                    'Abre Nuevo proceso y elige la modalidad correcta.',
                    'Completa los datos requeridos del trabajador y del hecho; revisa los campos que aparecen según modalidad.',
                    'Verifica nombre, identificación, fecha y descripción antes de guardar.',
                    'Guarda el registro y confirma el número de proceso que muestra el sistema.',
                    'Continúa en Generar documentos para diligenciar los formatos del caso.',
                ],
                'consejos' => [
                    'Crear el expediente no significa que sus documentos oficiales estén listos, generados o descargados.',
                    'Las modalidades visibles dependen del cargo y los accesos asignados. Si falta una, contacta a la coordinadora.',
                    'Si no tienes permiso Registrar procesos, solicítalo desde la barra superior.',
                    'Revisa la identificación y reincidencias para evitar asociar información a otra persona.',
                ],
                'resultado' => 'El expediente queda creado y disponible para continuar su gestión documental.',
            ],
            [
                'id' => 'documentos',
                'titulo' => 'Generar documentos',
                'descripcion' => 'Diligencia, genera, previsualiza y descarga documentos oficiales del expediente.',
                'icono' => 'fas fa-file-signature',
                'color' => 'orange',
                'grupo' => 'Documentación',
                'ruta' => route('documentos.hub'),
                'accion' => 'Abrir documentos',
                'permiso' => 'ver_documentos',
                'antes' => 'Abre el expediente correcto en Mis casos y ten a mano los datos y soportes necesarios para completar los formatos.',
                'funciones' => [
                    'Consultar los cuatro espacios documentales del expediente y el estado de cada uno.',
                    'Diligenciar campos, guardar borradores y generar documentos.',
                    'Previsualizar el resultado antes de descargarlo.',
                    'Enviar el caso a revisión cuando los cuatro documentos requeridos estén generados y descargados.',
                ],
                'pasos' => [
                    'Abre Generar documentos y localiza el expediente por trabajador o código.',
                    'Entra al espacio documental que vas a completar y revisa los datos ya incorporados.',
                    'Completa los campos editables; guarda el borrador si aún no está listo para generar.',
                    'Cuando esté revisado, genera el documento y usa la previsualización para verificar nombres y contenido.',
                    'Descarga el archivo si necesitas la copia. Repite el flujo en los demás espacios.',
                ],
                'consejos' => [
                    'Guardar borrador, generar y descargar son acciones diferentes; verifica el estado de cada formato.',
                    'Generar no descarga automáticamente el archivo.',
                    'La primera descarga puede ser libre; descargas posteriores pueden requerir permiso.',
                    'Editar un documento ya generado requiere el permiso Editar documento generado.',
                    'Si el documento requiere firma, sigue la indicación del formato antes de enviarlo a revisión.',
                ],
                'resultado' => 'Los formatos quedarán preparados con su estado visible; al completar los cuatro requeridos y descargarlos podrás enviar el expediente a revisión.',
            ],
            [
                'id' => 'reincidencias',
                'titulo' => 'Reincidencias',
                'descripcion' => 'Consulta el historial de expedientes agrupado por trabajador.',
                'icono' => 'fas fa-history',
                'color' => 'purple',
                'grupo' => 'Procesos',
                'ruta' => route('abogado.reincidencias'),
                'accion' => 'Consultar reincidencias',
                'permiso' => 'ver_reincidencias',
                'antes' => 'Usa un nombre o número de identificación confiable. La identificación suele ser el dato más preciso para distinguir homónimos.',
                'funciones' => [
                    'Consultar los expedientes agrupados por trabajador según los datos guardados.',
                    'Revisar fechas, estados y cantidad de procesos asociados.',
                    'Abrir un expediente del historial para verificar el antecedente original.',
                ],
                'pasos' => [
                    'Abre Reincidencias desde el menú lateral.',
                    'Busca por nombre o identificación y confirma que los datos correspondan al trabajador.',
                    'Revisa el grupo de procesos y sus estados.',
                    'Abre el caso que necesitas validar; vuelve al listado para comparar otros registros.',
                ],
                'consejos' => [
                    'La búsqueda depende de que el nombre o identificación estén escritos correctamente en los expedientes.',
                    'La consulta se limita a los casos que tu cuenta puede ver.',
                    'Los resultados se muestran paginados; revisa las páginas siguientes si no ves al trabajador.',
                    'Usa la coincidencia como ayuda de revisión, no como sustituto de verificar la identidad.',
                ],
                'resultado' => 'Podrás revisar antecedentes relacionados y comprobarlos abriendo el expediente correspondiente.',
            ],
            [
                'id' => 'plazos',
                'titulo' => 'Seguimiento de descargos',
                'descripcion' => 'Registra la presentación de descargos y revisa el vencimiento calculado de los expedientes.',
                'icono' => 'far fa-clock',
                'color' => 'red',
                'grupo' => 'Control',
                'ruta' => route('abogado.plazos'),
                'accion' => 'Abrir seguimiento de descargos',
                'permiso' => 'ver_plazos',
                'antes' => 'Ten identificada la fecha de creación del expediente y el soporte de la presentación, si existe. El plazo de referencia se calcula desde la creación del caso.',
                'funciones' => [
                    'Separar casos vigentes, próximos a vencer, vencidos y ya presentados.',
                    'Consultar modalidad de presentación, fecha de vencimiento y fecha registrada de presentación.',
                    'Registrar si los descargos fueron presenciales o virtuales y adjuntar soportes cuando tengas permiso.',
                ],
                'pasos' => [
                    'Abre Seguimiento de descargos y empieza por Vencido o Por vencer para priorizar.',
                    'Revisa trabajador, número de proceso, modalidad, vencimiento y estado.',
                    'En la fila del expediente, registra Presencial o Virtual cuando corresponda.',
                    'Adjunta el soporte si lo tienes y cuentas con permiso para subir anexos; confirma la acción.',
                    'Comprueba que el estado cambie a Presentado y revisa la fecha que quedó guardada.',
                ],
                'consejos' => [
                    'El sistema cuenta cinco días desde la creación; el día límite sigue vigente y pasa a vencido al día siguiente si no se registra presentación.',
                    'La fecha guardada indica cuándo se registró en SIPD; los datos históricos sin fecha no permiten confirmar si se presentó dentro del plazo.',
                    'Marcar Presencial o Virtual registra la modalidad, no reemplaza los soportes ni exige evidencia por sí solo.',
                    'Permisos: Ver plazos para abrir el módulo; Registrar procesos para la primera presentación; Editar datos del expediente para cambios posteriores; Subir anexos para adjuntar evidencia.',
                    'Si te falta un permiso, solicítalo desde la barra superior. El acceso Ver plazos lo habilita la coordinadora desde Equipo y permisos.',
                ],
                'resultado' => 'El expediente mostrará la modalidad y fecha registradas; el semáforo indicará si la presentación quedó antes o después del vencimiento cuando existe fecha.',
            ],
            [
                'id' => 'anexos',
                'titulo' => 'Anexos escaneados',
                'descripcion' => 'Administra archivos adjuntos y documentos firmados asociados a los expedientes.',
                'icono' => 'fas fa-file-upload',
                'color' => 'teal',
                'grupo' => 'Documentación',
                'ruta' => route('abogado.anexos'),
                'accion' => 'Abrir anexos',
                'permiso' => 'ver_anexos',
                'antes' => 'Confirma el número del expediente y prepara el archivo que vas a cargar. Verifica que el documento pertenezca a ese trabajador y proceso.',
                'funciones' => [
                    'Buscar anexos por expediente y revisar su tipo, nombre y estado.',
                    'Subir soportes o copias firmadas y asociarlos al caso correspondiente.',
                    'Descargar, reemplazar, editar datos del anexo o eliminarlo según los permisos habilitados.',
                ],
                'pasos' => [
                    'Abre Anexos escaneados y busca el expediente.',
                    'Selecciona el tipo de archivo y carga el documento en el control correspondiente.',
                    'Espera la confirmación y revisa el nombre del archivo en la lista del expediente.',
                    'Para consultar o retirar un archivo, usa la acción de esa fila y confirma el caso antes de hacerlo.',
                ],
                'consejos' => [
                    'El formulario informa los tipos y tamaños admitidos; archivos no permitidos serán rechazados.',
                    'Ver la lista no concede automáticamente permisos para subir, descargar, reemplazar o eliminar.',
                    'Cada acción requiere su permiso: Subir anexos, Descargar anexos, Editar anexos o Eliminar anexos.',
                    'Si no aparece una acción, solicítala en la barra superior; el acceso principal Ver anexos lo habilita la coordinadora.',
                ],
                'resultado' => 'El archivo quedará asociado al expediente y visible con su nombre y estado.',
            ],
            [
                'id' => 'reportes',
                'titulo' => 'Estadísticas y reportes',
                'descripcion' => 'Consulta indicadores y prepara informes de los expedientes visibles.',
                'icono' => 'fas fa-chart-bar',
                'color' => 'blue',
                'grupo' => 'Análisis',
                'ruta' => route('abogado.reportes'),
                'accion' => 'Abrir reportes',
                'permiso' => 'ver_reportes',
                'antes' => 'Define qué período o aspecto del trabajo necesitas revisar. Los informes solo incluyen datos dentro del alcance permitido para tu cuenta.',
                'funciones' => [
                    'Consultar indicadores, gráficos y listados agregados de expedientes.',
                    'Aplicar filtros disponibles para comparar períodos, estados u otras dimensiones.',
                    'Exportar los resultados en formatos disponibles cuando la cuenta tenga el permiso.',
                ],
                'pasos' => [
                    'Abre Estadísticas / Reportes.',
                    'Selecciona el rango de fechas y los demás filtros que necesites.',
                    'Actualiza o consulta los resultados y revisa que el período sea el esperado.',
                    'Si necesitas un archivo, usa la opción de exportación y elige el formato disponible.',
                ],
                'consejos' => [
                    'Ver reportes y exportarlos son permisos distintos; la exportación se solicita aparte.',
                    'Si aparece un bloqueo al exportar, solicita Exportar reportes desde la barra superior.',
                    'Los resultados se limitan a los expedientes que tu perfil puede consultar.',
                ],
                'resultado' => 'Tendrás los indicadores filtrados en pantalla o un archivo exportado si tienes ese permiso.',
            ],
            [
                'id' => 'veredictos',
                'titulo' => 'Veredictos',
                'descripcion' => 'Revisa expedientes en proceso y registra la decisión de la coordinadora.',
                'icono' => 'fas fa-gavel',
                'color' => 'purple',
                'grupo' => 'Coordinadora',
                'ruta' => route('coordinadora.veredictos'),
                'accion' => 'Abrir veredictos',
                'solo_coordinadora' => true,
                'antes' => 'Abre cada expediente y verifica antecedentes, soportes y responsable antes de registrar una decisión. El veredicto es definitivo.',
                'funciones' => [
                    'Consultar expedientes En proceso que esperan decisión.',
                    'Registrar sanción con días, archivar el caso o indicar terminación por justa causa.',
                    'Guardar la justificación y enviar un aviso al responsable del expediente para que comunique el resultado.',
                ],
                'pasos' => [
                    'Abre Veredictos desde el menú o Inicio y localiza el expediente.',
                    'Abre Ver expediente para revisar el historial antes de decidir.',
                    'Selecciona el tipo de resultado; si es sanción, indica los días.',
                    'Escribe la justificación cuando sea requerida y pulsa Registrar veredicto.',
                    'Confirma la acción definitiva y revisa el mensaje de éxito.',
                ],
                'consejos' => [
                    'Sanción y terminación por justa causa quedan en estado Sancionado; archivo queda Archivado.',
                    'La sanción exige una cantidad positiva de días y la justificación requerida por el formulario.',
                    'La decisión no se puede cambiar desde esta cola una vez registrada.',
                    'El aviso es interno para el abogado/RH asignado; no se envía directamente al trabajador.',
                ],
                'resultado' => 'El expediente quedará cerrado con el resultado y el responsable recibirá un aviso interno para comunicarlo al trabajador.',
            ],
            [
                'id' => 'solicitudes',
                'titulo' => 'Solicitudes de permisos',
                'descripcion' => 'La coordinadora resuelve solicitudes de acceso presentadas por el equipo.',
                'icono' => 'fas fa-key',
                'color' => 'orange',
                'grupo' => 'Coordinadora',
                'ruta' => route('coordinadora.solicitudes'),
                'accion' => 'Revisar solicitudes',
                'solo_coordinadora' => true,
                'antes' => 'Revisa las solicitudes pendientes y confirma que la cuenta destinataria sigue siendo la correcta antes de otorgar acceso.',
                'funciones' => [
                    'Leer quién solicita, qué función realizará, su motivo y duración solicitada.',
                    'Aprobar o rechazar una acción concreta y definir su duración cuando corresponda.',
                    'Consultar el historial de decisiones y retirar solicitudes resueltas según las opciones disponibles.',
                ],
                'pasos' => [
                    'Abre Solicitudes desde el menú o el acceso de pendientes de Inicio.',
                    'Lee los campos Qué va a hacer y Por qué; confirma el permiso y las horas solicitadas.',
                    'Si otorgas, revisa que el permiso seleccionado sea el solicitado y define una duración adecuada.',
                    'Si no corresponde, selecciona Rechazar. Confirma la decisión.',
                    'Comprueba que la solicitud pase al historial.',
                ],
                'consejos' => [
                    'Otorga solo el permiso necesario para la tarea. Descargar es independiente de ver.',
                    'Una solicitud pendiente no concede acceso; el permiso entra en vigor después de aprobarse.',
                    'Algunos permisos pueden otorgarse por tiempo limitado; revisa la duración elegida.',
                    'El acceso principal a ciertos módulos se gestiona en Equipo y permisos, no como solicitud.',
                ],
                'resultado' => 'La solicitud queda resuelta y la cuenta recibe la respuesta con el permiso o rechazo.',
            ],
            [
                'id' => 'equipo',
                'titulo' => 'Equipo y permisos',
                'descripcion' => 'Administra las cuentas del equipo, sus accesos y asignaciones.',
                'icono' => 'fas fa-user-tie',
                'color' => 'green',
                'grupo' => 'Coordinadora',
                'ruta' => route('coordinadora.abogados'),
                'accion' => 'Administrar equipo',
                'solo_coordinadora' => true,
                'antes' => 'Antes de cambiar una cuenta, identifica al integrante correcto y define qué funciones necesita para su trabajo.',
                'funciones' => [
                    'Crear y actualizar las cuentas, datos de contacto y cargo del equipo.',
                    'Activar o desactivar cuentas y administrar permisos generales, acciones y modalidades.',
                    'Revisar carga de trabajo y asignar expedientes al integrante responsable.',
                ],
                'pasos' => [
                    'Abre Equipo y permisos y busca al integrante por nombre o correo.',
                    'Selecciona editar para actualizar sus datos o inicia el alta si es una cuenta nueva.',
                    'Revisa permisos por módulo, acciones y modalidades; habilita solo lo que requiere el cargo.',
                    'Para asignar un proceso, selecciona el expediente y el usuario responsable.',
                    'Guarda y confirma el mensaje del sistema.',
                ],
                'consejos' => [
                    'Ver un módulo no implica poder editar, borrar o descargar; asigna cada acción necesaria por separado.',
                    'Las modalidades determinan qué tipos de proceso puede registrar la cuenta.',
                    'Desactivar una cuenta y eliminarla son acciones distintas; el sistema conserva historial de expedientes.',
                    'Después de asignar un caso, verifica el responsable mostrado en el expediente.',
                ],
                'resultado' => 'La cuenta tendrá los accesos definidos y los casos asignados al responsable correcto.',
            ],
            [
                'id' => 'recuperaciones',
                'titulo' => 'Recuperación de contraseñas',
                'descripcion' => 'Atiende solicitudes de recuperación de cuentas activas del equipo.',
                'icono' => 'fas fa-unlock-alt',
                'color' => 'teal',
                'grupo' => 'Coordinadora',
                'ruta' => route('coordinadora.recuperaciones'),
                'accion' => 'Revisar recuperaciones',
                'solo_coordinadora' => true,
                'antes' => 'Confirma que la solicitud corresponde a la cuenta correcta y que el integrante está activo.',
                'funciones' => [
                    'Revisar solicitudes pendientes de recuperación y su historial.',
                    'Enviar al correo registrado un enlace de restablecimiento de un solo uso.',
                    'Rechazar solicitudes que no correspondan.',
                ],
                'pasos' => [
                    'Abre Recuperación de contraseñas desde el menú o Inicio.',
                    'Comprueba nombre, correo y estado de la cuenta.',
                    'Envía el enlace si la solicitud es válida o recházala si no corresponde.',
                    'Informa al usuario que debe revisar el correo asociado y usar el enlace recibido.',
                ],
                'consejos' => [
                    'No se puede emitir el enlace desde este módulo para una cuenta inactiva.',
                    'El enlace es de un solo uso y permite al usuario establecer su propia contraseña.',
                    'No solicites ni compartas la nueva contraseña del integrante.',
                ],
                'resultado' => 'La persona recibirá el enlace para restablecer su contraseña o verá la solicitud rechazada.',
            ],
            [
                'id' => 'avisar-equipo',
                'titulo' => 'Avisar al equipo',
                'descripcion' => 'Envía una comunicación interna a uno o varios integrantes.',
                'icono' => 'far fa-paper-plane',
                'color' => 'blue',
                'grupo' => 'Coordinadora',
                'ruta' => route('coordinadora.notificar'),
                'accion' => 'Enviar aviso',
                'solo_coordinadora' => true,
                'antes' => 'Prepara un mensaje concreto y define quién necesita recibirlo. Asocia un expediente solo si el aviso se refiere a ese caso.',
                'funciones' => [
                    'Enviar una comunicación interna a uno o varios integrantes.',
                    'Especificar título, motivo y cuerpo del mensaje.',
                    'Vincular opcionalmente el aviso a un expediente.',
                ],
                'pasos' => [
                    'Abre Avisar al equipo.',
                    'Selecciona uno o más destinatarios.',
                    'Escribe un título claro, explica el motivo y redacta el mensaje.',
                    'Si aplica, selecciona el expediente relacionado.',
                    'Revisa destinatarios y contenido antes de enviar.',
                ],
                'consejos' => [
                    'El aviso queda en la bandeja de cada destinatario; no equivale a un correo electrónico.',
                    'No asocies un expediente si el mensaje no trata sobre ese caso.',
                    'Usa un texto profesional, específico y con una acción esperada.',
                ],
                'resultado' => 'El mensaje aparecerá en la bandeja interna de los integrantes seleccionados.',
            ],
        ];

        $accionesPorModulo = [
            'perfil' => ['editar_perfil'],
            'mis-casos' => ['editar_casos', 'eliminar_casos'],
            'nuevo-proceso' => ['registrar_casos'],
            'documentos' => ['editar_documentos', 'generar_documentos', 'editar_generados', 'descargar_documentos'],
            'plazos' => ['registrar_casos', 'editar_casos', 'subir_anexos'],
            'anexos' => ['subir_anexos', 'editar_anexos', 'descargar_anexos', 'eliminar_anexos'],
            'reportes' => ['exportar_reportes'],
        ];
        $solicitables = array_keys(RhPermisos::solicitables());

        foreach ($modulos as &$modulo) {
            $permisoModulo = $modulo['permiso'] ?? null;
            $moduloHabilitado = !$permisoModulo || $user->puede($permisoModulo);
            $acciones = [];

            foreach ($esCoordinadora ? [] : ($accionesPorModulo[$modulo['id']] ?? []) as $permiso) {
                $acciones[] = [
                    'permiso' => $permiso,
                    'nombre' => RhPermisos::etiqueta($permiso),
                    'detalle' => RhPermisos::hint($permiso),
                    'habilitado' => $user->puede($permiso),
                    'solicitable' => in_array($permiso, $solicitables, true),
                ];
            }

            $modulo['acceso'] = [
                'etiqueta' => $esCoordinadora
                    ? (!empty($modulo['solo_coordinadora']) ? 'Administración coordinadora' : 'Según perfil')
                    : ($permisoModulo ? 'Acceso controlado' : 'Disponible por perfil'),
                'descripcion' => $esCoordinadora
                    ? 'Las funciones disponibles dependen del rol de coordinadora.'
                    : ($permisoModulo
                        ? 'El acceso principal de este módulo depende del permiso ' . RhPermisos::etiqueta($permisoModulo) . '. La coordinadora lo administra desde Equipo y permisos.'
                        : 'El módulo está disponible para tu perfil; algunas acciones pueden requerir permisos adicionales.'),
                'permiso_modulo' => $permisoModulo
                    ? RhPermisos::etiqueta($permisoModulo)
                    : null,
                'modulo_habilitado' => $moduloHabilitado,
                'permiso_modulo_solicitable' => $permisoModulo && in_array($permisoModulo, $solicitables, true),
                'acciones' => $acciones,
                'requiere_solicitud' => !$esCoordinadora && (
                    (!$moduloHabilitado && $permisoModulo && in_array($permisoModulo, $solicitables, true))
                    || collect($acciones)->contains(
                        fn (array $accion) => !$accion['habilitado'] && $accion['solicitable']
                    )
                ),
            ];
        }
        unset($modulo);

        $modulos = array_values(array_filter($modulos, function (array $modulo) use ($user, $esCoordinadora) {
            if (!empty($modulo['solo_coordinadora']) && !$esCoordinadora) {
                return false;
            }

            if (!empty($modulo['solo_equipo']) && $esCoordinadora) {
                return false;
            }

            return true;
        }));

        return view('manual.index', [
            'pageTitle' => 'Manual de usuario',
            'modulos' => $modulos,
            'esCoordinadora' => $esCoordinadora,
            'rolManual' => $esCoordinadora ? 'Coordinadora de RH' : 'Equipo de RH',
        ]);
    }
}
