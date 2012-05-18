<?php
/* Copyright © 2011 Institut Obert de Catalunya

   This file is part of Choose Group.

   Choose Group is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   Choose Group is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once('../../config.php');
require_once('lib.php');

define('CHOOSEGROUP_BEFORE', 0);
define('CHOOSEGROUP_AFTER', 1);
define('CHOOSEGROUP_CLOSED', 2);
define('CHOOSEGROUP_NEVER', 3);

$id = required_param('id', PARAM_INT);  // Course Module ID


$url = new moodle_url('/mod/choosegroup/view.php', array('id'=>$id));
$PAGE->set_url($url);

if (! $cm = get_coursemodule_from_id('choosegroup', $id)) {
    print_error('invalidcoursemodule');
}

if (! $course = $DB->get_record("course", array('id' => $cm->course))) {
    print_error('coursemisconf');
}

require_course_login($course, false, $cm);

if (!$choosegroup = $DB->get_record('choosegroup', array('id' => $cm->instance))){
    print_error('invalidcoursemodule');
}

$context = context_module::instance($cm->id);

$can_choose = has_capability('mod/choosegroup:choose', $context, null, false);

$is_open = ((!$choosegroup->timeopen || $choosegroup->timeopen <= time()) &&
(!$choosegroup->timeclose || $choosegroup->timeclose > time()));

//Info about grups
$groups = groups_assigned($choosegroup);

//Get the group selected by student
$chosen = chosen($groups);
$data = data_submitted();

//Check whether there's data submited
if (!empty($data->group)) {
    if ($can_choose && $is_open) {
        choose($choosegroup, $groups, (int) $data->group, $chosen);
    }
    add_to_log($course->id, 'choosegroup', 'choose', "view.php?id={$cm->id}", "$data->group", $cm->id, $USER->id);

    redirect($url->out());
}
/************************ HEADER ************************/
add_to_log($course->id, 'choosegroup', 'view', "view.php?id={$cm->id}", $choosegroup->name, $cm->id);
$strchoosegroups = get_string('modulenameplural', 'choosegroup');
$strchoosegroup  = get_string('modulename', 'choosegroup');

$PAGE->set_url('/mod/choosegroup/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($choosegroup->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Output starts here
echo $OUTPUT->header();

/************************ INTRO ************************/
if ($choosegroup->intro) {
    // Conditions to show the intro can change to look for own settings or whatever
    echo $OUTPUT->box(format_module_intro('choosegroup', $choosegroup, $cm->id), 'generalbox', 'intro');
}
/************************ PRINT DATES ************************/
if ($is_open && ($choosegroup->timeopen || $choosegroup->timeclose)) {
    echo $OUTPUT->box_start('generalbox boxaligncenter', 'dates');
    $table = new html_table();
    $row = new html_table_row();
    $columns = array();
    $rows = array();
    if ($choosegroup->timeopen) {
        $cell = new html_table_cell();
        $cell->text = get_string('timeopen','choosegroup').':';
        $cell->attributes = array('class'=>'c0');
        $columns[] = $cell;
        $cell = new html_table_cell();
        $cell->text = userdate($choosegroup->timeopen);
        $cell->attributes = array('class'=>'c1');
        $columns[] = $cell;
        $row->cells = $columns;
    }
    if ($choosegroup->timeclose) {
        $cell = new html_table_cell();
        $cell->text = get_string('timeclose','choosegroup').':';
        $cell->attributes = array('class'=>'c0');
        $columns[] = $cell;
        $cell = new html_table_cell();
        $cell->text = userdate($choosegroup->timeclose);
        $cell->attributes = array('class'=>'c1');
        $columns[] = $cell;
        $row->cells = $columns;
    }
    $rows[] = $row;
    $table->data = $rows;
    $table->attributes['class'] = 'choosegroup-table';
    echo html_writer::table($table);
    echo $OUTPUT->box_end();
}

if ($can_choose) {
    $renderer = $PAGE->get_renderer('mod_choosegroup');
    $main = 'main';
    if (($chosen === false && $is_open && $choosegroup->showmembers < CHOOSEGROUP_AFTER) ||
    ($chosen !== false && $is_open && $choosegroup->showmembers < CHOOSEGROUP_CLOSED) ||
    ($chosen !== false && !$is_open && $choosegroup->showmembers < CHOOSEGROUP_NEVER)) {
        $main = 'main2';
    }
    if ($chosen !== false && !$is_open){
        echo $OUTPUT->box(get_string('activityclosed', 'choosegroup', userdate($choosegroup->timeclose)), "generalbox boxaligncenter main");
    }

    echo $OUTPUT->box_start("generalbox boxaligncenter $main");

    if ($chosen !== false) {
        if ($is_open && $choosegroup->allowupdate) {
            echo '<p>'.get_string('groupchosen', 'choosegroup', $chosen->name).
				' ('. get_string('changegroup', 'choosegroup', $chosen->name). ' )</p>';
            echo $renderer->print_form($groups,'chooseagroup', $choosegroup, $url, $chosen->id);
            if ($choosegroup->showmembers == CHOOSEGROUP_AFTER){
                echo '<p class="choosegroup_center"><u>'.get_string('owngroupmembers', 'choosegroup', $chosen->name).'</u></p>';
                echo show_members($chosen->id, $choosegroup->shownames, 'show-users-group');
            }
        } else {
            echo '<p>'.get_string('groupchosen', 'choosegroup', $chosen->name).'</p>';
            if (($is_open && $choosegroup->showmembers < CHOOSEGROUP_CLOSED) ||
            (!$is_open && $choosegroup->showmembers < CHOOSEGROUP_NEVER)) {
                echo '<p class="choosegroup_center"><u>'.get_string('owngroupmembers', 'choosegroup', $chosen->name).'</u></p>';
                if ($choosegroup->shownames){
                  echo show_members_col($chosen->id);
                } else {
                  echo show_members($chosen->id, $choosegroup->shownames, 'user-col');
                }
            }
        }
    } else {
        if ($is_open) {
            echo $renderer->print_form($groups,'chooseagroup', $choosegroup, $url);
        } else {
            print_string('activityclosed', 'choosegroup', userdate($choosegroup->timeclose));
        }
    }
    echo $OUTPUT->box_end();
}
echo $OUTPUT->footer();
