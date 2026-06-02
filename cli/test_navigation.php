<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/navigationlib.php');
require_once($CFG->dirroot . '/local/educaaragon/lib.php');

echo "=== Simulacion de navegacion del curso ===" . PHP_EOL . PHP_EOL;

// Buscar un curso procesado con editables
$course = $DB->get_record('course', ['shortname' => '50020125-IFC303-16805']);
if (!$course) {
    echo "Curso no encontrado" . PHP_EOL;
    exit;
}

$context = context_course::instance($course->id);
$admin = get_admin();

// Verificar condiciones del hook
$courseprocessed = $DB->get_record('local_educa_processedcourses', ['courseid' => $course->id]);
echo "Curso: {$course->shortname} (id={$course->id})" . PHP_EOL;
echo "Procesado: " . ($courseprocessed && $courseprocessed->processed ? 'SI' : 'NO') . PHP_EOL;

$editables = $DB->get_records('local_educa_editables', ['courseid' => $course->id]);
echo "Editables: " . count($editables) . PHP_EOL;

$hascap = has_capability('local/educaaragon:editresources', $context, $admin->id);
echo "Admin tiene editresources: " . ($hascap ? 'SI' : 'NO') . PHP_EOL;

// Simular la llamada al hook
echo PHP_EOL . "Simulando local_educaaragon_extend_navigation_course..." . PHP_EOL;
try {
    $navigation = new navigation_node('root');
    local_educaaragon_extend_navigation_course($navigation, $course, $context);
    
    $children = $navigation->get_children_key_list();
    echo "Nodos creados: " . count($children) . PHP_EOL;
    foreach ($children as $key) {
        $node = $navigation->get($key);
        echo "   - " . $node->get_content() . " => " . $node->action . PHP_EOL;
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Trace: " . $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL . "=== FIN ===" . PHP_EOL;
