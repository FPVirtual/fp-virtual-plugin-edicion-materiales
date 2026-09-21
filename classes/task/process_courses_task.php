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

        foreach ($courseids as $courseid) {
            $course = $DB->get_record('course', ['id' => $courseid]);
            if (!$course) {
                $log('Modulo no encontrado (id=' . $courseid . '). Se omite.');
                continue;
            }
            $task = new transform_dynamic_content();
            ob_start();
            $task->process_single_course($course, true);
            $courseoutput = ob_get_clean();
            $loglines = array_merge($loglines, explode("\n", $courseoutput));
        }

        try {
            write_execution_log('gener_' . $scopelabel, $loglines);
        } catch (Exception $e) {
            mtrace($e->getMessage());
        }
    }
}
