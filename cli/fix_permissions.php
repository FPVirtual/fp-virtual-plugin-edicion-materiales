<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/accesslib.php');

$capability = 'local/educaaragon:editresources';
$roletargets = ['manager', 'coursecreator', 'editingteacher'];

foreach ($roletargets as $shortname) {
    $role = $DB->get_record('role', ['shortname' => $shortname]);
    if (!$role) {
        echo "Rol no encontrado: {$shortname}" . PHP_EOL;
        continue;
    }
    assign_capability($capability, CAP_ALLOW, $role->id, context_system::instance()->id, true);
    echo "Asignado {$capability} al rol {$shortname} (id={$role->id})" . PHP_EOL;
}

$capability2 = 'local/educaaragon:manageall';
foreach (['manager', 'coursecreator'] as $shortname) {
    $role = $DB->get_record('role', ['shortname' => $shortname]);
    if ($role) {
        assign_capability($capability2, CAP_ALLOW, $role->id, context_system::instance()->id, true);
        echo "Asignado {$capability2} al rol {$shortname}" . PHP_EOL;
    }
}

echo PHP_EOL . 'Hecho. Ahora purga caches para que se vea el menu.' . PHP_EOL;
