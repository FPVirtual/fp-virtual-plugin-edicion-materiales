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
 * Script CLI para migrar versiones editadas de resourceid antiguos a resourceid nuevos.
 *
 * Comparte toda la lógica de migración con la clase
 * local_educaaragon\edition_versions_migrator, que también ejecuta la tarea
 * programada en el primer procesado de un módulo.
 *
 * Permite migrar un centro completo (todas las carpetas cuyo primer token
 * del shortname coincide) y genera un documento de log con todos los cambios
 * realizados y un resumen final en <repo>/editions/_logs/.
 *
 * @package    local_educaaragon
 * @author     3iPunt <https://www.tresipunt.com/>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/local/educaaragon/lib.php');
require_once($CFG->dirroot . '/local/educaaragon/classes/edition_versions_migrator.php');

use local_educaaragon\edition_versions_migrator;

// ============================================================================
// PARSEO DE ARGUMENTOS
// ============================================================================
$longopts = [
    'course:',
    'center:',
    'apply-version:',
    'include-original',
    'dry-run',
    'verbose',
    'help',
];
$options = getopt('', $longopts);

$coursesfilter = [];
if (isset($options['course'])) {
    $coursesfilter = is_array($options['course']) ? $options['course'] : [$options['course']];
}

$centerfilter    = isset($options['center']) ? trim($options['center']) : '';
$applyversion    = isset($options['apply-version']) ? $options['apply-version'] : '';
$includeoriginal = isset($options['include-original']);
$dryrun          = isset($options['dry-run']);
$verbose         = isset($options['verbose']);

// ============================================================================
// AYUDA
// ============================================================================
if (isset($options['help'])) {
    echo "Migrar versiones editadas de resourceid antiguos a nuevos.\n\n";
    echo "Uso:\n";
    echo "  php local/educaaragon/cli/migrate_edition_versions.php [opciones]\n\n";
    echo "Opciones:\n";
    echo "  --course=SHORTNAME      Filtrar por shortname de módulo (puede repetirse).\n";
    echo "  --center=CODIGO         Migrar todas las carpetas cuyo primer token del\n";
    echo "                          shortname coincide (ej. 50020125 migrara\n";
    echo "                          50020125-IFC303-16805, 50020125-IFC303-16809, ...).\n";
    echo "  --apply-version=NAME    Aplicar esta version tras migrar (ej. v1_2025-2026).\n";
    echo "  --include-original      Tambien copia la carpeta 'original' del antiguo.\n";
    echo "  --dry-run               Muestra que haria sin aplicar cambios.\n";
    echo "  --verbose               Muestra detalle de cada recurso.\n";
    echo "  --help                  Muestra esta ayuda.\n\n";
    echo "Ejemplos:\n";
    echo "  php local/educaaragon/cli/migrate_edition_versions.php --dry-run --verbose\n";
    echo "  php local/educaaragon/cli/migrate_edition_versions.php --course=50020125-IFC303-16805 --apply-version=v1_2025-2026\n";
    echo "  php local/educaaragon/cli/migrate_edition_versions.php --center=50020125 --apply-version=v1_2025-2026\n\n";
    echo "Al finalizar se genera un documento de log en editions/_logs/ con todos\n";
    echo "los cambios realizados y un resumen final.\n";
    exit(0);
}

// ============================================================================
// VALIDACIONES INICIALES
// ============================================================================
try {
    $repository = get_repository();
} catch (Exception $e) {
    cli_error('Error al obtener el repositorio: ' . $e->getMessage());
}

$rootpath = rtrim($repository->get_rootpath(), '/');
$editionspath = $rootpath . '/editions/';

if (!is_dir($editionspath)) {
    cli_error('No existe la carpeta editions/ en el repositorio: ' . $editionspath);
}

$loglabel  = $centerfilter !== '' ? $centerfilter : 'todos';
$logsuffix = $dryrun ? '_dryrun' : '';

$loglines = [];
$log = function(string $message) use (&$loglines): void {
    $loglines[] = '[' . date('Y-m-d H:i:s') . '] ' . $message;
};

$filtersdesc = $centerfilter !== '' ? 'centro=' . $centerfilter : 'todos los módulos';
if (!empty($coursesfilter)) {
    $filtersdesc .= ' | módulos=' . implode(',', $coursesfilter);
}
$optionsdesc = trim(($dryrun ? 'dry-run ' : '')
    . ($applyversion !== '' ? 'apply-version=' . $applyversion . ' ' : '')
    . ($includeoriginal ? 'include-original ' : '')
    . ($verbose ? 'verbose' : ''));

$log('============================================================');
$log(' MIGRACION DE VERSIONES EDITADAS');
$log(' Fecha:       ' . date('Y-m-d H:i:s'));
$log(' Repositorio: ' . $rootpath);
$log(' Filtros:     ' . $filtersdesc);
$log(' Opciones:    ' . ($optionsdesc !== '' ? $optionsdesc : '(ninguna)'));
$log('============================================================');

// ============================================================================
// ESTADISTICAS
// ============================================================================
$processedcourses   = 0;
$migratedresources  = 0;
$migratedversions   = 0;
$appliedversions    = 0;
$skippedresources   = 0;
$errors             = 0;

// ============================================================================
// PROCESAMIENTO
// ============================================================================
$coursedirs = scandir($editionspath);
if ($coursedirs === false) {
    cli_error('No se pudo leer el directorio: ' . $editionspath);
}

foreach ($coursedirs as $coursedir) {
    if ($coursedir === '.' || $coursedir === '..' || $coursedir === '_logs') {
        continue;
    }

    $courseshortname = $coursedir;

    if (!is_dir($editionspath . $coursedir . '/')) {
        continue;
    }

    // Filtro por shortname exacto.
    if (!empty($coursesfilter) && !in_array($courseshortname, $coursesfilter, true)) {
        continue;
    }

    // Filtro por centro: primer token del shortname (ej. 50020125).
    if ($centerfilter !== '' && explode('-', $courseshortname)[0] !== $centerfilter) {
        continue;
    }

    // Buscar módulo en Moodle.
    $course = $DB->get_record('course', ['shortname' => $courseshortname]);
    if (!$course) {
        cli_writeln('Módulo no encontrado en Moodle: ' . $courseshortname);
        $log('ERROR: Módulo no encontrado en Moodle: ' . $courseshortname);
        $errors++;
        continue;
    }

    if ($verbose) {
        cli_writeln('');
        cli_writeln('Módulo: ' . $courseshortname . ' (id=' . $course->id . ')');
    }

    $migrator = new edition_versions_migrator($repository, $dryrun, $applyversion, $includeoriginal, $verbose);
    $stats = $migrator->migrate_course($course);
    $loglines = array_merge($loglines, $migrator->get_logs());

    if ($stats['migratedresources'] === 0) {
        if ($verbose) {
            cli_writeln('   Nada que migrar para este módulo');
        }
        $log('Nada que migrar para este módulo.');
        $skippedresources += $stats['skipped'];
        $errors += $stats['errors'];
        continue;
    }

    $processedcourses++;
    $migratedresources += $stats['migratedresources'];
    $migratedversions  += $stats['migratedversions'];
    $appliedversions   += $stats['appliedversions'];
    $skippedresources  += $stats['skipped'];
    $errors            += $stats['errors'];
}

// ============================================================================
// RESUMEN
// ============================================================================
cli_writeln('');
cli_writeln('═══════════════════════════════════════════════');
cli_writeln(' RESUMEN DE MIGRACION');
cli_writeln('═══════════════════════════════════════════════');
cli_writeln('Módulos procesados:      ' . $processedcourses);
cli_writeln('Recursos emparejados:   ' . $migratedresources);
cli_writeln('Versiones copiadas:     ' . $migratedversions);
cli_writeln('Versiones aplicadas:    ' . $appliedversions);
cli_writeln('Saltadas/omitidas:      ' . $skippedresources);
cli_writeln('Errores:                ' . $errors);
cli_writeln('═══════════════════════════════════════════════');

$log('');
$log('============================================================');
$log(' RESUMEN DE MIGRACION');
$log('============================================================');
$log('Módulos procesados:    ' . $processedcourses);
$log('Recursos emparejados: ' . $migratedresources);
$log('Versiones copiadas:   ' . $migratedversions);
$log('Versiones aplicadas:  ' . $appliedversions);
$log('Saltadas/omitidas:    ' . $skippedresources);
$log('Errores:              ' . $errors);
if ($dryrun) {
    cli_writeln('');
    cli_writeln('Se ejecuto en modo --dry-run. No se realizaron cambios.');
    cli_writeln('   Revisa el emparejamiento y ejecuta sin --dry-run para aplicar.');
    $log('Modo dry-run: no se realizaron cambios.');
}
$log('============================================================');

// ============================================================================
// ESCRITURA DEL DOCUMENTO DE LOG
// ============================================================================
try {
    $logfile = write_execution_log('migracion_' . $loglabel . $logsuffix, $loglines);
} catch (Exception $e) {
    cli_error($e->getMessage());
}

cli_writeln('');
cli_writeln('Log guardado en: ' . $logfile);

exit($errors > 0 ? 1 : 0);
