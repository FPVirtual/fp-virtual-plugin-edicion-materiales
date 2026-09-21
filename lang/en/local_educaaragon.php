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
 * @package local_educaaragon
 * @author 3iPunt <https://www.tresipunt.com/>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 3iPunt <https://www.tresipunt.com/>
 */

$string['pluginname'] = 'Educa Aragón';
$string['educaaragon:manageall'] = 'Manage plugin local_educaaragon';
$string['educaaragon:editresources'] = 'Edit editable resources in the module';

$string['generalconfig'] = 'General configuration';
$string['activetask'] = 'Activate scheduled task to transform resources';
$string['activetask_desc'] = 'If enabled, a scheduled Moodle cron job will run through the modules looking for SCORM and IMS content to transform it.';
$string['repository'] = 'Content repository';
$string['repository_desc'] = 'Select the repository of type "filesystem" where all the dynamic contents in HTML format are stored. If none exists, you will have to create one and store the contents in it. The contents of each module should be stored in folders named with the short name of the module to make the relation.';
$string['repositoryroot'] = 'repository root';
$string['sourcefolder'] = 'Source content folder';
$string['sourcefolder_desc'] = 'Name of the folder, inside the filesystem repository root, where the original module contents are located. Leave this field empty if the module folders are directly at the repository root (for example, <code>&lt;repo_root&gt;/50020125-IFC303-16805/01/index.html</code>). Enter <code>recursos-editables</code> if the structure is <code>&lt;repo_root&gt;/recursos-editables/50020125-IFC303-16805/01/index.html</code>. Edited versions are always saved in <code>&lt;repo_root&gt;/editions/&lt;shortname&gt;/&lt;resourceid&gt;/&lt;version&gt;/</code>.';
$string['no_repository_exists'] = 'There is no filesystem repository. A repository with HTML module contents is needed. Consult a developer.';
$string['no_repository_select'] = 'No repository is selected in the plugin configuration. Select a repository before you can run the task.';
$string['allcourses'] = 'Apply to all modules';
$string['allcourses_desc'] = 'If enabled, the scheduled task will apply to all modules on the platform';
$string['category'] = 'Category';
$string['category_desc'] = 'Select the category where the SCORMS and IMS transformation to resources will be applied. All modules contained in this category will be affected, including those in sub-categories.';
$string['transformdynamiccontent'] = 'Dynamic content transformation task';
$string['course_processed'] = 'Module processed. Time spent: ';
$string['memory_used'] = 'Memory used: ';
$string['allcourses_processed'] = 'All modules processed. Time spent: ';
$string['printable'] = 'printable';

$string['transform_dynamic_content_desc'] = 'Task to transform SCORMS and IMS into HTML resources and their print version. After passing this task, the affected module contents can be edited.';
$string['notactivetask'] = 'Scheduled task has been deactivated from configuration. No module will be modified.';
$string['coursesfound'] = 'To be processed {$a} modules';
$string['processcourse'] = 'Processing module {$a->shortname} with ID {$a->courseid}';
$string['errorprocesscourse'] = 'Error processing module. Check the contents of the module in the repository';
$string['errorprocesscourse_desc'] = 'Error processing module {$a->course}: {$a->error}';
$string['error/invalidpersistenterror'] = 'There are invalid character errors in the links<br>error/invalidpersistenterror';
$string['error/invalidfilerequested'] = 'There are resources that contain directories, or invalid files in their content<br>error/invalidfilerequested';
$string['dynamiccontent_found'] = 'Found {$a} dynamic contents';
$string['editionsfolder_found'] = 'Module {$a} already processed previously. Existing versions will be recognized.';
$string['importededitions_start'] = 'Importing existing edited versions for module {$a}...';
$string['importededitions_result'] = 'Version import finished. Migrated resources: {$a->resources}. Copied versions: {$a->versions}. Applied versions: {$a->applied}. Skipped: {$a->skipped}. Errors: {$a->errors}.';
$string['recognize_resource_notfound'] = 'The editable resource module {$a} was not found. Skipping recognition of its original version.';
$string['no_resourcegenerator'] = 'There is no resource generator in this environment, so the task cannot continue. Contact a developer.';
$string['no_associated_folder'] = 'The folder {$a->folder}/{$a->course} was not found in the repository {$a->repository}';
$string['elements_does_not_match'] = 'The number of dynamic resources in the module {$a->course} does not match the number of associated resources in the repository {$a->repository}. This process will not change anything in the module until this is resolved.';
$string['elements_cant_associate'] = 'Could not associate the contents of the module {$a->course} with the contents of the repository {$a->repository}. Please check the titles of the resources and the nomenclature of the repository content. The numbering should be 01, 02, 03, etc.';
$string['error_copy_files'] = 'Error copying module files {$a->course}. Origin: {$a->origen} - Destination: {$a->destiny}. Resolve this before re-running the task.';
$string['no_index_file'] = 'No index.html file found on the resource {$a->cmname} of the module {$a->course}. The process will not continue for this module.';
$string['editable_filearea_empty'] = 'The editable resource {$a->cmname} (module {$a->course}) has no content in Moodle, so its original version could not be created or rebuilt. Delete the resource and process the module again.';
$string['version_folder_empty'] = 'Version {$a->version} of the resource {$a->cmname} (module {$a->course}) contains no files, so it cannot be applied.';
$string['processlink_error'] = 'Error processing links of resource {$a->resourceid}: {$a->error}';
$string['correctly_processed'] = 'Correctly processed module';
$string['correctly_processed_needassociation'] = 'Module processed correctly. Needs manual sorting of editable resources';
$string['selected_for_reprocessing'] = 'Selected to be reprocessed at the next execution of the task';
$string['resource_deleted'] = 'One or more editable resources have been deleted from this module. Reprocessing is recommended.';
$string['processresource'] = 'Content created in progress ';
$string['processlink'] = 'Resource link processing ';

// Launch task
$string['launchtask'] = 'Manual task execution';
$string['launchtask_desc'] = 'Allows you to manually run the plugin tasks: editable material generation and material version import, for all modules, for a specific module or for a whole centre. Both are queued as background tasks and generate a log document in the logs/ folder at the materials repository root.';
$string['launchtask_task'] = 'Task to run';
$string['launchtask_task_generate'] = 'Editable material generation';
$string['launchtask_task_generate_desc'] = 'Creates the editable and printable resources of the modules from their dynamic contents (SCORM/IMSCP).';
$string['launchtask_task_migrate'] = 'Material version import';
$string['launchtask_task_migrate_desc'] = 'Copies the edited versions stored under old identifiers to the current module resources.';
$string['launchtask_migrate_requirement'] = 'Note: the version import requires the editable material generation to have been run successfully beforehand on the affected modules, since it pairs the versions with the existing resources.';
$string['launchtask_migrationoptions'] = 'Import options';
$string['launchtask_applyversion'] = 'Apply a version after import';
$string['launchtask_applyversion_desc'] = 'By default the import does not apply any version: the newly created resources already show students the original content. Only mark this option if you want a migrated version to be applied.';
$string['launchtask_applyversion_name'] = 'Name of the version to apply';
$string['launchtask_applyversion_name_desc'] = 'Enter the exact name of the version (e.g. v1_2025-2026).';
$string['launchtask_includeoriginal'] = 'Also copy the \'original\' folder';
$string['launchtask_dryrun'] = 'Simulation (do not apply changes)';
$string['launchtask_logfile'] = 'Execution log saved at:';
$string['launchtask_migration_coursenotfound'] = 'Module not found in Moodle: {$a}';
$string['launchtask_migration_noeditions'] = 'The editions/ folder does not exist for the module: {$a}';
$string['launchtask_scope_missing'] = 'Select the execution scope.';
$string['launchtask_scope'] = 'Execution scope';
$string['launchtask_all'] = 'Process all modules';
$string['launchtask_all_desc'] = 'All unprocessed modules will be processed according to the current configuration (all modules or the selected category).';
$string['launchtask_single'] = 'Process a specific module';
$string['launchtask_single_desc'] = 'Only the selected module will be processed.';
$string['launchtask_center'] = 'Process a whole centre';
$string['launchtask_center_desc'] = 'All unprocessed modules whose centre code (first token of the shortname) matches the given one will be processed, e.g. 50020125.';
$string['launchtask_centercode'] = 'Centre code';
$string['launchtask_center_empty'] = 'You must enter a centre code.';
$string['launchtask_center_none'] = 'No unprocessed modules were found for the given centre.';
$string['launchtask_course'] = 'Module';
$string['launchtask_selectcourse'] = 'Select a module';
$string['launchtask_searchcourse'] = 'Search module';
$string['launchtask_execute'] = 'Run';
$string['launchtask_result'] = 'Execution result';
$string['launchtask_course_processed_warning'] = 'This module has already been processed.';
$string['launchtask_reprocess'] = 'Reprocess module';
$string['launchtask_reprocess_confirm'] = 'The selected module has already been processed. To reprocess it, the previously generated resources will be removed. Do you want to continue?';
$string['launchtask_course_notfound'] = 'The selected module was not found.';
$string['launchtask_execution_finished'] = 'Execution finished.';
$string['launchtask_all_none'] = 'There are no modules pending processing.';
$string['launchtask_queued'] = 'Processing of {$a} queued: it will run in the background on the next cron execution. The log will be saved in the logs/ folder at the materials repository root.';
$string['processcourses_task'] = 'Generate editable materials for';
$string['migrateversionstask'] = 'Import material versions for courses';

// Tables
$string['processedcourses'] = 'Processed modules';
$string['processedcourses_help'] = 'List of modules processed by the task <b>local_educaaragon\task\transform_dynamic_content</b>.<br>From this panel you can manage the modules that you need to be reprocessed in the next execution of the task.';
$string['courseid'] = 'Module ID';
$string['coursename'] = 'Full name';
$string['shortname'] = 'Short name';
$string['processed'] = 'Processed';
$string['message'] = 'Message';
$string['usermodified'] = 'User';
$string['timemodified'] = 'Date of modification';
$string['actions'] = 'Actions';
$string['reprocessing'] = 'Reprocess module on next run';
$string['reprocessingmsg'] = '<p>This action will mark this module for reprocessing at the next execution of the scheduled task <b>local_educaaragon\task\transform_dynamic_content</b>.</p><h4>ATTENTION!</h4><h5>Please note that marking this module for re-processing will remove the resources that were previously generated by the task, to avoid duplication.</h5>';
$string['reprocess'] = 'Reprocess';
$string['editableresources'] = 'Show list of generated editable resources';
$string['editables'] = 'Editable resources';
$string['editablematerials'] = 'Ministry Materials - Editable';
$string['editables_help'] = 'List of resources available for editing.<br>You can filter the results by module by adding the "courseid" parameter to the url.';
$string['resourceid'] = 'Resource ID';
$string['resourcename'] = 'Name of the resource';
$string['viewcourse'] = 'View module';
$string['backversions'] = 'Back to version selector';
$string['relatedcmid'] = 'Related resource';
$string['revieweditableresource'] = 'View resource';
$string['editresource'] = 'Edit resource';
$string['viewprintresource'] = 'View printable version';
$string['vieweditcontent'] = 'Editar contenidos';

// Edit resource
$string['editingresource'] = 'Editing resource';
$string['resourcenoteditable'] = 'This resource is not editable';
$string['versionnoteditable'] = 'This version cannot be edited. Select a different version in <a href="{$a}">{$a}</a>';
$string['selectversion'] = 'Select the version';
$string['selectsection'] = 'Select the section to edit';
$string['createnewversion'] = 'Create a new version';
$string['createnewversion_desc'] = 'Are you sure you want to create a new version to edit?<br>The name you have given to the version will be modified to remove special characters and replace spaces with _. If you have left it empty, the date will be set in Unix format as the version name.';
$string['confirm'] = 'Confirm';
$string['versionname'] = 'Name';
$string['loadversion'] = 'Editar version';
$string['deleteversion'] = 'Delete version';
$string['deleteversion_desc'] = 'Are you sure you want to delete the selected version?<br>Please note that if this version is applied for display, it will still be shown to users even if you delete it. To fix this, apply another version';
$string['asofversion'] = 'as of version';
$string['versionalreadyexist'] = 'A version with that name already exists';
$string['errorcreateversion'] = 'An error occurred while creating a new version. Check that the name is not repeated or contains special characters and try again by reloading this page. If the problem persists, please contact an administrator.';
$string['save_changes'] = 'Save changes';
$string['save_changes_desc'] = 'Are you sure you want to save the changes applied to this version? The changes will be saved on the version, they will not be applied to the existing resource in the module..';
$string['changes_saved'] = 'Changes saved correctly: ';
$string['not_saved'] = 'The changes could not be saved, please try again: ';
$string['apply_version'] = 'Apply version';
$string['apply_version_desc'] = 'Are you sure you want to apply the version that is selected to the resource that students will see?';
$string['version_saved'] = 'The version has been applied correctly: ';
$string['version_not_saved'] = 'The version could not be applied, please try again: ';
$string['versionprintable_saved'] = 'The print version has been applied correctly from the edited version: ';
$string['versionprintable_not_saved'] = 'The print version could not be applied, please try again: ';

// Edited resource
$string['registereditions'] = 'Register of editions';
$string['version_created'] = 'New version created';
$string['version_created_asofversion'] = 'Creada a partir de la versión: ';
$string['version_deleted'] = 'Deleted version';
$string['version_changes_saved'] = 'Saved changes';
$string['version_changes_saved_file'] = 'Affected file: ';
$string['version_applied'] = 'Version applied to the resource';
$string['version_printable_applied'] = 'Printable version applied to the resource';
$string['version_original_created'] = 'Original version created';
$string['action'] = 'Event';
$string['other'] = 'Additional information';
$string['version'] = 'Version';
$string['edit_comments'] = 'Editor\'s comments: ';
$string['write_comment'] = 'Additional information on the edition: ';

// Links
$string['link_report'] = 'Link report';
$string['link_report_desc'] = 'In this report you can see information about the links contained in a particular version of an editable resource.';
$string['processresourcelinks'] = 'Searching for broken links and flash content in resources';
$string['link_case'] = 'Case';
$string['link'] = 'Link';
$string['video'] = 'Video';
$string['iframe'] = 'Iframe';
$string['file'] = 'File';
$string['link_type'] = 'Type of link:';
$string['link_text'] = 'Link text: ';
$string['link_active'] = 'Active link';
$string['link_broken'] = 'Broken link';
$string['link_broken_cantfix'] = 'Broken link. Not solved with https';
$string['link_fixed'] = 'Link fixed with https';
$string['link_flash'] = 'Flash content';
$string['link_notvalid'] = 'The URL appears to be invalid, and does not work';
$string['link_notvalid_active'] = 'The URL looks invalid, but it works';
$string['link_youtube'] = 'Valid youtube link';
$string['link_youtube_fixed'] = 'Youtube link fixed';
$string['link_youtube_broken'] = 'Broken youtube link';
$string['showactivelinks'] = 'Show active links';
$string['hideactivelinks'] = 'Hide active links';
$string['link_broken_afterchangehttps'] = 'Broken link after applying https, works with http';
$string['process_resource_links'] = 'Processed resource links';
$string['process_version_links'] = 'Process version links';
$string['process_version_links_desc'] = 'All links in this version will be processed to detect or fix links that do not work.<br>When the process is finished, you will be redirected to the link report for this version, but the version will not be applied and you will have to apply it manually when you review it.<br>This process can take several minutes, and will not stop even if you close the tab (if you close the tab you will not be redirected when it finishes).<br>All previously generated link processing records for this version will be deleted for re-creation.<h5>Are you sure you can process the links for this version?</h5>';
$string['view_version_links'] = 'View record of processed links';
$string['processed_resource_links'] = 'Links processed correctly: ';
$string['not_processed_resource_links'] = 'The links could not be processed, please try again later: ';
$string['numfiles'] = 'Files: ';
$string['numlinks'] = 'Links: ';
$string['numlinksactive'] = 'Assets: ';
$string['numlinksfixed'] = 'Fixed: ';
$string['numlinksbroken'] = 'Broken: ';
$string['numlinksnotvalid'] = 'No valid: ';
$string['numprocessed'] = 'Processed: ';
$string['numprocessedcorrectly'] = 'Correct: ';
$string['numprocessederror'] = 'Errors: ';
$string['numprocessedwarning'] = 'No folder: ';
$string['nofolder'] = 'No folder';
$string['changesnotsaved'] = 'Save before leaving';
$string['changesnotsaved_desc'] = 'Changes have been detected and it is necessary to save before exiting.<br>If you do not want to save, close or reload this browser tab.';
$string['savechanges'] = 'Save changes';
$string['haschanges'] = '* Changes have been made';
$string['edittoc'] = 'Edition of TOC';
$string['edittoctitle'] = 'Editing table of contents';
$string['toc_list'] = 'Table of Contents';
$string['toc_list_info'] = 'In this panel you can modify the titles and order of the table of contents by dragging and dropping the titles to order them as you wish, but note that the numbering has to be added manually, as it will not be ordered automatically (you can also choose to remove the numbering as required by the content).<br><b style="color: red">Make sure that no one is editing the same resource at the same time, either the content itself or the table of contents, as this can lead to corruption of the version and loss of all the work done on it.</b><br><b style="color: red">The item marked in red corresponds to the index.html file, so it should always be the first item in the list as it is the one that loads when you enter the resource. If you make any modifications, please take this into account.</b><br><b style="color: red">The titles you edit in this panel will not be applied to the corresponding content, so they will also have to be modified in the content edition itself (in case it has a title).</b></br><b>When you are done, save the changes and apply the version so that students can view the changes.</b>';
$string['delete_node'] = 'Delete node';
$string['delete_node_desc'] = 'The selected node, and all its nested nodes, will be deleted. Are you sure you want to delete it?';
$string['addnewnode'] = 'Add new item';
$string['more_info'] = 'More information on the interface';
$string['content_here'] = '"Here is its content"';
