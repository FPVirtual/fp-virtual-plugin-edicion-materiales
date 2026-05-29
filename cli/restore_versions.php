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
 * Script CLI para reaplicar masivamente la versión original de los recursos
 * editables desde el filesystem (editions/) a los módulos mod_resource de Moodle.
 *
 * @package    local_educaaragon
 * @author     3iPunt <https://www.tresipunt.com/>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/local/educaaragon/lib.php');
require_once($CFG->dirroot . '/local/educaaragon/classes/manage_editable_resource.php');
require_once($CFG->dirroot . '/local/educaaragon/classes/manage_logs.php');

use local_educaaragon\manage_editable_resource;
use local_educaaragon\manage_logs;

// ============================================================================
// PARSEO DE ARGUMENTOS
// ============================================================================
$longopts = [
    'course:',
    'category:',
    'dry-run',
    'verbose',
    'help',
];
$options = getopt('', $longopts);

// Normalizar --course a array.
$coursesfilter = [];
if (isset($options['course'])) {
    $coursesfilter = is_array($options['course']) ? $options['course'] : [$options['course']];
}

$categoryfilter = isset($options['category']) ? (int)$options['category'] : 0;
$dryrun         = isset($options['dry-run']);
$verbose        = isset($options['verbose']);

// ============================================================================
// AYUDA
// ============================================================================
if (isset($options['help'])) {
    echo "Reaplica masivamente la versión 'original' de los recursos editables desde editions/.\n\n";
    echo "Uso:\n";
    echo "  php local/educaaragon/cli/restore_versions.php [opciones]\n\n";
    echo "Opciones:\n";
    echo "  --course=SHORTNAME    Filtrar por shortname de curso (puede repetirse).\n";
    echo "  --category=ID         Filtrar por ID de categoría de Moodle.\n";
    echo "  --dry-run             Muestra qué haría sin aplicar cambios.\n";
    echo "  --verbose             Muestra detalle de cada recurso procesado.\n";
    echo "  --help                Muestra esta ayuda.\n\n";
    echo "Ejemplos:\n";
    echo "  php local/educaaragon/cli/restore_versions.php --dry-run --verbose\n";
    echo "  php local/educaaragon/cli/restore_versions.php --course=50020125-IFC201-4991\n";
    echo "  php local/educaaragon/cli/restore_versions.php --category=42\n";
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

$resourcemoduleid = $DB->get_field('modules', 'id', ['name' => 'resource']);
if (!$resourcemoduleid) {
    cli_error('No se encontró el módulo "resource" en la tabla modules.');
}

// ============================================================================
// ESTADÍSTICAS
// ============================================================================
$processedcourses  = 0;
$processedresources = 0;
$skippedresources  = 0;
$errors            = 0;

// ============================================================================
// PROCESAMIENTO
// ============================================================================
$coursedirs = scandir($editionspath);
if ($coursedirs === false) {
    cli_error('No se pudo leer el directorio: ' . $editionspath);
}

foreach ($coursedirs as $coursedir) {
    if ($coursedir === '.' || $coursedir === '..') {
        continue;
    }

    $courseshortname = $coursedir;
    $coursepath = $editionspath . $coursedir . '/';

    if (!is_dir($coursepath)) {
        continue;
    }

    // Filtro por shortname.
    if (!empty($coursesfilter) && !in_array($courseshortname, $coursesfilter, true)) {
        continue;
    }

    // Buscar curso en Moodle.
    $course = $DB->get_record('course', ['shortname' => $courseshortname]);
    if (!$course) {
        cli_writeln('⚠️  Curso no encontrado en Moodle: ' . $courseshortname);
        $errors++;
        continue;
    }

    // Filtro por categoría.
    if ($categoryfilter && (int)$course->category !== $categoryfilter) {
        continue;
    }

    $courseprocessed = false;

    if ($verbose) {
        cli_writeln('📁 Procesando curso: ' . $courseshortname . ' (id=' . $course->id . ')');
    }

    // Escanear resourceids dentro del curso.
    $resourcedirs = scandir($coursepath);
    if ($resourcedirs === false) {
        cli_writeln('⚠️  No se pudo leer: ' . $coursepath);
        $errors++;
        continue;
    }

    foreach ($resourcedirs as $resourcedir) {
        if ($resourcedir === '.' || $resourcedir === '..' || !is_dir($coursepath . $resourcedir)) {
            continue;
        }

        $resourceid = (int)$resourcedir;
        if ($resourceid <= 0) {
            if ($verbose) {
                cli_writeln('   ⚠️  Ignorando carpeta no numérica: ' . $resourcedir);
            }
            continue;
        }

        $originalpath = $coursepath . $resourcedir . '/original/';
        if (!is_dir($originalpath)) {
            if ($verbose) {
                cli_writeln('   ⚠️  No existe original/ para resourceid ' . $resourceid);
            }
            $skippedresources++;
            continue;
        }

        // Buscar el course_module correspondiente.
        $cm = $DB->get_record('course_modules', [
            'instance' => $resourceid,
            'module'   => $resourcemoduleid,
            'course'   => $course->id,
        ]);

        if (!$cm) {
            cli_writeln('   ⚠️  CM no encontrado para resourceid ' . $resourceid .
                        ' en curso ' . $courseshortname);
            $errors++;
            continue;
        }

        if ($dryrun) {
            cli_writeln('   [DRY-RUN] Aplicaría original a resourceid ' . $resourceid .
                        ' (cmid=' . $cm->id . ')');
            $processedresources++;
            $courseprocessed = true;
            continue;
        }

        // Aplicar versión.
        try {
            $transaction = $DB->start_delegated_transaction();

            $modinfo = get_fast_modinfo($course);
            $cminfo  = $modinfo->get_cm($cm->id);

            $manager = new manage_editable_resource($cminfo, 'original');
            $manager->applyversion();
            $manager->apllyversionprintable();

            $transaction->allow_commit();

            if ($verbose) {
                cli_writeln('   ✅ Aplicada original a resourceid ' . $resourceid .
                            ' (cmid=' . $cm->id . ')');
            }
            $processedresources++;
            $courseprocessed = true;
        } catch (Exception $e) {
            if (isset($transaction)) {
                try {
                    $transaction->rollback($e);
                } catch (Exception $rollbackex) {
                    // Ignorar error de rollback.
                }
            }
            cli_writeln('   ❌ Error en resourceid ' . $resourceid . ': ' . $e->getMessage());
            $errors++;
        }
    }

    if ($courseprocessed) {
        $processedcourses++;
    }
}

// ============================================================================
// RESUMEN
// ============================================================================
cli_writeln('');
cli_writeln('═══════════════════════════════════════════════');
cli_writeln(' RESUMEN');
cli_writeln('═══════════════════════════════════════════════');
cli_writeln('Cursos procesados:    ' . $processedcourses);
cli_writeln('Recursos aplicados:   ' . $processedresources);
cli_writeln('Recursos omitidos:    ' . $skippedresources);
cli_writeln('Errores:              ' . $errors);
cli_writeln('═══════════════════════════════════════════════');

if ($dryrun) {
    cli_writeln('');
    cli_writeln('ℹ️  Se ejecutó en modo --dry-run. No se realizaron cambios.');
}

exit($errors > 0 ? 1 : 0);
