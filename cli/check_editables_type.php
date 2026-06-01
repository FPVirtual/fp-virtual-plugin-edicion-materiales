<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');

$records = $DB->get_records('local_educa_editables', ['courseid' => 482]);
echo 'Registros para curso 482:' . PHP_EOL;
foreach ($records as $r) {
    echo '  resourceid=' . $r->resourceid . ' type=' . var_export($r->type, true) . ' version=' . $r->version . PHP_EOL;
}
