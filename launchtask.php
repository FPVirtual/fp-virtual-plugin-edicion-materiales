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
 * Manual launcher for the local_educaaragon transformation task.
 *
 * Allows administrators to run the transformation task immediately for all
 * configured courses or for a single selected course.
 *
 * @package    local_educaaragon
 * @author     3iPunt <https://www.tresipunt.com/>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  3iPunt <https://www.tresipunt.com/>
 */

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/educaaragon/classes/task/transform_dynamic_content.php');
require_once($CFG->dirroot . '/local/educaaragon/classes/external/reprocessing_external.php');

use local_educaaragon\external\reprocessing_external;
use local_educaaragon\task\transform_dynamic_content;

require_login();

$context = context_system::instance();
require_capability('local/educaaragon:manageall', $context);

$scope = optional_param('scope', '', PARAM_ALPHA);
$courseid = optional_param('courseid', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

$PAGE->set_url('/local/educaaragon/launchtask.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->set_title(get_string('launchtask', 'local_educaaragon'));
$PAGE->set_heading(get_string('launchtask', 'local_educaaragon'));

$task = new transform_dynamic_content();
$output = '';
$executionoutput = '';
$showform = true;

// Validate repository configuration before allowing execution.
$repositoryid = get_config('local_educaaragon', 'repository');
if ($repositoryid === false) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('no_repository_select', 'local_educaaragon'), 'error');
    echo $OUTPUT->footer();
    exit;
}

if ($scope === 'all') {
    require_sesskey();
    raise_memory_limit(MEMORY_EXTRA);
    core_php_time_limit::raise(0);

    ob_start();
    $task->run(true);
    $executionoutput = ob_get_clean();

    $output .= $OUTPUT->notification(get_string('launchtask_execution_finished', 'local_educaaragon'), 'success');
} else if ($scope === 'single' && $courseid > 0) {
    require_sesskey();
    $course = $DB->get_record('course', ['id' => $courseid]);
    if (!$course) {
        $output .= $OUTPUT->notification(get_string('launchtask_course_notfound', 'local_educaaragon'), 'error');
    } else {
        $processed = $DB->get_record('local_educa_processedcourses', ['courseid' => $course->id], 'processed');
        $isprocessed = $processed !== false && (int)$processed->processed === 1;

        if ($isprocessed && !$confirm) {
            $showform = false;
            $output .= $OUTPUT->notification(get_string('launchtask_course_processed_warning', 'local_educaaragon'), 'warning');
            $confirmurl = new moodle_url('/local/educaaragon/launchtask.php', [
                'scope' => 'single',
                'courseid' => $course->id,
                'confirm' => 1,
                'sesskey' => sesskey(),
            ]);
            $output .= html_writer::start_div('mt-3');
            $output .= html_writer::tag('p', get_string('launchtask_reprocess_confirm', 'local_educaaragon'));
            $output .= $OUTPUT->single_button($confirmurl, get_string('launchtask_reprocess', 'local_educaaragon'), 'post');
            $output .= html_writer::end_div();
        } else {
            if ($isprocessed && $confirm) {
                reprocessing_external::reprocessing_course($course->id);
            }

            raise_memory_limit(MEMORY_EXTRA);
            core_php_time_limit::raise(0);

            ob_start();
            $task->process_single_course($course, true);
            $executionoutput = ob_get_clean();

            $output .= $OUTPUT->notification(get_string('launchtask_execution_finished', 'local_educaaragon'), 'success');
        }
    }
}

$courses = get_courses();
unset($courses[1]);
usort($courses, function($a, $b) {
    return strcmp($a->fullname, $b->fullname);
});

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('launchtask', 'local_educaaragon'));
echo html_writer::tag('p', get_string('launchtask_desc', 'local_educaaragon'));

if (!empty($output)) {
    echo $output;
}

if (!empty($executionoutput)) {
    echo html_writer::start_div('mt-3');
    echo html_writer::tag('h4', get_string('launchtask_result', 'local_educaaragon'));
    echo html_writer::tag('pre', s($executionoutput), ['class' => 'pre-scrollable border p-2 bg-light']);
    echo html_writer::end_div();
}

if ($showform) {
    echo html_writer::start_div('mt-3');
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => new moodle_url('/local/educaaragon/launchtask.php'),
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

    echo html_writer::start_tag('fieldset', ['class' => 'form-group']);
    echo html_writer::tag('legend', get_string('launchtask_scope', 'local_educaaragon'));

    echo html_writer::start_div('form-check');
    echo html_writer::empty_tag('input', [
        'class' => 'form-check-input',
        'type' => 'radio',
        'name' => 'scope',
        'id' => 'scope_all',
        'value' => 'all',
        'checked' => ($scope === 'all') ? 'checked' : null,
    ]);
    echo html_writer::tag('label', get_string('launchtask_all', 'local_educaaragon'), ['class' => 'form-check-label', 'for' => 'scope_all']);
    echo html_writer::tag('small', get_string('launchtask_all_desc', 'local_educaaragon'), ['class' => 'form-text text-muted d-block']);
    echo html_writer::end_div();

    echo html_writer::start_div('form-check mt-2');
    echo html_writer::empty_tag('input', [
        'class' => 'form-check-input',
        'type' => 'radio',
        'name' => 'scope',
        'id' => 'scope_single',
        'value' => 'single',
        'checked' => ($scope === 'single') ? 'checked' : null,
    ]);
    echo html_writer::tag('label', get_string('launchtask_single', 'local_educaaragon'), ['class' => 'form-check-label', 'for' => 'scope_single']);
    echo html_writer::tag('small', get_string('launchtask_single_desc', 'local_educaaragon'), ['class' => 'form-text text-muted d-block']);
    echo html_writer::end_div();

    echo html_writer::end_tag('fieldset');

    echo html_writer::start_div('form-group');
    echo html_writer::tag('label', get_string('launchtask_course', 'local_educaaragon'), ['for' => 'courseid']);
    echo html_writer::start_tag('select', ['class' => 'form-control', 'name' => 'courseid', 'id' => 'courseid']);
    echo html_writer::tag('option', get_string('launchtask_selectcourse', 'local_educaaragon'), ['value' => '']);
    foreach ($courses as $course) {
        $selected = ($courseid === (int)$course->id) ? ['selected' => 'selected'] : [];
        echo html_writer::tag('option', s($course->fullname . ' (' . $course->shortname . ')'), ['value' => $course->id] + $selected);
    }
    echo html_writer::end_tag('select');
    echo html_writer::end_div();

    echo html_writer::start_div('mt-3');
    echo html_writer::tag('button', get_string('launchtask_execute', 'local_educaaragon'), ['type' => 'submit', 'class' => 'btn btn-primary']);
    echo html_writer::end_div();

    echo html_writer::end_tag('form');
    echo html_writer::end_div();
}

echo $OUTPUT->footer();
