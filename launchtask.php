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
 * Manual execution page for the local_educaaragon tasks.
 *
 * Allows administrators to run two tasks immediately:
 * - Editable material generation (transform_dynamic_content), for all
 *   configured courses, a single course or a whole centre.
 * - Material version import (edition_versions_migrator), which pairs edited
 *   versions stored under old resourceids with the current course resources
 *   and therefore requires the generation task to have run first.
 * Both executions are queued as adhoc tasks and run in the background on the
 * next cron execution, generating a log document in <repo>/logs/.
 *
 * @package    local_educaaragon
 * @author     3iPunt <https://www.tresipunt.com/>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  3iPunt <https://www.tresipunt.com/>
 */

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE, $USER;

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/educaaragon/lib.php');

use local_educaaragon\task\migrate_versions_task;
use local_educaaragon\task\process_courses_task;

require_login();

$context = context_system::instance();
require_capability('local/educaaragon:manageall', $context);

$tasktype = optional_param('task', 'generate', PARAM_ALPHA);
$scope = optional_param('scope', '', PARAM_ALPHA);
$courseid = optional_param('courseid', 0, PARAM_INT);
$center = optional_param('center', '', PARAM_ALPHANUMEXT);
$confirm = optional_param('confirm', 0, PARAM_INT);
$applyversioncheck = optional_param('applyversioncheck', 0, PARAM_BOOL);
$applyversion = optional_param('applyversion', '', PARAM_ALPHANUMEXT);
$includeoriginal = optional_param('includeoriginal', 0, PARAM_BOOL);
$dryrun = optional_param('dryrun', 0, PARAM_BOOL);

// The version name only applies when its checkbox is marked.
if (!$applyversioncheck) {
    $applyversion = '';
}

$PAGE->set_url('/local/educaaragon/launchtask.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->set_title(get_string('launchtask', 'local_educaaragon'));
$PAGE->set_heading(get_string('launchtask', 'local_educaaragon'));

$output = '';
$showform = true;

/**
 * Queues an adhoc task that generates the editable materials of the given courses.
 *
 * @param int[] $courseids Ids of the courses to process.
 * @param string $scopelabel Clean label used in the log file name.
 * @param string $scopedesc Human readable description of the scope.
 * @return void
 */
$queuegenerate = function(array $courseids, string $scopelabel, string $scopedesc) use ($USER): void {
    $task = new process_courses_task();
    $task->set_custom_data((object)[
        'course' => $courseids,
        'scopelabel' => $scopelabel,
        'scopedesc' => $scopedesc,
    ]);
    $task->set_next_run_time(time());
    $task->set_userid($USER->id);
    \core\task\manager::queue_adhoc_task($task);
};

// Validate repository configuration before allowing execution.
$repositoryid = get_config('local_educaaragon', 'repository');
if ($repositoryid === false) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('no_repository_select', 'local_educaaragon'), 'error');
    echo $OUTPUT->footer();
    exit;
}

// ============================================================================
// GENERACIÓN DE MATERIALES EDITABLES
// ============================================================================
if ($tasktype === 'generate') {
    if ($scope === 'all') {
        require_sesskey();

        // Pending courses, with the same exclusion criteria as the scheduled task.
        $allcourses = get_courses();
        unset($allcourses[1]);
        $toprocess = [];
        foreach ($allcourses as $allcourse) {
            $processed = $DB->get_record('local_educa_processedcourses', ['courseid' => $allcourse->id], 'processed');
            if ($processed !== false && (int)$processed->processed === 1) {
                continue;
            }
            $toprocess[] = $allcourse;
        }

        if (empty($toprocess)) {
            $output .= $OUTPUT->notification(get_string('launchtask_all_none', 'local_educaaragon'), 'info');
        } else {
            $courseids = array_map(function($allcourse) {
                return (int)$allcourse->id;
            }, $toprocess);
            $scopedesc = get_string('launchtask_all', 'local_educaaragon');
            $queuegenerate($courseids, 'global', $scopedesc);
            $output .= $OUTPUT->notification(get_string('launchtask_queued', 'local_educaaragon', $scopedesc), 'success');
        }
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
                    'task' => 'generate',
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
                $queuegenerate(
                    [$course->id],
                    clean_string($course->shortname),
                    $course->shortname . ' (id=' . $course->id . ')'
                );
                $output .= $OUTPUT->notification(
                    get_string('launchtask_queued', 'local_educaaragon', $course->shortname), 'success');
            }
        }
    } else if ($scope === 'center') {
        require_sesskey();
        if ($center === '') {
            $output .= $OUTPUT->notification(get_string('launchtask_center_empty', 'local_educaaragon'), 'error');
        } else {
            // Courses of the center: first token of the shortname matches the code.
            $where = $DB->sql_like('shortname', ':pattern') . ' OR shortname = :exact';
            $centercourses = $DB->get_records_select('course', $where,
                ['pattern' => $center . '-%', 'exact' => $center], 'shortname ASC', 'id, shortname');

            // Skip already processed courses, same criteria as the "all" scope.
            $toprocess = [];
            foreach ($centercourses as $centercourse) {
                $processed = $DB->get_record('local_educa_processedcourses', ['courseid' => $centercourse->id], 'processed');
                if ($processed !== false && (int)$processed->processed === 1) {
                    continue;
                }
                $toprocess[] = $centercourse;
            }

            if (empty($centercourses)) {
                $output .= $OUTPUT->notification(
                    get_string('launchtask_center_notfound', 'local_educaaragon', $center), 'error');
            } else if (empty($toprocess)) {
                $output .= $OUTPUT->notification(get_string('launchtask_center_none', 'local_educaaragon'), 'warning');
            } else {
                $courseids = array_map(function($centercourse) {
                    return (int)$centercourse->id;
                }, $toprocess);
                $scopedesc = get_string('launchtask_center', 'local_educaaragon') . ': ' . $center;
                $queuegenerate($courseids, clean_string($center), $scopedesc);
                $output .= $OUTPUT->notification(get_string('launchtask_queued', 'local_educaaragon', $scopedesc), 'success');
            }
        }
    } else {
        $output .= $OUTPUT->notification(get_string('launchtask_scope_missing', 'local_educaaragon'), 'error');
    }

// ============================================================================
// IMPORTACIÓN DE VERSIONES DE MATERIALES
// ============================================================================
} else if ($tasktype === 'migrate') {
    if ($scope !== 'all' && $scope !== 'center' && !($scope === 'single' && $courseid > 0)) {
        $output .= $OUTPUT->notification(get_string('launchtask_scope_missing', 'local_educaaragon'), 'error');
    } else if ($scope === 'center' && $center === '') {
        $output .= $OUTPUT->notification(get_string('launchtask_center_empty', 'local_educaaragon'), 'error');
    } else {
        require_sesskey();

        $repository = get_repository();
        $editionspath = rtrim($repository->get_rootpath(), '/') . '/editions/';

        // Build the list of courses to migrate.
        $migrationcourses = [];
        if ($scope === 'single') {
            $course = $DB->get_record('course', ['id' => $courseid], 'id, shortname');
            if (!$course) {
                $output .= $OUTPUT->notification(get_string('launchtask_course_notfound', 'local_educaaragon'), 'error');
            } else if (!is_dir($editionspath . $course->shortname)) {
                $output .= $OUTPUT->notification(
                    get_string('launchtask_migration_noeditions', 'local_educaaragon', $course->shortname), 'warning');
            } else {
                $migrationcourses[] = $course;
            }
        } else {
            $coursedirs = scandir($editionspath);
            if ($coursedirs === false) {
                $output .= $OUTPUT->notification(get_string('launchtask_migration_noeditions', 'local_educaaragon', ''), 'error');
                $coursedirs = [];
            }
            foreach ($coursedirs as $coursedir) {
                if ($coursedir === '.' || $coursedir === '..' || $coursedir === '_logs' || !is_dir($editionspath . $coursedir)) {
                    continue;
                }
                if ($scope === 'center' && explode('-', $coursedir)[0] !== $center) {
                    continue;
                }
                $migrationcourse = $DB->get_record('course', ['shortname' => $coursedir], 'id, shortname');
                if (!$migrationcourse) {
                    $migrationcourses['notfound:' . $coursedir] = null;
                    continue;
                }
                $migrationcourses[] = $migrationcourse;
            }
        }

        if (!empty($migrationcourses)) {
            if ($scope === 'center') {
                $scopedesc = get_string('launchtask_center', 'local_educaaragon') . ': ' . $center;
                $scopelabel = clean_string($center);
            } else if ($scope === 'single') {
                $runcourse = $DB->get_record('course', ['id' => $courseid], 'id, shortname');
                $scopedesc = $runcourse ? $runcourse->shortname . ' (id=' . $runcourse->id . ')' : $scope;
                $scopelabel = $runcourse ? clean_string($runcourse->shortname) : 'modulo';
            } else {
                $scopedesc = get_string('launchtask_all', 'local_educaaragon');
                $scopelabel = 'global';
            }

            // Separate the course ids from the shortnames without a matching course.
            $courseids = [];
            $notfound = [];
            foreach ($migrationcourses as $key => $migrationcourse) {
                if ($migrationcourse === null) {
                    $notfound[] = substr((string)$key, strlen('notfound:'));
                    continue;
                }
                $courseids[] = (int)$migrationcourse->id;
            }

            $task = new migrate_versions_task();
            $task->set_custom_data((object)[
                'courseids' => $courseids,
                'notfound' => $notfound,
                'dryrun' => $dryrun,
                'applyversion' => $applyversion,
                'includeoriginal' => $includeoriginal,
                'scopelabel' => $scopelabel,
                'scopedesc' => $scopedesc,
            ]);
            $task->set_next_run_time(time());
            $task->set_userid($USER->id);
            \core\task\manager::queue_adhoc_task($task);

            $output .= $OUTPUT->notification(
                get_string('launchtask_queued', 'local_educaaragon', $scopedesc), 'success');
        } else if ($scope === 'center') {
            $output .= $OUTPUT->notification(
                get_string('launchtask_migration_centernone', 'local_educaaragon', $center), 'error');
        } else if ($scope === 'all') {
            $output .= $OUTPUT->notification(
                get_string('launchtask_migration_noeditions', 'local_educaaragon', ''), 'warning');
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
echo html_writer::start_div('mb-3');
echo html_writer::link(
    new moodle_url('/local/educaaragon/logs.php'),
    get_string('logs', 'local_educaaragon'),
    ['class' => 'btn btn-secondary btn-sm']);
echo html_writer::end_div();

if (!empty($output)) {
    echo $output;
}

if ($showform) {
    echo html_writer::start_div('mt-3');
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => new moodle_url('/local/educaaragon/launchtask.php'),
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

    // Task selector.
    echo html_writer::start_tag('fieldset', ['class' => 'form-group']);
    echo html_writer::tag('legend', get_string('launchtask_task', 'local_educaaragon'));

    echo html_writer::start_div('form-check');
    echo html_writer::empty_tag('input', [
        'class' => 'form-check-input',
        'type' => 'radio',
        'name' => 'task',
        'id' => 'task_generate',
        'value' => 'generate',
        'checked' => ($tasktype === 'generate') ? 'checked' : null,
    ]);
    echo html_writer::tag('label', get_string('launchtask_task_generate', 'local_educaaragon'), ['class' => 'form-check-label', 'for' => 'task_generate']);
    echo html_writer::tag('small', get_string('launchtask_task_generate_desc', 'local_educaaragon'), ['class' => 'form-text text-muted d-block']);
    echo html_writer::end_div();

    echo html_writer::start_div('form-check mt-2');
    echo html_writer::empty_tag('input', [
        'class' => 'form-check-input',
        'type' => 'radio',
        'name' => 'task',
        'id' => 'task_migrate',
        'value' => 'migrate',
        'checked' => ($tasktype === 'migrate') ? 'checked' : null,
    ]);
    echo html_writer::tag('label', get_string('launchtask_task_migrate', 'local_educaaragon'), ['class' => 'form-check-label', 'for' => 'task_migrate']);
    echo html_writer::tag('small', get_string('launchtask_task_migrate_desc', 'local_educaaragon'), ['class' => 'form-text text-muted d-block']);
    echo html_writer::end_div();

    echo html_writer::end_tag('fieldset');

    // Scope selector.
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

    echo html_writer::start_div('form-check mt-2');
    echo html_writer::empty_tag('input', [
        'class' => 'form-check-input',
        'type' => 'radio',
        'name' => 'scope',
        'id' => 'scope_center',
        'value' => 'center',
        'checked' => ($scope === 'center') ? 'checked' : null,
    ]);
    echo html_writer::tag('label', get_string('launchtask_center', 'local_educaaragon'), ['class' => 'form-check-label', 'for' => 'scope_center']);
    echo html_writer::tag('small', get_string('launchtask_center_desc', 'local_educaaragon'), ['class' => 'form-text text-muted d-block']);
    echo html_writer::end_div();

    // Center code (only shown when the "center" scope is selected).
    echo html_writer::start_div('form-group mt-2', ['id' => 'centergroup']);
    echo html_writer::tag('label', get_string('launchtask_centercode', 'local_educaaragon'), ['for' => 'center']);
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'class' => 'form-control',
        'name' => 'center',
        'id' => 'center',
        'value' => $center,
        'placeholder' => '50020125',
        'autocomplete' => 'off',
    ]);
    echo html_writer::end_div();

    // Module selector (only shown when the "single" scope is selected).
    echo html_writer::start_div('mt-2', ['id' => 'coursegroup']);
    echo html_writer::start_div('form-group');
    echo html_writer::tag('label', get_string('launchtask_searchcourse', 'local_educaaragon'), ['for' => 'course_search']);
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'class' => 'form-control',
        'id' => 'course_search',
        'placeholder' => get_string('launchtask_searchcourse', 'local_educaaragon'),
        'autocomplete' => 'off',
    ]);
    echo html_writer::end_div();

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
    echo html_writer::end_div();

    echo html_writer::end_tag('fieldset');

    // Migration options (only shown when the import task is selected).
    echo html_writer::start_tag('fieldset', ['class' => 'form-group', 'id' => 'migrateoptions']);
    echo html_writer::tag('legend', get_string('launchtask_migrationoptions', 'local_educaaragon'));
    echo $OUTPUT->notification(get_string('launchtask_migrate_requirement', 'local_educaaragon'), 'warning');

    echo html_writer::start_div('form-check');
    echo html_writer::empty_tag('input', [
        'class' => 'form-check-input',
        'type' => 'checkbox',
        'name' => 'applyversioncheck',
        'id' => 'applyversioncheck',
        'value' => '1',
        'checked' => $applyversioncheck ? 'checked' : null,
    ]);
    echo html_writer::tag('label', get_string('launchtask_applyversion', 'local_educaaragon'), ['class' => 'form-check-label', 'for' => 'applyversioncheck']);
    echo html_writer::tag('small', get_string('launchtask_applyversion_desc', 'local_educaaragon'), ['class' => 'form-text text-muted d-block']);
    echo html_writer::end_div();

    // Version name (only shown when the apply-version option is checked).
    echo html_writer::start_div('form-group mt-2', ['id' => 'applyversiongroup']);
    echo html_writer::tag('label', get_string('launchtask_applyversion_name', 'local_educaaragon'), ['for' => 'applyversion']);
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'class' => 'form-control',
        'name' => 'applyversion',
        'id' => 'applyversion',
        'value' => $applyversion,
        'placeholder' => 'v1_2025-2026',
        'autocomplete' => 'off',
    ]);
    echo html_writer::tag('small', get_string('launchtask_applyversion_name_desc', 'local_educaaragon'), ['class' => 'form-text text-muted']);
    echo html_writer::end_div();

    echo html_writer::start_div('form-check');
    echo html_writer::empty_tag('input', [
        'class' => 'form-check-input',
        'type' => 'checkbox',
        'name' => 'includeoriginal',
        'id' => 'includeoriginal',
        'value' => '1',
        'checked' => $includeoriginal ? 'checked' : null,
    ]);
    echo html_writer::tag('label', get_string('launchtask_includeoriginal', 'local_educaaragon'), ['class' => 'form-check-label', 'for' => 'includeoriginal']);
    echo html_writer::end_div();

    echo html_writer::start_div('form-check mt-2');
    echo html_writer::empty_tag('input', [
        'class' => 'form-check-input',
        'type' => 'checkbox',
        'name' => 'dryrun',
        'id' => 'dryrun',
        'value' => '1',
        'checked' => $dryrun ? 'checked' : null,
    ]);
    echo html_writer::tag('label', get_string('launchtask_dryrun', 'local_educaaragon'), ['class' => 'form-check-label', 'for' => 'dryrun']);
    echo html_writer::end_div();

    echo html_writer::end_tag('fieldset');

    echo html_writer::script("(function() {
        function checkedValue(name) {
            var el = document.querySelector('input[name=\"' + name + '\"]:checked');
            return el ? el.value : '';
        }
        var migrateOptions = document.getElementById('migrateoptions');
        var centerGroup = document.getElementById('centergroup');
        var courseGroup = document.getElementById('coursegroup');
        var applyversionCheck = document.getElementById('applyversioncheck');
        var applyversionGroup = document.getElementById('applyversiongroup');

        // Module search filter.
        var searchInput = document.getElementById('course_search');
        var courseSelect = document.getElementById('courseid');
        var options = Array.from(courseSelect.options);
        searchInput.addEventListener('input', function() {
            var term = searchInput.value.toLowerCase();
            options.forEach(function(option) {
                if (option.value === '') {
                    option.hidden = false;
                    return;
                }
                option.hidden = term.length > 0 && option.text.toLowerCase().indexOf(term) === -1;
            });
        });

        function toggleForm() {
            var task = checkedValue('task');
            var scope = checkedValue('scope');
            migrateOptions.style.display = (task === 'migrate') ? '' : 'none';
            centerGroup.style.display = (scope === 'center') ? '' : 'none';
            courseGroup.style.display = (scope === 'single') ? '' : 'none';
            applyversionGroup.style.display = applyversionCheck.checked ? '' : 'none';
        }
        document.querySelectorAll('input[name=\"task\"], input[name=\"scope\"]').forEach(function(input) {
            input.addEventListener('change', toggleForm);
        });
        applyversionCheck.addEventListener('change', toggleForm);
        toggleForm();
    })();");

    echo html_writer::start_div('mt-3');
    echo html_writer::tag('button', get_string('launchtask_execute', 'local_educaaragon'), ['type' => 'submit', 'class' => 'btn btn-primary']);
    echo html_writer::end_div();

    echo html_writer::end_tag('form');
    echo html_writer::end_div();
}

echo local_educaaragon_version_footer();
echo $OUTPUT->footer();
