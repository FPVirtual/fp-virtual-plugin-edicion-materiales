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
 * Script CLI to mark a single course for reprocessing by the local_educaaragon plugin.
 *
 * The script deletes the editable/printable resources of the course, removes the
 * entries from local_educa_editables and deletes the editions folder from the
 * filesystem repository. The course is then left as not processed, so the next
 * execution of the transform_dynamic_content scheduled task will regenerate it.
 *
 * @package    local_educaaragon
 * @author     3iPunt <https://www.tresipunt.com/>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  3iPunt <https://www.tresipunt.com/>
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/local/educaaragon/classes/external/reprocessing_external.php');

use local_educaaragon\external\reprocessing_external;

$longopts = [
    'shortname:',
    'courseid:',
    'help',
];
$options = getopt('', $longopts);

if (isset($options['help']) || (!isset($options['shortname']) && !isset($options['courseid']))) {
    cli_writeln('Mark a course for reprocessing by local_educaaragon.');
    cli_writeln('');
    cli_writeln('Usage:');
    cli_writeln('  php local/educaaragon/cli/reprocess_course.php --shortname=<shortname>');
    cli_writeln('  php local/educaaragon/cli/reprocess_course.php --courseid=<courseid>');
    cli_writeln('');
    cli_writeln('Examples:');
    cli_writeln('  php local/educaaragon/cli/reprocess_course.php --shortname=50020125-IFC303-16805');
    cli_writeln('  php local/educaaragon/cli/reprocess_course.php --courseid=476');
    cli_writeln('');
    cli_writeln('After running this script, execute the scheduled task to regenerate the resources:');
    cli_writeln('  php admin/cli/scheduled_task.php --execute=\'\\local_educaaragon\\task\\transform_dynamic_content\'');
    exit(0);
}

$course = false;
if (isset($options['shortname'])) {
    $course = $DB->get_record('course', ['shortname' => $options['shortname']]);
    if (!$course) {
        cli_error('Course not found: ' . $options['shortname']);
    }
} else if (isset($options['courseid'])) {
    $course = $DB->get_record('course', ['id' => (int)$options['courseid']]);
    if (!$course) {
        cli_error('Course not found with id: ' . $options['courseid']);
    }
}

if ($course === false) {
    cli_error('A --shortname or --courseid must be provided.');
}

cli_writeln('Reprocessing course ' . $course->shortname . ' (id=' . $course->id . ')...');

try {
    $result = reprocessing_external::reprocessing_course($course->id);
    if ($result['response']) {
        cli_writeln('Course marked for reprocessing successfully.');
        cli_writeln('');
        cli_writeln('Run the scheduled task to regenerate the resources:');
        cli_writeln('  php admin/cli/scheduled_task.php --execute=\'\\local_educaaragon\\task\\transform_dynamic_content\'');
    } else {
        cli_error('The reprocessing service returned a negative response.');
    }
} catch (Exception $e) {
    cli_error('Error reprocessing course: ' . $e->getMessage());
}
