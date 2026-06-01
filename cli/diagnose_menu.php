<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');

echo "=== DIAGNÓSTICO: Menú Contenidos Editables ===" . PHP_EOL . PHP_EOL;

// 1. Plugin instalado
$plugin = $DB->get_record('config_plugins', ['plugin' => 'local_educaaragon', 'name' => 'version']);
echo "1. Plugin version: " . ($plugin ? $plugin->value : 'NO INSTALADO') . PHP_EOL;

// 2. Capacidad asignada a roles
$roles = get_roles_with_capability('local/educaaragon:editresources');
echo "2. Roles con permiso 'local/educaaragon:editresources': " . count($roles) . PHP_EOL;
foreach ($roles as $role) {
    echo "   - {$role->shortname} (id={$role->id})" . PHP_EOL;
}

// 3. Cursos procesados
$processed = $DB->get_records('local_educa_processedcourses', ['processed' => 1]);
echo "3. Cursos marcados como procesados: " . count($processed) . PHP_EOL;

// 4. Cursos con editables
$editables = $DB->get_records_sql("SELECT DISTINCT courseid FROM {local_educa_editables}");
echo "4. Cursos con registros en local_educa_editables: " . count($editables) . PHP_EOL;

// 5. Detalle de un curso de ejemplo
$shortname = '50020125-IFC303-16805';
$course = $DB->get_record('course', ['shortname' => $shortname]);
if ($course) {
    echo PHP_EOL . "5. Detalle del curso {$shortname} (id={$course->id}):" . PHP_EOL;
    $proc = $DB->get_record('local_educa_processedcourses', ['courseid' => $course->id]);
    echo "   - Procesado: " . ($proc ? ($proc->processed ? 'SÍ' : 'NO') : 'NO EXISTE REGISTRO') . PHP_EOL;
    $courseeditables = $DB->get_records('local_educa_editables', ['courseid' => $course->id]);
    echo "   - Registros editables: " . count($courseeditables) . PHP_EOL;
} else {
    echo PHP_EOL . "5. Curso {$shortname}: NO ENCONTRADO" . PHP_EOL;
}

echo PHP_EOL . "=== FIN DIAGNÓSTICO ===" . PHP_EOL;
