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
 * Adhoc task that processes a list of courses with the editable material
 * generation logic (transform_dynamic_content) and writes an execution log
 * document in <repo>/logs/.
 *
 * Custom data: object with ->course (int[]), ->scopelabel (string) and
 * ->scopedesc (string).
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
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/local/educaaragon/lib.php');
require_once($CFG->dirroot . '/local/educaaragon/classes/task/transform_dynamic_content.php');

class process_courses_task extends adhoc_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string('processcourses_task', 'local_educaaragon');
    }

    /**
     * Processes each course of the custom data list and writes the log document.
     *
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function execute(): void {
        global $DB;
        $data = $this->get_custom_data();
        $courseids = $data->course ?? [];
        $scopelabel = $data->scopelabel ?? 'global';
        $scopedesc = $data->scopedesc ?? '';

        $loglines = [];
        $log = function(string $message) use (&$loglines): void {
            $loglines[] = '[' . date('Y-m-d H:i:s') . '] ' . $message;
        };

        $log('============================================================');
        $log(' GENERACION DE MATERIALES EDITABLES (tarea en segundo plano)');
        $log(' Fecha:  ' . date('Y-m-d H:i:s'));
        $log(' Ambito: ' . $scopedesc);
        $log('============================================================');

        // NOTE: the per-course detail cannot be captured with ob_start(): under CLI
        // mtrace() writes to STDOUT (fwrite), which bypasses the output buffers.
        // transform_dynamic_content collects its trace lines explicitly instead.
        $start = microtime(true);
        $processed = 0;
        $failed = [];
        foreach ($courseids as $courseid) {
            $course = $DB->get_record('course', ['id' => $courseid]);
            if (!$course) {
                $error = get_string('tasksummary_coursenotfound', 'local_educaaragon', $courseid);
                $log($error);
                $failed[] = ['shortname' => 'id=' . $courseid, 'courseid' => $courseid, 'error' => $error];
                continue;
            }
            $task = new transform_dynamic_content();
            $task->process_single_course($course, true);
            $loglines = array_merge($loglines, $task->get_trace_lines());
            $courseerror = $task->get_last_error();
            if ($courseerror !== null) {
                $failed[] = ['shortname' => $course->shortname, 'courseid' => $course->id, 'error' => $courseerror];
            } else {
                $processed++;
            }
        }

        $summary = [];
        $summary[] = get_string('tasksummary_scope', 'local_educaaragon', count($courseids));
        $summary[] = get_string('tasksummary_ok', 'local_educaaragon', $processed);
        $summary[] = get_string('tasksummary_failed', 'local_educaaragon', count($failed));
        foreach ($failed as $failedcourse) {
            $summary[] = get_string('tasksummary_failedcourse', 'local_educaaragon', (object)$failedcourse);
        }
        $summary[] = get_string('tasksummary_elapsed', 'local_educaaragon', round(microtime(true) - $start, 2) . 's');
        $summary[] = get_string('tasksummary_peakmemory', 'local_educaaragon', display_size(memory_get_peak_usage(true)));

        // The summary goes to the log document and to the cron output (docker logs).
        $log('');
        foreach ($summary as $summaryline) {
            $log($summaryline);
        }
        mtrace(PHP_EOL . get_string('processcourses_task', 'local_educaaragon') . PHP_EOL . implode(PHP_EOL, $summary));

        try {
            write_execution_log('gener_' . $scopelabel, $loglines);
        } catch (Exception $e) {
            mtrace($e->getMessage());
        }
    }
}
