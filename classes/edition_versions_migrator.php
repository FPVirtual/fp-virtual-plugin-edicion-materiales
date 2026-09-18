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

namespace local_educaaragon;

use dml_exception;
use Exception;
use repository_filesystem;
use stdClass;

require_once(__DIR__ . '/../../../config.php');
global $CFG;

require_once($CFG->dirroot . '/local/educaaragon/lib.php');
require_once($CFG->dirroot . '/local/educaaragon/classes/manage_editable_resource.php');
require_once($CFG->dirroot . '/lib/modinfolib.php');

/**
 * Imports edited versions stored under editions/<courseshortname>/ into the
 * current editable resources of a course. Old resourceid folders are paired
 * positionally with the current resourceids so existing edited versions
 * survive a re-creation of the course resources.
 *
 * Used by the scheduled task on the first processing of a course and by the
 * CLI script cli/migrate_edition_versions.php.
 *
 * @package local_educaaragon
 * @author 3iPunt <https://www.tresipunt.com/>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 3iPunt <https://www.tresipunt.com/>
 */
class edition_versions_migrator {

    /** @var string */
    private $editionspath;

    /** @var bool */
    private $dryrun;

    /** @var bool */
    private $includeoriginal;

    /** @var string */
    private $applyversion;

    /** @var bool */
    private $verbose;

    /** @var string[] Log lines collected during migrations, with timestamps. */
    private $logs = [];

    /**
     * Returns the log lines collected during the migrations run.
     *
     * @return string[]
     */
    public function get_logs(): array {
        return $this->logs;
    }

    /**
     * Records a message in the log and echoes it. Details are only echoed
     * when verbose; errors are always echoed.
     *
     * @param string $message
     * @param bool $echoalways Echo even when not verbose.
     * @return void
     */
    private function log(string $message, bool $echoalways = false): void {
        $this->logs[] = '[' . date('Y-m-d H:i:s') . '] ' . $message;
        if ($this->verbose || $echoalways) {
            mtrace($message);
        }
    }

    /**
     * @param repository_filesystem $repository
     * @param bool $dryrun
     * @param string $applyversion Version to apply after migrating (empty = none).
     * @param bool $includeoriginal Also copy the 'original' folder of the old resourceid.
     * @param bool $verbose
     */
    public function __construct(
        repository_filesystem $repository,
        bool $dryrun = false,
        string $applyversion = '',
        bool $includeoriginal = false,
        bool $verbose = false) {
        $this->editionspath = rtrim($repository->get_rootpath(), '/') . '/' . processcourse::EDITIONS_FOLDER . '/';
        $this->dryrun = $dryrun;
        $this->includeoriginal = $includeoriginal;
        $this->applyversion = $applyversion;
        $this->verbose = $verbose;
    }

    /**
     * Whether the repository has an editions folder for the given course shortname.
     *
     * @param string $courseshortname
     * @return bool
     */
    public function has_editions_for_course(string $courseshortname): bool {
        return is_dir($this->editionspath . $courseshortname);
    }

    /**
     * Migrates the edited versions of one course. Old resourceid folders found in
     * editions/<shortname>/ are paired by position with the current editable
     * resourceids and their version folders are copied over.
     *
     * @param stdClass $course Course record (must contain at least id and shortname).
     * @return array Stats with keys: migratedresources, migratedversions, appliedversions, skipped, errors.
     * @throws dml_exception
     */
    public function migrate_course(stdClass $course): array {
        global $DB;
        $stats = [
            'migratedresources' => 0,
            'migratedversions' => 0,
            'appliedversions' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];
        $coursepath = $this->editionspath . $course->shortname . '/';
        if (!is_dir($coursepath)) {
            return $stats;
        }
        $this->log('');
        $this->log('=== Módulo: ' . $course->shortname . ' (id=' . $course->id . ') ===');

        // Current editable resourceids of the course, in ascending order.
        $currenteditables = $DB->get_records('local_educa_editables', [
            'courseid' => $course->id,
            'type' => 'editable',
        ], 'resourceid ASC', 'resourceid');
        $currentids = array_keys($currenteditables);
        if (empty($currentids)) {
            return $stats;
        }

        // Numeric folders inside editions/<shortname>/.
        $folderids = [];
        foreach (scandir($coursepath) as $resourcedir) {
            if ($resourcedir === '.' || $resourcedir === '..' || !is_dir($coursepath . $resourcedir)) {
                continue;
            }
            $rid = (int)$resourcedir;
            if ($rid > 0) {
                $folderids[] = $rid;
            }
        }
        sort($folderids, SORT_NUMERIC);

        // Folders that do not match a current resourceid belong to old resourceids.
        $oldfolders = array_values(array_diff($folderids, $currentids));
        $paircount = min(count($oldfolders), count($currentids));
        if ($paircount === 0) {
            return $stats;
        }

        $resourcemoduleid = (int)$DB->get_field('modules', 'id', ['name' => 'resource']);

        for ($i = 0; $i < $paircount; $i++) {
            $oldid = $oldfolders[$i];
            $newid = $currentids[$i];
            $oldpath = $coursepath . $oldid . '/';
            $newpath = $coursepath . $newid . '/';

            $this->log('  Migrating old resourceid ' . $oldid . ' -> ' . $newid);

            foreach (scandir($oldpath) as $versionname) {
                if ($versionname === '.' || $versionname === '..' || !is_dir($oldpath . $versionname)) {
                    continue;
                }
                if ($versionname === 'original' && !$this->includeoriginal) {
                    continue;
                }
                $src = $oldpath . $versionname . '/';
                $dst = $newpath . $versionname . '/';
                if (is_dir($dst)) {
                    $this->log('  Version ' . $versionname . ' already exists in ' . $newid . ', skipping');
                    $stats['skipped']++;
                    continue;
                }
                if ($this->dryrun) {
                    $this->log('  [DRY-RUN] Would copy version ' . $versionname . ' from ' . $oldid . ' to ' . $newid, true);
                    $stats['migratedversions']++;
                    continue;
                }
                try {
                    copy_folder($src, $dst);
                    $this->log('  Copied version ' . $versionname);
                    $stats['migratedversions']++;
                } catch (Exception $e) {
                    $this->log('  Error copying version ' . $versionname . ': ' . $e->getMessage(), true);
                    $stats['errors']++;
                    continue;
                }
            }

            if ($this->applyversion !== '' && !$this->dryrun) {
                $applied = $this->apply_version($course, $newid, $resourcemoduleid, $stats);
                if ($applied) {
                    $stats['appliedversions']++;
                }
            }
        }

        $stats['migratedresources'] = $paircount;
        $this->log('  -> Recursos emparejados: ' . $stats['migratedresources']
            . ' | Versiones copiadas: ' . $stats['migratedversions']
            . ' | Versiones aplicadas: ' . $stats['appliedversions']
            . ' | Omitidas: ' . $stats['skipped']
            . ' | Errores: ' . $stats['errors']);
        return $stats;
    }

    /**
     * Applies the configured version to a migrated resource and its printable twin.
     *
     * @param stdClass $course
     * @param int $newid
     * @param int $resourcemoduleid
     * @param array $stats Stats array to count errors on.
     * @return bool
     * @throws dml_exception
     */
    private function apply_version(stdClass $course, int $newid, int $resourcemoduleid, array &$stats): bool {
        global $DB;
        $coursepath = $this->editionspath . $course->shortname . '/';
        $versiondir = $coursepath . $newid . '/' . $this->applyversion . '/';
        if (!is_dir($versiondir)) {
            $this->log('  Version ' . $this->applyversion . ' not found after migrating resourceid ' . $newid);
            return false;
        }
        $cm = $DB->get_record('course_modules', [
            'instance' => $newid,
            'module' => $resourcemoduleid,
            'course' => $course->id,
        ]);
        if (!$cm) {
            $this->log('  Course module not found for resourceid ' . $newid, true);
            $stats['errors']++;
            return false;
        }
        try {
            $transaction = $DB->start_delegated_transaction();
            $modinfo = get_fast_modinfo($course);
            $cminfo = $modinfo->get_cm($cm->id);
            $manager = new manage_editable_resource($cminfo, $this->applyversion);
            $manager->applyversion();
            $manager->apllyversionprintable();
            $transaction->allow_commit();
            $this->log('  Applied version ' . $this->applyversion . ' to resourceid ' . $newid);
            return true;
        } catch (Exception $e) {
            if (isset($transaction)) {
                try {
                    $transaction->rollback($e);
                } catch (Exception $ignored) {
                    // Ignored.
                }
            }
            $this->log('  Error applying version ' . $this->applyversion . ': ' . $e->getMessage(), true);
            $stats['errors']++;
            return false;
        }
    }
}
