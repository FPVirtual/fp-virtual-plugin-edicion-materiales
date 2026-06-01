<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');

echo "=== Verificacion manual del hook ===" . PHP_EOL . PHP_EOL;

// 1. Sintaxis de lib.php
echo "1. Sintaxis de lib.php:" . PHP_EOL;
system('php -l ' . escapeshellarg($CFG->dirroot . '/local/educaaragon/lib.php') . ' 2>&1');

// 2. Plugins locales activos
echo PHP_EOL . "2. Plugins locales activos:" . PHP_EOL;
$plugins = core_plugin_manager::instance()->get_installed_plugins('local');
foreach ($plugins as $name => $version) {
    echo "   - " . $name . " => " . $version . PHP_EOL;
}

// 3. get_plugin_list
echo PHP_EOL . "3. get_plugin_list(local): " . PHP_EOL;
$list = get_plugin_list('local');
echo "   educaaragon => " . (isset($list['educaaragon']) ? $list['educaaragon'] : 'NO ENCONTRADO') . PHP_EOL;

// 4. Cargar lib.php manualmente
echo PHP_EOL . "4. Cargando lib.php manualmente..." . PHP_EOL;
$libfile = $CFG->dirroot . '/local/educaaragon/lib.php';
if (file_exists($libfile)) {
    require_once($libfile);
    echo "   Funcion local_educaaragon_extend_navigation_course existe: " . (function_exists('local_educaaragon_extend_navigation_course') ? 'SI' : 'NO') . PHP_EOL;
} else {
    echo "   lib.php NO EXISTE" . PHP_EOL;
}

// 5. get_plugin_list_with_function
echo PHP_EOL . "5. get_plugin_list_with_function result:" . PHP_EOL;
$hooks = get_plugin_list_with_function('local', 'extend_navigation_course');
if (isset($hooks['educaaragon'])) {
    echo "   educaaragon => REGISTRADO" . PHP_EOL;
} else {
    echo "   educaaragon => NO REGISTRADO" . PHP_EOL;
    echo "   Plugins registrados: " . implode(', ', array_keys($hooks)) . PHP_EOL;
}

echo PHP_EOL . "=== FIN ===" . PHP_EOL;
