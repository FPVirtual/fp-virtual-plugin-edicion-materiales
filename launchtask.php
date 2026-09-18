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
 * Both tasks generate a log document in <repo>/editions/_logs/.
 *
 * @package    local_educaaragon
 * @author     3iPunt <https://www.tresipunt.com/>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  3iPunt <https://www.tresipunt.com/>
 */

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/educaaragon/lib.php');
require_once($CFG->dirroot . '/local/educaaragon/classes/task/transform_dynamic_content.php');
require_once($CFG->dirroot . '/local/educaaragon/classes/edition_versions_migrator.php');

use local_educaaragon\edition_versions_migrator;
use local_educaaragon\task\transform_dynamic_content;

require_login();

$context = context_system::instance();
require_capability('local/educaaragon:manageall', $context);

$tasktype = optional_param('task', 'generate', PARAM_ALPHA);
$scope = optional_param('scope', '', PARAM_ALPHA);
$courseid = optional_param('courseid', 0, PARAM_INT);
$center = optional_param('center', '', PARAM_ALPHANUMEXT);
$confirm = optional_param('confirm', 0, PARAM_INT);
$applyversion = optional_param('applyversion', '', PARAM_ALPHANUMEXT);
$includeoriginal = optional_param('includeoriginal', 0, PARAM_BOOL);
$dryrun = optional_param('dryrun', 0, PARAM_BOOL);

$PAGE->set_url('/local/educaaragon/launchtask.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->set_title(get_string('launchtask', 'local_educaaragon'));
$PAGE->set_heading(get_string('launchtask', 'local_educaaragon'));

$task = new transform_dynamic_content();
$output = '';
$executionoutput = '';
$showform = true;
$logfile = '';

$loglines = [];
$log = function(string $message) use (&$loglines): void {
    $loglines[] = '[' . date('Y-m-d H:i:s') . '] ' . $message;
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
                raise_memory_limit(MEMORY_EXTRA);
                core_php_time_limit::raise(0);

                ob_start();
                $task->process_single_course($course, true);
                $executionoutput = ob_get_clean();

                $output .= $OUTPUT->notification(get_string('launchtask_execution_finished', 'local_educaaragon'), 'success');
            }
        }
    } else if ($scope === 'center') {
        require_sesskey();
        if ($center === '') {
            $output .= $OUTPUT->notification(get_string('launchtask_center_empty', 'local_educaaragon'), 'error');
        } else {
            raise_memory_limit(MEMORY_EXTRA);
            core_php_time_limit::raise(0);

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

            if (empty($toprocess)) {
                $output .= $OUTPUT->notification(get_string('launchtask_center_none', 'local_educaaragon'), 'warning');
            } else {
                ob_start();
                foreach ($toprocess as $toprocesscourse) {
                    $task->process_single_course($toprocesscourse, true);
                }
                $executionoutput = ob_get_clean();

                $output .= $OUTPUT->notification(get_string('launchtask_execution_finished', 'local_educaaragon'), 'success');
            }
        }
    } else {
        $output .= $OUTPUT->notification(get_string('launchtask_scope_missing', 'local_educaaragon'), 'error');
    }

    // Log document of the generation run.
    if ($executionoutput !== '') {
        if ($scope === 'center') {
            $scopedesc = get_string('launchtask_center', 'local_educaaragon') . ': ' . $center;
            $scopelabel = clean_string($center);
        } else if ($scope === 'single') {
            $runcourse = $DB->get_record('course', ['id' => $courseid], 'id, shortname');
            $scopedesc = $runcourse ? $runcourse->shortname . ' (id=' . $runcourse->id . ')' : $scope;
            $scopelabel = $runcourse ? clean_string($runcourse->shortname) : 'curso';
        } else {
            $scopedesc = get_string('launchtask_all', 'local_educaaragon');
            $scopelabel = 'global';
        }

        $log('============================================================');
        $log(' GENERACION DE MATERIALES EDITABLES');
        $log(' Fecha:  ' . date('Y-m-d H:i:s'));
        $log(' Ambito: ' . $scopedesc);
        $log('============================================================');
        $loglines = array_merge($loglines, explode("\n", $executionoutput));
        try {
            $logfile = write_execution_log('generacion_' . $scopelabel, $loglines);
        } catch (Exception $e) {
            $output .= $OUTPUT->notification($e->getMessage(), 'error');
        }
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
        raise_memory_limit(MEMORY_EXTRA);
        core_php_time_limit::raise(0);

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

        $totalstats = [
            'migratedresources' => 0,
            'migratedversions' => 0,
            'appliedversions' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];
        $processedcourses = 0;

        if ($scope === 'center') {
            $scopedesc = get_string('launchtask_center', 'local_educaaragon') . ': ' . $center;
            $scopelabel = clean_string($center);
        } else if ($scope === 'single') {
            $runcourse = $DB->get_record('course', ['id' => $courseid], 'id, shortname');
            $scopedesc = $runcourse ? $runcourse->shortname . ' (id=' . $runcourse->id . ')' : $scope;
            $scopelabel = $runcourse ? clean_string($runcourse->shortname) : 'curso';
        } else {
            $scopedesc = get_string('launchtask_all', 'local_educaaragon');
            $scopelabel = 'global';
        }

        $log('============================================================');
        $log(' IMPORTACION DE VERSIONES DE MATERIALES');
        $log(' Fecha:    ' . date('Y-m-d H:i:s'));
        $log(' Ambito:   ' . $scopedesc);
        $log(' Opciones: ' . trim(($dryrun ? 'simulacion ' : '')
            . ($applyversion !== '' ? 'apply-version=' . $applyversion . ' ' : '')
            . ($includeoriginal ? 'include-original' : '')));
        $log('============================================================');

        try {
            ob_start();
            try {
                foreach ($migrationcourses as $key => $migrationcourse) {
                    if ($migrationcourse === null) {
                        $notfoundshortname = substr((string)$key, strlen('notfound:'));
                        $log(get_string('launchtask_migration_coursenotfound', 'local_educaaragon', $notfoundshortname));
                        $totalstats['errors']++;
                        continue;
                    }
                    $migrator = new edition_versions_migrator($repository, $dryrun, $applyversion, $includeoriginal, true);
                    $coursestats = $migrator->migrate_course($migrationcourse);
                    $loglines = array_merge($loglines, $migrator->get_logs());
                    if ($coursestats['migratedresources'] === 0) {
                        $log('Nada que migrar para este curso.');
                    } else {
                        $processedcourses++;
                    }
                    foreach (array_keys($totalstats) as $statkey) {
                        $totalstats[$statkey] += $coursestats[$statkey];
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
            } finally {
                // Discard the mtrace echo of the migrator, the log lines are shown instead.
                ob_end_clean();
            }

            $executionoutput = implode("\n", $loglines);
            $logfile = write_execution_log('importacion_' . $scopelabel . ($dryrun ? '_dryrun' : ''), $loglines);
            $output .= $OUTPUT->notification(get_string('launchtask_execution_finished', 'local_educaaragon'), 'success');
        } catch (Exception $e) {
            $output .= $OUTPUT->notification($e->getMessage(), 'error');
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

if (!empty($logfile)) {
    echo $OUTPUT->notification(get_string('launchtask_logfile', 'local_educaaragon') . ' ' . $logfile, 'info');
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

    echo html_writer::start_div('form-group mt-2');
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

    echo html_writer::end_tag('fieldset');

    // Migration options (only shown when the import task is selected).
    echo html_writer::start_tag('fieldset', ['class' => 'form-group', 'id' => 'migrateoptions']);
    echo html_writer::tag('legend', get_string('launchtask_migrationoptions', 'local_educaaragon'));
    echo $OUTPUT->notification(get_string('launchtask_migrate_requirement', 'local_educaaragon'), 'warning');

    echo html_writer::start_div('form-group');
    echo html_writer::tag('label', get_string('launchtask_applyversion', 'local_educaaragon'), ['for' => 'applyversion']);
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'class' => 'form-control',
        'name' => 'applyversion',
        'id' => 'applyversion',
        'value' => $applyversion,
        'placeholder' => 'v1_2025-2026',
        'autocomplete' => 'off',
    ]);
    echo html_writer::tag('small', get_string('launchtask_applyversion_desc', 'local_educaaragon'), ['class' => 'form-text text-muted']);
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

    echo html_writer::script("(function() {
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
    })();");

    echo html_writer::script("(function() {
        var migrateOptions = document.getElementById('migrateoptions');
        var taskInputs = document.querySelectorAll('input[name=\"task\"]');
        function toggleMigrateOptions() {
            var selected = document.querySelector('input[name=\"task\"]:checked');
            migrateOptions.style.display = (selected && selected.value === 'migrate') ? '' : 'none';
        }
        taskInputs.forEach(function(input) {
            input.addEventListener('change', toggleMigrateOptions);
        });
        toggleMigrateOptions();
    })();");

    echo html_writer::start_div('mt-3');
    echo html_writer::tag('button', get_string('launchtask_execute', 'local_educaaragon'), ['type' => 'submit', 'class' => 'btn btn-primary']);
    echo html_writer::end_div();

    echo html_writer::end_tag('form');
    echo html_writer::end_div();
}

echo local_educaaragon_version_footer();
echo $OUTPUT->footer();
