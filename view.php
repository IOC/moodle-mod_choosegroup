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
 * Main view for choosegroup mod
 *
 * @package    mod
 * @subpackage choosegroup
 * @copyright  2013 Institut Obert de Catalunya
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Albert Gasset <albert.gasset@gmail.com>
 * @author     Marc Català <reskit@gmail.com>
 * @author     Manuel Cagigas <sedras@gmail.com>
 */

require_once('../../config.php');
require_once('lib.php');
require_once($CFG->libdir.'/completionlib.php');


define('CHOOSEGROUP_BEFORE', 0);
define('CHOOSEGROUP_AFTER', 1);
define('CHOOSEGROUP_CLOSED', 2);
define('CHOOSEGROUP_NEVER', 3);

$id = required_param('id', PARAM_INT);  // Course Module ID.


$url = new moodle_url('/mod/choosegroup/view.php', array('id' => $id));
$PAGE->set_url($url);

if (! $cm = get_coursemodule_from_id('choosegroup', $id)) {
    print_error('invalidcoursemodule');
}

if (! $course = $DB->get_record("course", array('id' => $cm->course))) {
    print_error('coursemisconf');
}

require_course_login($course, false, $cm);

if (!$choosegroup = $DB->get_record('choosegroup', array('id' => $cm->instance))) {
    print_error('invalidcoursemodule');
}

$context = context_module::instance($cm->id);

$canchoose = has_capability('mod/choosegroup:choose', $context, null, false);

$now = time();

$isopen = ((!$choosegroup->timeopen || $choosegroup->timeopen <= $now) &&
(!$choosegroup->timeclose || $choosegroup->timeclose > $now));

// Info about grups.
$groups = choosegroup_groups_assigned($choosegroup);

// Get the group selected by student.
$chosen = choosegroup_chosen($groups, $USER->id);
$data = data_submitted();

// Check whether there's data submited.
if (!empty($data->group)) {
    if ($canchoose && $isopen) {
        choosegroup_choose($choosegroup, $groups, (int) $data->group, $chosen);
        // Update completion state.
        $completion = new completion_info($course);
        if ($completion->is_enabled($cm) && $choosegroup->completionchoosegroup) {
            $completion->update_state($cm, COMPLETION_COMPLETE);
        }

        $eventdata = array();
        $eventdata['context'] = $context;
        $eventdata['objectid'] = $choosegroup->id;
        $eventdata['userid'] = $USER->id;
        $eventdata['courseid'] = $course->id;
        $eventdata['other'] = array();
        $eventdata['other']['group'] = (int) $data->group;

        $event = \mod_choosegroup\event\choosing_group::create($eventdata);
        $event->trigger();
    }

    redirect($url->out());
}
/************************ HEADER ************************/
$event = \mod_choosegroup\event\course_module_viewed::create(array(
    'objectid' => $choosegroup->id,
    'context' => $context,
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->trigger();

$strchoosegroups = get_string('modulenameplural', 'choosegroup');
$strchoosegroup  = get_string('modulename', 'choosegroup');

$PAGE->set_url('/mod/choosegroup/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($choosegroup->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// View for completion.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Output starts here.
echo $OUTPUT->header();

/************************ INTRO ************************/
if ($choosegroup->intro) {
    // Conditions to show the intro can change to look for own settings or whatever.
    echo $OUTPUT->box(format_module_intro('choosegroup', $choosegroup, $cm->id), 'generalbox', 'intro');
}
/************************ PRINT DATES ************************/
if ($isopen && ($choosegroup->timeopen || $choosegroup->timeclose)) {
    echo $OUTPUT->box_start('generalbox boxaligncenter', 'dates');
    $table = new html_table();
    $row = new html_table_row();
    $columns = array();
    $rows = array();
    if ($choosegroup->timeopen) {
        $cell = new html_table_cell();
        $cell->text = get_string('timeopen', 'choosegroup').':';
        $cell->attributes = array('class' => 'c0');
        $columns[] = $cell;
        $cell = new html_table_cell();
        $cell->text = userdate($choosegroup->timeopen);
        $cell->attributes = array('class' => 'c1');
        $columns[] = $cell;
        $row->cells = $columns;
    }
    if ($choosegroup->timeclose) {
        $cell = new html_table_cell();
        $cell->text = get_string('timeclose', 'choosegroup').':';
        $cell->attributes = array('class' => 'c0');
        $columns[] = $cell;
        $cell = new html_table_cell();
        $cell->text = userdate($choosegroup->timeclose);
        $cell->attributes = array('class' => 'c1');
        $columns[] = $cell;
        $row->cells = $columns;
    }
    $rows[] = $row;
    $table->data = $rows;
    $table->attributes['class'] = 'choosegroup-table';
    echo html_writer::table($table);
    echo $OUTPUT->box_end();
}

if ($canchoose) {
    $renderer = $PAGE->get_renderer('mod_choosegroup');
    $main = 'main';
    if (($chosen === false && $isopen && $choosegroup->showmembers < CHOOSEGROUP_AFTER) ||
    ($chosen !== false && $isopen && $choosegroup->showmembers < CHOOSEGROUP_CLOSED) ||
    ($chosen !== false && !$isopen && $choosegroup->showmembers < CHOOSEGROUP_NEVER)) {
        $main = 'main2';
    }
    if ($chosen !== false && !$isopen) {
        if ($choosegroup->timeopen > $now) {
            echo $OUTPUT->box(get_string('notopenyet', 'choosegroup', userdate($choosegroup->timeopen)), "generalbox boxaligncenter main");
        } else {
            echo $OUTPUT->box(get_string('activityclosed', 'choosegroup', userdate($choosegroup->timeclose)), "generalbox boxaligncenter main");
        }
    }

    echo $OUTPUT->box_start("boxaligncenter $main");

    if ($chosen !== false) {
        if ($isopen && $choosegroup->allowupdate) {
            echo '<p>'.get_string('groupchosen', 'choosegroup', $chosen->name).
                ' ('. get_string('changegroup', 'choosegroup', $chosen->name). ' )</p>';
            echo $renderer->print_form($groups, 'chooseagroup', $choosegroup, $url, $chosen->id);
            if ($choosegroup->showmembers == CHOOSEGROUP_AFTER) {
                echo '<p class="choosegroup_center"><u>'.get_string('owngroupmembers', 'choosegroup', $chosen->name).'</u></p>';
                echo choosegroup_show_members($chosen->id, $choosegroup->shownames, 'show-users-group');
            }
        } else {
            echo '<p>'.get_string('groupchosen', 'choosegroup', $chosen->name).'</p>';
            if (($isopen && $choosegroup->showmembers < CHOOSEGROUP_CLOSED) ||
            (!$isopen && $choosegroup->showmembers < CHOOSEGROUP_NEVER)) {
                echo '<p class="choosegroup_center"><u>'.get_string('owngroupmembers', 'choosegroup', $chosen->name).'</u></p>';
                if ($choosegroup->shownames) {
                    echo choosegroup_show_members_col($chosen->id);
                } else {
                    echo choosegroup_show_members($chosen->id, $choosegroup->shownames, 'user-col');
                }
            }
        }
    } else {
        if ($isopen) {
            echo $renderer->print_form($groups, 'chooseagroup', $choosegroup, $url);
        } else if ($choosegroup->timeopen > $now) {
            print_string('notopenyet', 'choosegroup', userdate($choosegroup->timeopen));
        } else {
            print_string('activityclosed', 'choosegroup', userdate($choosegroup->timeclose));
        }
    }
    echo $OUTPUT->box_end();
}
echo $OUTPUT->footer();
