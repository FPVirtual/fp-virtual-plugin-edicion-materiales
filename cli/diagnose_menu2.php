<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/navigationlib.php');

echo "=== DIAGNÓSTICO AVANZADO: Menú Contenidos Editables ===" . PHP_EOL . PHP_EOL;

// 1. Listar cursos procesados con detalle
$processed = $DB->get_records('local_educa_processedcourses', ['processed' => 1]);
echo "1. Cursos procesados: " . count($processed) . PHP_EOL;
foreach ($processed as $p) {
    $course = $DB->get_record('course', ['id' => $p->courseid], 'id,shortname,category');
    $editables = $DB->count_records('local_educa_editables', ['courseid' => $p->courseid]);
    $short = $course ? $course->shortname : '???';
    echo "   - id={$p->courseid} shortname={$short} editables={$editables}" . PHP_EOL;
}

// 2. Verificar permisos del admin en un curso específico
$testcourse = $DB->get_record('course', ['shortname' => '50020125-IFC303-16805']);
if ($testcourse) {
    echo PHP_EOL . "2. Verificando curso {$testcourse->shortname} (id={$testcourse->id}):" . PHP_EOL;
    
    $context = context_course::instance($testcourse->id);
    $admin = get_admin();
    $hascap = has_capability('local/educaaragon:editresources', $context, $admin->id);
    echo "   - Admin (id={$admin->id}) tiene editresources: " . ($hascap ? 'SÍ' : 'NO') . PHP_EOL;
    
    // Verificar todos los roles asignados en el curso
    $roles = get_user_roles($context, $admin->id, false);
    echo "   - Roles del admin en este curso:" . PHP_EOL;
    foreach ($roles as $role) {
        echo "     * {$role->shortname} (id={$role->roleid})" . PHP_EOL;
    }
    
    // Simular la lógica del hook
    $proc = $DB->get_record('local_educa_processedcourses', ['courseid' => $testcourse->id, 'processed' => 1]);
    $editables = $DB->get_records('local_educa_editables', ['courseid' => $testcourse->id]);
    echo "   - ¿Procesado? " . ($proc ? 'SÍ' : 'NO') . PHP_EOL;
    echo "   - ¿Tiene editables? " . (count($editables) > 0 ? 'SÍ (' . count($editables) . ')' : 'NO') . PHP_EOL;
    echo "   - ¿Admin tiene cap? " . ($hascap ? 'SÍ' : 'NO') . PHP_EOL;
    echo "   => ¿Debería aparecer menú? " . ($proc && count($editables) > 0 && $hascap ? 'SÍ' : 'NO') . PHP_EOL;
} else {
    echo PHP_EOL . "2. Curso 50020125-IFC303-16805 no encontrado" . PHP_EOL;
}

// 3. Verificar si el hook está registrado
echo PHP_EOL . "3. Funciones hook registradas:" . PHP_EOL;
$hooks = get_plugin_list_with_function('local', 'extend_navigation_course');
if (isset($hooks['educaaragon'])) {
    echo "   - local_educaaragon_extend_navigation_course: REGISTRADO" . PHP_EOL;
} else {
    echo "   - local_educaaragon_extend_navigation_course: NO REGISTRADO" . PHP_EOL;
}

// 4. Verificar capacidad en base de datos
$cap = $DB->get_record('capabilities', ['name' => 'local/educaaragon:editresources']);
echo PHP_EOL . "4. Capacidad en BD: " . ($cap ? 'EXISTS' : 'NO EXISTS') . PHP_EOL;
if ($cap) {
    echo "   - name={$cap->name} contextlevel={$cap->contextlevel}" . PHP_EOL;
}

// 5. Verificar asignaciones en role_capabilities
$assignments = $DB->get_records('role_capabilities', ['capability' => 'local/educaaragon:editresources']);
echo PHP_EOL . "5. Asignaciones de editresources en role_capabilities: " . count($assignments) . PHP_EOL;
foreach ($assignments as $a) {
    $role = $DB->get_record('role', ['id' => $a->roleid], 'shortname');
    $rname = $role ? $role->shortname : 'id=' . $a->roleid;
    echo "   - role={$rname} permission={$a->permission} (1=allow, -1=prohibit)" . PHP_EOL;
}

echo PHP_EOL . "=== FIN DIAGNÓSTICO ===" . PHP_EOL;
