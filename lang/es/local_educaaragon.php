<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package local_educaaragon
 * @author 3iPunt <https://www.tresipunt.com/>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 3iPunt <https://www.tresipunt.com/>
 */

$string['pluginname'] = 'Educa Aragón';
$string['educaaragon:manageall'] = 'Manejar plugin local_educaaragon';
$string['educaaragon:editresources'] = 'Editar recursos editables en el módulo';

$string['generalconfig'] = 'Configuración general';
$string['activetask'] = 'Activar tarea programada para transformar recursos';
$string['activetask_desc'] = 'Si se activa, una tarea programada del cron de Moodle recorrerá los módulos buscando contenidos SCORM e IMS para transformarlo';
$string['repository'] = 'Repositorio de contenidos';
$string['repository_desc'] = 'Seleccione el repositorio del tipo "filesystem" donde están almacenados todos los contenidos dinámicos en formato HTML. Si no existe ninguno, tendrá que crear uno y almacenar los contenidos en el. Los contenidos de cada módulo deberán estar almacenados en carpetas nombradas con el nombre corto del módulo para hacer la relación.';
$string['repositoryroot'] = 'raíz del repositorio';
$string['sourcefolder'] = 'Carpeta de contenidos fuente';
$string['sourcefolder_desc'] = 'Nombre de la carpeta, dentro de la raíz del repositorio filesystem, donde están los contenidos originales de los módulos. Deje este campo vacío si las carpetas de los módulos están directamente en la raíz del repositorio (por ejemplo, <code>&lt;raíz_repo&gt;/50020125-IFC303-16805/01/index.html</code>). Escriba <code>recursos-editables</code> si la estructura es <code>&lt;raíz_repo&gt;/recursos-editables/50020125-IFC303-16805/01/index.html</code>. Las versiones editadas siempre se guardan en <code>&lt;raíz_repo&gt;/editions/&lt;shortname&gt;/&lt;resourceid&gt;/&lt;version&gt;/</code>.';
$string['no_repository_exists'] = 'No existe ningún repositorio del tipo filesystem. Se necesita un repositorio con los contenidos de los módulos en HTML. Consulte con un desarrollador.';
$string['no_repository_select'] = 'No se ha seleccionado ningún repositorio en la configuración del plugin. Seleccione un repositorio antes de poder ejecutar la tarea.';
$string['allcourses'] = 'Aplicar a todos los módulos';
$string['allcourses_desc'] = 'Si está activada, la tarea programada aplicará a todos los módulos de la plataforma';
$string['category'] = 'Categoría';
$string['category_desc'] = 'Seleccione la categoría donde se aplicará la transformación de SCORMS e IMS a recursos. Todos los módulos contenidos en esta categoría se verán afectados, incluyendo los que estén en subcategorías';
$string['transformdynamiccontent'] = 'Tarea de transformación de contenidos dinámicos';
$string['course_processed'] = 'Módulo procesado. Tiempo empleado: ';
$string['memory_used'] = 'Memoria utilizada: ';
$string['allcourses_processed'] = 'Todos los módulos procesados. Tiempo empleado: ';
$string['printable'] = 'imprimible';

$string['transform_dynamic_content_desc'] = 'Tarea para transformar SCORMS e IMS en recursos HTML y en su versión para imprimir. Después de pasar esta tarea, se podrán editar los contenidos de los módulos afectados.';
$string['notactivetask'] = 'Se ha desactivado la tarea programada desde configuración. No se modificará ningún módulo.';
$string['coursesfound'] = 'Se van a procesar {$a} módulos';
$string['processcourse'] = 'Procesando módulo {$a->shortname} con ID {$a->courseid}';
$string['errorprocesscourse'] = 'Error procesando módulo. Revise los contenidos correspondientes al módulo en el repositorio';
$string['errorprocesscourse_desc'] = 'Error procesando módulo {$a->course}: {$a->error}';
$string['error/invalidpersistenterror'] = 'Hay errores de carácteres inválidos en los enlaces<br>error/invalidpersistenterror';
$string['error/invalidfilerequested'] = 'Hay recursos que contienen directorios, o archivos no válidos en su contenido<br>error/invalidfilerequested';
$string['dynamiccontent_found'] = 'Encontrados {$a} contenidos dinámicos';
$string['editionsfolder_found'] = 'Módulo {$a} ya procesado previamente. Se reconocerán las versiones existentes.';
$string['importededitions_start'] = 'Importando versiones editadas existentes para el módulo {$a}...';
$string['importededitions_result'] = 'Importación de versiones finalizada. Recursos migrados: {$a->resources}. Versiones copiadas: {$a->versions}. Versiones aplicadas: {$a->applied}. Omitidas: {$a->skipped}. Errores: {$a->errors}.';
$string['recognize_resource_notfound'] = 'No se ha encontrado el módulo del recurso editable {$a}. Se omite el reconocimiento de su versión original.';
$string['no_resourcegenerator'] = 'No existe generador de recursos en este entorno, por lo que la tarea no puede continuar. Contacte con un desarrollador.';
$string['no_associated_folder'] = 'No se ha encontrado la carpeta {$a->folder}/{$a->course} en el repositorio {$a->repository}';
$string['elements_does_not_match'] = 'El número de recursos dinámicos del módulo {$a->course} no coincide con el número de recursos asociados en el repositorio {$a->repository}. Este proceso no modificará nada en el módulo hasta que esto se resuelva.';
$string['elements_cant_associate'] = 'No se han podido asociar los contenidos del módulo {$a->course} con los contenidos del repositorio {$a->repository}. Por favor, revise los títulos de los recursos y la nomenclatura del contenido del repositorio. La numeración ha de ser 01, 02, 03, etc.';
$string['error_copy_files'] = 'Error al copiar los archivos del módulo {$a->course}. Origen: {$a->origen} - Destino: {$a->destiny}. Resuelva esto antes de volver a ejecutar la tarea.';
$string['no_index_file'] = 'No se ha encontrado un archivo index.html en el recurso {$a->cmname} del módulo {$a->course}. El proceso no continuará para este módulo.';
$string['editable_filearea_empty'] = 'El recurso editable {$a->cmname} (módulo {$a->course}) no tiene contenido en Moodle, por lo que no se ha podido crear o reconstruir su versión original. Elimine el recurso y vuelva a procesar el módulo.';
$string['version_folder_empty'] = 'La versión {$a->version} del recurso {$a->cmname} (módulo {$a->course}) no contiene archivos, por lo que no se puede aplicar.';
$string['processlink_error'] = 'Error procesando los enlaces del recurso {$a->resourceid}: {$a->error}';
$string['correctly_processed'] = 'Módulo procesado correctamente';
$string['correctly_processed_needassociation'] = 'Módulo procesado correctamente. Necesita ordenación manual de recursos editables';
$string['selected_for_reprocessing'] = 'Seleccionado para volver a procesarse en la siguiente ejecución de la tarea';
$string['resource_deleted'] = 'Se ha eliminado uno o varios recursos editable de este módulo. Se recomienda volver a procesar.';
$string['processresource'] = 'Contenido creado en módulo ';
$string['processlink'] = 'Procesado de enlaces de recurso ';

// Launch task
$string['launchtask'] = 'Ejecución manual de tareas';
$string['launchtask_desc'] = 'Permite ejecutar manualmente las tareas del plugin: la generación de materiales editables y la importación de versiones de materiales, para todos los módulos, para un módulo concreto o para un centro completo. Ambas se encolan como tareas en segundo plano y generan un documento de log en la carpeta logs/ de la raíz del repositorio.';
$string['launchtask_task'] = 'Tarea a ejecutar';
$string['launchtask_task_generate'] = 'Generación de materiales editables';
$string['launchtask_task_generate_desc'] = 'Crea los recursos editables e imprimibles de los módulos a partir de sus contenidos dinámicos (SCORM/IMSCP).';
$string['launchtask_task_migrate'] = 'Importación de versiones de materiales';
$string['launchtask_task_migrate_desc'] = 'Copia las versiones editadas guardadas bajo identificadores antiguos hacia los recursos actuales de los módulos.';
$string['launchtask_migrate_requirement'] = 'Atención: la importación de versiones requiere que la generación de materiales editables se haya ejecutado correctamente con anterioridad sobre los módulos afectados, ya que empareja las versiones con los recursos existentes.';
$string['launchtask_migrationoptions'] = 'Opciones de la importación';
$string['launchtask_applyversion'] = 'Aplicar una versión tras importar';
$string['launchtask_applyversion_desc'] = 'Por defecto la importación no aplica ninguna versión: los recursos recién creados ya muestran a los estudiantes el contenido original. Marque esta opción solo si quiere que se aplique una versión migrada.';
$string['launchtask_applyversion_name'] = 'Nombre de la versión a aplicar';
$string['launchtask_applyversion_name_desc'] = 'Escriba el nombre exacto de la versión (p. ej. v1_2025-2026).';
$string['launchtask_includeoriginal'] = 'También copiar la carpeta «original»';
$string['launchtask_dryrun'] = 'Simulación (no aplicar cambios)';
$string['launchtask_logfile'] = 'Log de la ejecución guardado en:';
$string['launchtask_migration_coursenotfound'] = 'Módulo no encontrado en Moodle: {$a}';
$string['launchtask_migration_noeditions'] = 'No existe la carpeta editions/ para el módulo: {$a}';
$string['launchtask_scope_missing'] = 'Seleccione el ámbito de ejecución.';
$string['launchtask_scope'] = 'Ámbito de ejecución';
$string['launchtask_all'] = 'Procesar todos los módulos';
$string['launchtask_all_desc'] = 'Se procesarán todos los módulos no procesados según la configuración actual (todos los módulos o la categoría seleccionada).';
$string['launchtask_single'] = 'Procesar un módulo concreto';
$string['launchtask_single_desc'] = 'Se procesará únicamente el módulo seleccionado.';
$string['launchtask_center'] = 'Procesar un centro completo';
$string['launchtask_center_desc'] = 'Se procesarán todos los módulos no procesados cuyo código de centro (primer tramo del nombre corto) coincida con el indicado, p. ej. 50020125.';
$string['launchtask_centercode'] = 'Código de centro';
$string['launchtask_center_empty'] = 'Debe indicar un código de centro.';
$string['launchtask_center_none'] = 'No se han encontrado módulos sin procesar para el centro indicado.';
$string['launchtask_course'] = 'Módulo';
$string['launchtask_selectcourse'] = 'Seleccione un módulo';
$string['launchtask_searchcourse'] = 'Buscar módulo';
$string['launchtask_execute'] = 'Ejecutar';
$string['launchtask_result'] = 'Resultado de la ejecución';
$string['launchtask_course_processed_warning'] = 'Este módulo ya ha sido procesado.';
$string['launchtask_reprocess'] = 'Reprocesar módulo';
$string['launchtask_reprocess_confirm'] = 'El módulo seleccionado ya ha sido procesado. Para volver a procesarlo se eliminarán los recursos generados anteriormente. ¿Desea continuar?';
$string['launchtask_course_notfound'] = 'No se ha encontrado el módulo seleccionado.';
$string['launchtask_execution_finished'] = 'Ejecución finalizada.';
$string['launchtask_all_none'] = 'No hay módulos pendientes de procesar.';
$string['launchtask_queued'] = 'Procesamiento de {$a} encolado: se ejecutará en segundo plano la próxima ejecución del cron. El log quedará guardado en la carpeta logs/ de la raíz del repositorio de materiales.';
$string['processcourses_task'] = 'Generar materiales editables de cursos';
$string['migrateversionstask'] = 'Importar versiones de materiales de cursos';
$string['errorprocessingnotwritable'] = 'La carpeta fileprocessing no es escribible por el usuario del web: {$a}';
$string['tasksummary_scope'] = 'Cursos en el ámbito: {$a}';
$string['tasksummary_ok'] = 'Procesados correctamente: {$a}';
$string['tasksummary_failed'] = 'Fallidos: {$a}';
$string['tasksummary_failedcourse'] = '{$a->shortname} (id={$a->courseid}): {$a->error}';
$string['tasksummary_elapsed'] = 'Tiempo total de ejecución: {$a}';
$string['tasksummary_peakmemory'] = 'Memoria pico utilizada: {$a}';
$string['tasksummary_coursenotfound'] = 'Módulo no encontrado (id={$a}). Se omite.';
$string['launchtask_center_notfound'] = 'No existe ningún módulo con el código de centro indicado: {$a}';
$string['launchtask_migration_centernone'] = 'No se han encontrado carpetas de ediciones para el centro indicado: {$a}';
$string['logs'] = 'Logs de ejecución';
$string['logs_desc'] = 'Documentos de log generados por las ejecuciones de generación de materiales e importación de versiones. Se guardan en la carpeta logs/ de la raíz del repositorio de materiales.';
$string['logs_empty'] = 'Todavía no se ha generado ningún log de ejecución.';
$string['logs_file'] = 'Fichero';
$string['logs_modified'] = 'Fecha';
$string['logs_size'] = 'Tamaño';
$string['logs_download'] = 'Descargar';
$string['logs_back'] = 'Volver al listado de logs';
$string['logs_notfound'] = 'No se ha encontrado el fichero de log indicado.';

// Tables
$string['processedcourses'] = 'Módulos procesados';
$string['processedcourses_help'] = 'Listado de los módulos procesados por la tarea <b>local_educaaragon\task\transform_dynamic_content</b>.<br>Desde este panel podrá gestionar los módulos que necesite que se vuelvan a procesar en la siguiente ejecución de la tarea.';
$string['courseid'] = 'Id de módulo';
$string['coursename'] = 'Nombre completo';
$string['shortname'] = 'Nombre corto';
$string['processed'] = 'Procesado';
$string['message'] = 'Mensaje';
$string['usermodified'] = 'Usuario';
$string['timemodified'] = 'Fecha de modificación';
$string['actions'] = 'Acciones';
$string['reprocessing'] = 'Reprocesar módulo en la próxima ejecución';
$string['reprocessingmsg'] = '<p>Esta acción marcará este módulo para que se vuelva a procesar en la próxima ejecución de la tarea programada <b>local_educaaragon\task\transform_dynamic_content</b>.</p><h4>¡ATENCIÓN!</h4><h5>Tenga en cuenta que al marcar este módulo para que se vuelva a procesar se eliminarán los recursos que fueron generados anteriormente por la tarea, para evitar que se dupliquen.</h5>';
$string['reprocess'] = 'Reprocesar';
$string['editableresources'] = 'Mostrar la lista de recursos editables generados';
$string['editables'] = 'Recursos editables';
$string['editablematerials'] = 'Materiales Ministerio - Editables';
$string['editables_help'] = 'Listado de recursos disponibles para su edición.<br>Puede filtrar los resultados por módulo añadiendo el parámetro "courseid" a la url.';
$string['resourceid'] = 'ID del recurso';
$string['resourcename'] = 'Nombre del recurso';
$string['viewcourse'] = 'Ver módulo';
$string['backversions'] = 'Volver al selector de versiones';
$string['relatedcmid'] = 'Recurso relacionado';
$string['revieweditableresource'] = 'Ver recurso';
$string['editresource'] = 'Editar recurso';
$string['viewprintresource'] = 'Ver versión para impresión';
$string['vieweditcontent'] = 'Editar contenidos';

// Edit resource
$string['editingresource'] = 'Editando recurso';
$string['resourcenoteditable'] = 'Este recurso no es editable';
$string['versionnoteditable'] = 'Esta versión no se puede editar. Seleccione una versión diferente en <a href="{$a}">{$a}</a>';
$string['selectversion'] = 'Seleccione la versión';
$string['selectsection'] = 'Seleccione el apartado para editar';
$string['createnewversion'] = 'Crear una nueva versión';
$string['createnewversion_desc'] = '¿Seguro que quiere crear una nueva versión para editar?<br>El nombre que ha puesto a la versión será modificado para quitar carácteres especiales y sustituir espacios por _. Si lo ha dejado vacío, se pondrá la fecha en formato Unix como nombre de la versión';
$string['confirm'] = 'Confirmar';
$string['versionname'] = 'Nombre';
$string['loadversion'] = 'Editar version';
$string['deleteversion'] = 'Eliminar version';
$string['deleteversion_desc'] = '¿Seguro que quiere eliminar la versión seleccionada?<br>Tenga en cuenta que si esta versión está aplicada para su visualización, seguirá mostrándose a los usuarios aunque la elimine. Para solucionarlo, aplique otra versión.';
$string['asofversion'] = 'a partir de versión';
$string['versionalreadyexist'] = 'Ya existe una versión con ese nombre';
$string['errorcreateversion'] = 'Se ha producido un error al crear una nueva versión. Compruebe que el nombre no esté repetido ni contenga caráteres especiales y vuelva a intentarlo recargando esta página. Si el problema persiste póngase en contacto con un administrador.';
$string['save_changes'] = 'Guardar cambios';
$string['save_changes_desc'] = '¿Seguro que quiere guardar los cambios aplicados en esta versión? Los cambios se guardarán sobre la versión, no se aplicarán al recurso existente en el módulo.';
$string['changes_saved'] = 'Cambios guardados correctamente: ';
$string['not_saved'] = 'No se han podido guardar los cambios, inténtelo de nuevo: ';
$string['apply_version'] = 'Aplicar versión';
$string['apply_version_desc'] = '¿Seguro que quiere aplicar la versión que está seleccionada al recurso que verán los estudiantes?';
$string['version_saved'] = 'La versión se ha aplicado correctamente: ';
$string['version_not_saved'] = 'No se ha podido aplicar la versión, inténtelo de nuevo: ';
$string['versionprintable_saved'] = 'La versión de impresión se ha aplicado correctamente a partir de la versión editada: ';
$string['versionprintable_not_saved'] = 'No se ha podido aplicar la versión de impresión, inténtelo de nuevo: ';

// Edited resource
$string['registereditions'] = 'Registro de ediciones';
$string['version_created'] = 'Nueva versión creada';
$string['version_created_asofversion'] = 'Creada a partir de la versión: ';
$string['version_deleted'] = 'Versión eliminada';
$string['version_changes_saved'] = 'Cambios guardados';
$string['version_changes_saved_file'] = 'Archivo afectado: ';
$string['version_applied'] = 'Versión aplicada al recurso';
$string['version_printable_applied'] = 'Versión imprimible aplicada al recurso';
$string['version_original_created'] = 'Versión original creada';
$string['action'] = 'Evento';
$string['other'] = 'Información adicional';
$string['version'] = 'Versión';
$string['edit_comments'] = 'Comentarios del editor: ';
$string['write_comment'] = 'Información adicional sobre la edición: ';

// Links
$string['link_report'] = 'Informe de enlaces';
$string['link_report_desc'] = 'En este informe se puede ver información sobre los enlaces que contiene una versión concreta de un recurso editable.';
$string['processresourcelinks'] = 'Buscando enlaces rotos y contenido flash en los recursos';
$string['link_case'] = 'Caso';
$string['link'] = 'Enlace';
$string['video'] = 'Video';
$string['iframe'] = 'Iframe';
$string['file'] = 'Archivo';
$string['link_type'] = 'Tipo de enlace:';
$string['link_text'] = 'Texto del enlace: ';
$string['link_active'] = 'Enlace activo';
$string['link_broken'] = 'Enlace roto';
$string['link_broken_cantfix'] = 'Enlace roto. No se soluciona con https';
$string['link_fixed'] = 'Enlace arreglado con https';
$string['link_flash'] = 'Contenido Flash';
$string['link_notvalid'] = 'La URL parece no válida, y no funciona';
$string['link_notvalid_active'] = 'La URL parece no válida, pero funciona';
$string['link_youtube'] = 'Enlace youtube válido';
$string['link_youtube_fixed'] = 'Enlace youtube arreglado';
$string['link_youtube_broken'] = 'Enlace youtube roto';
$string['showactivelinks'] = 'Mostrar enlaces activos';
$string['hideactivelinks'] = 'Ocultar enlaces activos';
$string['link_broken_afterchangehttps'] = 'Link roto tras aplicar https, funciona con http';
$string['process_resource_links'] = 'Enlaces de recurso procesados';
$string['process_version_links'] = 'Procesar los enlaces de la versión';
$string['process_version_links_desc'] = 'Se procesarán todos los enlaces de esta versión para detectar o arreglar enlaces que no funcionan.<br>Cuando termine el proceso, se le redirigirá al informe de enlaces para esta versión, pero la versión no será aplicada y tendrá que aplicarla manualmente cuando la revise.<br>Este proceso puede tardar varios minutos, y no se detendrá aunque cierre la pestaña (si la cierra no será redirigido al terminar).<br>Todos los registros de procesados de enlaces para esta versión que se hayan generado anteriormente serán eliminados para volver a crearse.<h5>¿Está seguro de procesar los enlaces para esta versión?</h5>';
$string['view_version_links'] = 'Ver registro de enlaces procesados';
$string['processed_resource_links'] = 'Enlaces procesados correctamente: ';
$string['not_processed_resource_links'] = 'No se han podido procesar los enlaces, inténtelo de nuevo más tarde: ';
$string['numfiles'] = 'Archivos: ';
$string['numlinks'] = 'Enlaces: ';
$string['numlinksactive'] = 'Activos: ';
$string['numlinksfixed'] = 'Arreglados: ';
$string['numlinksbroken'] = 'Rotos: ';
$string['numlinksnotvalid'] = 'No válidos: ';
$string['numprocessed'] = 'Procesados: ';
$string['numprocessedcorrectly'] = 'Correctos: ';
$string['numprocessederror'] = 'Errores: ';
$string['numprocessedwarning'] = 'Sin carpeta: ';
$string['nofolder'] = 'Sin carpeta';
$string['changesnotsaved'] = 'Guardar antes de salir';
$string['changesnotsaved_desc'] = 'Se han detectado cambios y es necesario guardar antes de salir.<br>Si no desea guardar, cierre o recargue esta pestaña del navegador.';
$string['savechanges'] = 'Guardar cambios';
$string['haschanges'] = '* Se han realizado cambios';
$string['edittoc'] = 'Edición de TOC';
$string['edittoctitle'] = 'Edición de tabla de contenidos';
$string['toc_list'] = 'Tabla de Contenidos';
$string['toc_list_info'] = 'En este panel puede modificar los títulos y el orden de la tabla de contenidos arrastrando y soltando los títulos para ordenarlos cómo desee, pero tenga en cuenta que la numeración se ha de añadir manualmente, ya que no se ordenará automáticamente (también puede optar por eliminar la numeración, según requiera el contenido).<br><b style="color: red">Asegúrese de que nadie esté editando el mismo recurso al mismo tiempo, ya sea el propio contenido o la tabla de contenidos, ya que se puede llegar a corromper la versión y perder todo el trabajo realizado sobre ésta.</b><br><b style="color: red">El elemento marcado con letra de color rojo corresponde al archivo index.html, por lo que siempre debe ser el primer elemento de la lista, ya que es el que se carga al entrar en el recurso.</b><br><b style="color: red">Los títulos que edite en este panel no serán aplicados al contenido correspondiente, por lo que también se tendrán que modificar en la propia edición de contenidos (en el caso de que tenga título).</b></br><b>Cuando termine, guarde los cambios y aplique la versión en el panel de edición de contenidos para que los estudiantes puedan visualizar los cambios.</b>';
$string['delete_node'] = 'Eliminar nodo';
$string['delete_node_desc'] = 'Se eliminará el nodo seleccionado, y todos sus nodos anidados. ¿Estás seguro de eliminarlo?';
$string['addnewnode'] = 'Añadir nuevo elemento';
$string['more_info'] = 'Más información sobre la interfaz';
$string['content_here'] = '"Aquí su contenido"';