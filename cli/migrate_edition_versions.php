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
 * @package    local_educaaragon
 * @author     3iPunt <https://www.tresipunt.com/>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/local/educaaragon/lib.php');
require_once($CFG->dirroot . '/local/educaaragon/classes/manage_editable_resource.php');

use local_educaaragon\manage_editable_resource;

// ============================================================================
// PARSEO DE ARGUMENTOS
// ============================================================================
$longopts = [
    'course:',
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
    echo "  --course=SHORTNAME      Filtrar por shortname de curso (puede repetirse).\n";
    echo "  --apply-version=NAME    Aplicar esta version tras migrar (ej. v1_2025-2026).\n";
    echo "  --include-original      Tambien copia la carpeta 'original' del antiguo.\n";
    echo "  --dry-run               Muestra que haria sin aplicar cambios.\n";
    echo "  --verbose               Muestra detalle de cada recurso.\n";
    echo "  --help                  Muestra esta ayuda.\n\n";
    echo "Ejemplo:\n";
    echo "  php local/educaaragon/cli/migrate_edition_versions.php --dry-run --verbose\n";
    echo "  php local/educaaragon/cli/migrate_edition_versions.php --course=50020125-IFC303-16805 --apply-version=v1_2025-2026\n";
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
    cli_error('No se encontro el modulo "resource" en la tabla modules.');
}

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

    $courseprocessed = false;

    if ($verbose) {
        cli_writeln('');
        cli_writeln('📁 Curso: ' . $courseshortname . ' (id=' . $course->id . ')');
    }

    // Obtener resourceids actuales del curso (tipo editable).
    $currenteditables = $DB->get_records('local_educa_editables', [
        'courseid' => $course->id,
        'type'     => 'editable',
    ], 'resourceid ASC', 'resourceid');
    $currentids = array_keys($currenteditables);

    if (empty($currentids)) {
        if ($verbose) {
            cli_writeln('   ℹ️  No hay recursos editables actuales en Moodle');
        }
        continue;
    }

    // Escanear carpetas en editions/<curso>/.
    $resourcedirs = scandir($coursepath);
    if ($resourcedirs === false) {
        cli_writeln('⚠️  No se pudo leer: ' . $coursepath);
        $errors++;
        continue;
    }

    $folderids = [];
    foreach ($resourcedirs as $resourcedir) {
        if ($resourcedir === '.' || $resourcedir === '..' || !is_dir($coursepath . $resourcedir)) {
            continue;
        }
        $rid = (int)$resourcedir;
        if ($rid > 0) {
            $folderids[] = $rid;
        }
    }
    sort($folderids, SORT_NUMERIC);

    if (empty($folderids)) {
        if ($verbose) {
            cli_writeln('   ℹ️  No hay carpetas en editions para migrar');
        }
        continue;
    }

    if ($verbose) {
        cli_writeln('   Actuales en Moodle: ' . implode(', ', $currentids));
        cli_writeln('   Carpetas en editions: ' . implode(', ', $folderids));
    }

    // Separar carpetas que ya son IDs actuales (propios) vs antiguos.
    $oldfolders = [];
    $actualfolders = [];
    foreach ($folderids as $fid) {
        if (in_array($fid, $currentids, true)) {
            $actualfolders[] = $fid;
        } else {
            $oldfolders[] = $fid;
        }
    }

    // Targets: todos los actuales, en orden.
    // Se empareja oldfolders[i] -> currentids[i] por posición.
    $targetids = $currentids;

    if ($verbose) {
        cli_writeln('   Carpetas propias: ' . implode(', ', $actualfolders));
        cli_writeln('   Antiguos en editions: ' . implode(', ', $oldfolders));
        cli_writeln('   Targets sin carpeta: ' . implode(', ', $targetids));
    }

    $paircount = min(count($oldfolders), count($targetids));
    if ($paircount === 0) {
        if ($verbose) {
            cli_writeln('   ℹ️  No hay antiguos para migrar o todos los actuales ya tienen carpeta');
        }
        continue;
    }

    for ($i = 0; $i < $paircount; $i++) {
        $oldid = $oldfolders[$i];
        $newid = $targetids[$i];

        $oldpath = $coursepath . $oldid . '/';
        $newpath = $coursepath . $newid . '/';

        if ($verbose) {
            cli_writeln('');
            cli_writeln('   🔀 Emparejamiento ' . ($i + 1) . '/' . $paircount);
            cli_writeln('      Antiguo: ' . $oldid . ' -> Nuevo: ' . $newid);
        }

        // Listar versiones del antiguo.
        $versions = scandir($oldpath);
        if ($versions === false) {
            cli_writeln('      ⚠️  No se pudo leer: ' . $oldpath);
            $errors++;
            continue;
        }

        foreach ($versions as $versionname) {
            if ($versionname === '.' || $versionname === '..' || !is_dir($oldpath . $versionname)) {
                continue;
            }

            // Saltar original salvo que se fuerce.
            if ($versionname === 'original' && !$includeoriginal) {
                continue;
            }

            $src = $oldpath . $versionname . '/';
            $dst = $newpath . $versionname . '/';

            if (is_dir($dst)) {
                if ($verbose) {
                    cli_writeln('      ⚠️  Version ' . $versionname . ' ya existe en nuevo, saltando');
                }
                $skippedresources++;
                continue;
            }

            if ($dryrun) {
                cli_writeln('      [DRY-RUN] Copiaria ' . $versionname . ' de ' . $oldid . ' a ' . $newid);
                $migratedversions++;
                $courseprocessed = true;
                continue;
            }

            try {
                copy_folder($src, $dst);
                if ($verbose) {
                    cli_writeln('      ✅ Copiada version ' . $versionname);
                }
                $migratedversions++;
                $courseprocessed = true;
            } catch (Exception $e) {
                cli_writeln('      ❌ Error copiando ' . $versionname . ': ' . $e->getMessage());
                $errors++;
                continue;
            }
        }

        // Aplicar version si se solicito.
        if ($applyversion !== '' && !$dryrun) {
            $versiondir = $newpath . $applyversion . '/';
            if (!is_dir($versiondir)) {
                if ($verbose) {
                    cli_writeln('      ⚠️  Version ' . $applyversion . ' no existe tras migrar');
                }
                continue;
            }

            $cm = $DB->get_record('course_modules', [
                'instance' => $newid,
                'module'   => $resourcemoduleid,
                'course'   => $course->id,
            ]);
            if (!$cm) {
                cli_writeln('      ⚠️  CM no encontrado para resourceid ' . $newid);
                $errors++;
                continue;
            }

            try {
                $transaction = $DB->start_delegated_transaction();

                $modinfo = get_fast_modinfo($course);
                $cminfo  = $modinfo->get_cm($cm->id);

                $manager = new manage_editable_resource($cminfo, $applyversion);
                $manager->applyversion();
                $manager->apllyversionprintable();

                $transaction->allow_commit();

                if ($verbose) {
                    cli_writeln('      ✅ Aplicada version ' . $applyversion . ' a resourceid ' . $newid);
                }
                $appliedversions++;
            } catch (Exception $e) {
                if (isset($transaction)) {
                    try {
                        $transaction->rollback($e);
                    } catch (Exception $rollbackex) {
                        // Ignorar.
                    }
                }
                cli_writeln('      ❌ Error aplicando version: ' . $e->getMessage());
                $errors++;
            }
        }
    }

    if ($courseprocessed) {
        $processedcourses++;
        $migratedresources += $paircount;
    }
}

// ============================================================================
// RESUMEN
// ============================================================================
cli_writeln('');
cli_writeln('═══════════════════════════════════════════════');
cli_writeln(' RESUMEN DE MIGRACION');
cli_writeln('═══════════════════════════════════════════════');
cli_writeln('Cursos procesados:      ' . $processedcourses);
cli_writeln('Recursos emparejados:   ' . $migratedresources);
cli_writeln('Versiones copiadas:     ' . $migratedversions);
cli_writeln('Versiones aplicadas:    ' . $appliedversions);
cli_writeln('Saltadas/omitidas:      ' . $skippedresources);
cli_writeln('Errores:                ' . $errors);
cli_writeln('═══════════════════════════════════════════════');

if ($dryrun) {
    cli_writeln('');
    cli_writeln('ℹ️  Se ejecuto en modo --dry-run. No se realizaron cambios.');
    cli_writeln('   Revisa el emparejamiento y ejecuta sin --dry-run para aplicar.');
}

exit($errors > 0 ? 1 : 0);
