<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/navigationlib.php');
require_once($CFG->dirroot . '/local/educaaragon/lib.php');

echo "=== Debug paso a paso del hook ===" . PHP_EOL . PHP_EOL;

$course = $DB->get_record('course', ['shortname' => '50020125-IFC303-16805']);
if (!$course) {
    echo "Curso no encontrado" . PHP_EOL;
    exit;
}

$context = context_course::instance($course->id);
$admin = get_admin();

// Forzar login como admin para has_capability
\core\session\manager::set_user($admin);

echo "Curso: {$course->shortname} (id={$course->id})" . PHP_EOL;
echo "Admin ID: {$admin->id}" . PHP_EOL;

// Paso 1: curso procesado
$courseprocessed = $DB->get_record('local_educa_processedcourses', ['courseid' => $course->id]);
echo "Paso 1 - curso procesado: " . ($courseprocessed !== false ? 'SI' : 'NO') . PHP_EOL;
if ($courseprocessed) {
    echo "Paso 1b - processed=1: " . ((int)$courseprocessed->processed === 1 ? 'SI' : 'NO') . PHP_EOL;
}

// Paso 2: editables
$editables = $DB->get_records('local_educa_editables', ['courseid' => $course->id]);
echo "Paso 2 - editables > 0: " . (count($editables) > 0 ? 'SI (' . count($editables) . ')' : 'NO') . PHP_EOL;

// Paso 3: capacidad (sin userid explicito, como hace el hook)
$hascap = has_capability('local/educaaragon:editresources', $context);
echo "Paso 3 - has_capability (usuario actual): " . ($hascap ? 'SI' : 'NO') . PHP_EOL;

// Paso 3b: capacidad con admin explicito
$hascap_admin = has_capability('local/educaaragon:editresources', $context, $admin->id);
echo "Paso 3b - has_capability (admin explicito): " . ($hascap_admin ? 'SI' : 'NO') . PHP_EOL;

// Paso 4: get_string
echo "Paso 4 - get_string: ";
try {
    $label = get_string('editables', 'local_educaaragon');
    echo $label . PHP_EOL;
} catch (Exception $e) {
    echo "ERROR - " . $e->getMessage() . PHP_EOL;
}

// Llamada al hook
echo PHP_EOL . "Llamando al hook..." . PHP_EOL;
$navigation = new navigation_node('root');
local_educaaragon_extend_navigation_course($navigation, $course, $context);

$children = $navigation->get_children_key_list();
echo "Nodos creados: " . count($children) . PHP_EOL;
foreach ($children as $key) {
    $node = $navigation->get($key);
    echo "   - " . $node->get_content() . " => " . $node->action . PHP_EOL;
}

echo PHP_EOL . "=== FIN ===" . PHP_EOL;
