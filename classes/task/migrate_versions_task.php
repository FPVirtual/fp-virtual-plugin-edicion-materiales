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
 * Adhoc task that imports edited material versions into the current course
 * resources (edition_versions_migrator) and writes an execution log document
 * in <repo>/logs/.
 *
 * Custom data: object with ->courseids (int[]), ->notfound (string[]),
 * ->dryrun (bool), ->applyversion (string), ->includeoriginal (bool),
 * ->scopelabel (string) and ->scopedesc (string).
 *
 * @package    local_educaaragon
 * @author     3iPunt <https://www.tresipunt.com/>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  3iPunt <https://www.tresipunt.com/>
 */

namespace local_educaaragon\task;

use coding_exception;
use core\task\adhoc_task;
use dml_exception;
use Exception;
use local_educaaragon\edition_versions_migrator;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/local/educaaragon/lib.php');

class migrate_versions_task extends adhoc_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string('migrateversionstask', 'local_educaaragon');
    }

    /**
     * Migrates the versions of each course of the custom data list and writes
     * the log document.
     *
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function execute(): void {
        global $DB;
        $data = $this->get_custom_data();
        $courseids = $data->courseids ?? [];
        $notfound = $data->notfound ?? [];
        $dryrun = !empty($data->dryrun);
        $applyversion = $data->applyversion ?? '';
        $includeoriginal = !empty($data->includeoriginal);
        $scopelabel = $data->scopelabel ?? 'global';
        $scopedesc = $data->scopedesc ?? '';

        $repository = get_repository();

        $totalstats = [
            'migratedresources' => 0,
            'migratedversions' => 0,
            'appliedversions' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $loglines = [];
        $log = function(string $message) use (&$loglines): void {
            $loglines[] = '[' . date('Y-m-d H:i:s') . '] ' . $message;
        };

        $log('============================================================');
        $log(' IMPORTACION DE VERSIONES DE MATERIALES (tarea en segundo plano)');
        $log(' Fecha:    ' . date('Y-m-d H:i:s'));
        $log(' Ambito:   ' . $scopedesc);
        $log(' Opciones: ' . trim(($dryrun ? 'simulacion ' : '')
            . ($applyversion !== '' ? 'apply-version=' . $applyversion . ' ' : '')
            . ($includeoriginal ? 'include-original' : '')));
        $log('============================================================');

        $start = microtime(true);

        foreach ($notfound as $notfoundshortname) {
            $log(get_string('launchtask_migration_coursenotfound', 'local_educaaragon', $notfoundshortname));
            $totalstats['errors']++;
        }

        foreach ($courseids as $courseid) {
            $course = $DB->get_record('course', ['id' => $courseid]);
            if (!$course) {
                $log(get_string('launchtask_migration_coursenotfound', 'local_educaaragon', 'id=' . $courseid));
                $totalstats['errors']++;
                continue;
            }
            ob_start();
            try {
                $migrator = new edition_versions_migrator($repository, $dryrun, $applyversion, $includeoriginal, true);
                $coursestats = $migrator->migrate_course($course);
                $loglines = array_merge($loglines, $migrator->get_logs());
                if ($coursestats['migratedresources'] === 0) {
                    $log('Nada que migrar para este módulo.');
                }
                foreach (array_keys($totalstats) as $statkey) {
                    $totalstats[$statkey] += $coursestats[$statkey];
                }
            } finally {
                // Discard the mtrace echo of the migrator, the log lines are kept instead.
                ob_end_clean();
            }
        }

        $log('');
        $log(get_string('importededitions_result', 'local_educaaragon', (object)[
            'resources' => $totalstats['migratedresources'],
            'versions' => $totalstats['migratedversions'],
            'applied' => $totalstats['appliedversions'],
            'skipped' => $totalstats['skipped'],
            'errors' => $totalstats['errors'],
        ]));
        if ($dryrun) {
            $log('Simulacion (dry-run): no se realizo ningun cambio.');
        }

        $summary = [];
        $summary[] = get_string('tasksummary_scope', 'local_educaaragon', count($courseids) + count($notfound));
        $summary[] = get_string('tasksummary_elapsed', 'local_educaaragon', round(microtime(true) - $start, 2) . 's');
        $summary[] = get_string('tasksummary_peakmemory', 'local_educaaragon', display_size(memory_get_peak_usage(true)));

        // The summary goes to the log document and to the cron output (docker logs).
        $log('');
        foreach ($summary as $summaryline) {
            $log($summaryline);
        }
        mtrace(PHP_EOL . get_string('migrateversionstask', 'local_educaaragon') . PHP_EOL . implode(PHP_EOL, $summary));

        try {
            write_execution_log('importacion_' . $scopelabel . ($dryrun ? '_dryrun' : ''), $loglines);
        } catch (Exception $e) {
            mtrace($e->getMessage());
        }
    }
}
