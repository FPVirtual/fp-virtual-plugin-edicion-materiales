<?php
// Script de diagnóstico para verificar la sincronización entre local_educa_editables
// y los course_modules reales de un curso.

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

$shortname = cli_input('Introduce el shortname del curso (ej: 50020125-IFC303-16805): ');

$course = $DB->get_record('course', ['shortname' => $shortname]);
if (!$course) {
    cli_error('Curso no encontrado: ' . $shortname);
}

cli_writeln('');
cli_writeln('═══════════════════════════════════════════════════════════════');
cli_writeln(' DIAGNÓSTICO DE EDITABLES PARA CURSO: ' . $shortname);
cli_writeln(' Course ID: ' . $course->id);
cli_writeln('═══════════════════════════════════════════════════════════════');
cli_writeln('');

// 1. Recursos registrados en local_educa_editables
cli_writeln('1. Registros en local_educa_editables:');
$editables = $DB->get_records('local_educa_editables', ['courseid' => $course->id]);
if (empty($editables)) {
    cli_writeln('   (Ninguno)');
} else {
    foreach ($editables as $e) {
        $cm = $DB->get_record('course_modules', [
            'instance' => $e->resourceid,
            'course'   => $course->id,
            'module'   => $DB->get_field('modules', 'id', ['name' => 'resource'])
        ]);
        $status = $cm ? ('cmid=' . $cm->id . ' visible=' . $cm->visible) : 'CM NO ENCONTRADO EN ESTE CURSO';
        cli_writeln(sprintf(
            '   - editable.id=%d | resourceid=%d | type=%s | version=%s | relatedcmid=%s | %s',
            $e->id,
            $e->resourceid,
            $e->type,
            $e->version,
            $e->relatedcmid ?? 'NULL',
            $status
        ));
    }
}

// 2. Todos los course_modules de tipo resource en el curso
cli_writeln('');
cli_writeln('2. Todos los course_modules de tipo "resource" en el curso:');
$resourcemoduleid = $DB->get_field('modules', 'id', ['name' => 'resource']);
$allcms = $DB->get_records('course_modules', ['course' => $course->id, 'module' => $resourcemoduleid]);
foreach ($allcms as $cm) {
    $resource = $DB->get_record('resource', ['id' => $cm->instance], 'id, name');
    $name = $resource ? $resource->name : 'NAME NOT FOUND';
    $ineditables = $DB->record_exists('local_educa_editables', [
        'courseid' => $course->id,
        'resourceid' => $cm->instance
    ]);
    cli_writeln(sprintf(
        '   - cmid=%d | instance=%d | visible=%d | name="%s" | %s',
        $cm->id,
        $cm->instance,
        $cm->visible,
        $name,
        $ineditables ? 'REGISTRADO en local_educa_editables' : 'NO registrado'
    ));
}

// 3. Verificar si hay course_modules con la misma instance en OTROS cursos
cli_writeln('');
cli_writeln('3. Búsqueda global (sin filtro de curso) para las instances de local_educa_editables:');
foreach ($editables as $e) {
    $cms = $DB->get_records('course_modules', ['instance' => $e->resourceid]);
    foreach ($cms as $cm) {
        $othercourse = $DB->get_field('course', 'shortname', ['id' => $cm->course]);
        cli_writeln(sprintf(
            '   - instance=%d encontrado en cmid=%d del curso "%s" (id=%d)',
            $e->resourceid,
            $cm->id,
            $othercourse,
            $cm->course
        ));
    }
}

cli_writeln('');
cli_writeln('═══════════════════════════════════════════════════════════════');
cli_writeln(' FIN DEL DIAGNÓSTICO');
cli_writeln('═══════════════════════════════════════════════════════════════');
