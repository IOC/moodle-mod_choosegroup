<?php
/* Copyright © 2010 Institut Obert de Catalunya

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

$id = optional_param('id', 0, PARAM_INT); // course_module ID

if ($id) {
    if (!$cm = get_coursemodule_from_id('choosegroup', $id)) {
        error('Course Module ID was incorrect');
    }

    if (!$course = get_record('course', 'id', $cm->course)) {
        error('Course is misconfigured');
    }

    if (!$choosegroup = get_record('choosegroup', 'id',$cm->instance)) {
        error('Course module is incorrect');
    }
} else {
    error('You must specify a course_module ID');
}

require_course_login($course, true, $cm);

$url = new moodle_url(null, array('id' => $id));

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

$can_choose = has_capability('mod/choosegroup:choose', $context, null, false);

$is_open = ((!$choosegroup->timeopen || $choosegroup->timeopen <= time()) &&
(!$choosegroup->timeclose || $choosegroup->timeclose > time()));

//Info about grups
$groups = groups_assigned($choosegroup);

//Get the group selected by student
$chosen = chosen($groups);
$data = data_submitted();

if (!empty($data->group)) {
    if ($can_choose && $is_open) {
        choose($choosegroup, $groups, (int) $data->group, $chosen);
    }
    add_to_log($course->id, 'choosegroup', 'choose', "view.php?id={$cm->id}", "$data->group", $cm->id, $USER->id);

    redirect($url->out());
} else {
    /************************ HEADER ************************/
    $strchoosegroups = get_string('modulenameplural', 'choosegroup');
    $strchoosegroup  = get_string('modulename', 'choosegroup');

    $navlinks = array();
    $navlinks[] = array('name' => $strchoosegroups,
                            'link' => 'index.php?id=' . $course->id,
                            'type' => 'activity');
    $navlinks[] = array('name' => format_string($choosegroup->name),
                            'link' => '',
                            'type' => 'activityinstance');

    $navigation = build_navigation($navlinks);

    print_header_simple(format_string($choosegroup->name), '',
    $navigation, '', '', true,
    update_module_button($cm->id, $course->id, $strchoosegroup), navmenu($course, $cm));
}
/************************ INTRO ************************/
print_box_start('generalbox', 'intro');
echo format_text($choosegroup->intro, FORMAT_HTML);
print_box_end();

/************************ PRINT DATES ************************/
if ($choosegroup->timeopen || $choosegroup->timeclose) {
    print_simple_box_start('center', '', '', 0, 'generalbox', 'dates');
    echo '<table>';
    if ($choosegroup->timeopen) {
        echo '<tr><td class="c0">'.get_string('timeopen','choosegroup').':</td>';
        echo '    <td class="c1">'.userdate($choosegroup->timeopen).'</td></tr>';
    }
    if ($choosegroup->timeclose) {
        echo '<tr><td class="c0">'.get_string('timeclose','choosegroup').':</td>';
        echo '    <td class="c1">'.userdate($choosegroup->timeclose).'</td></tr>';
    }
    echo '</table>';
    print_simple_box_end();
}

if ($can_choose) {
    $main = 'main';
    if (($chosen === false && $is_open && $choosegroup->showmembers < CHOOSEGROUP_AFTER) ||
    ($chosen !== false && $is_open && $choosegroup->showmembers < CHOOSEGROUP_CLOSED) ||
    ($chosen !== false && !$is_open && $choosegroup->showmembers < CHOOSEGROUP_NEVER)) {
        $main = 'main2';
    }

    print_box_start("generalbox boxaligncenter $main");

    if ($chosen !== false) {
        if ($is_open && $choosegroup->allowupdate) {
            echo '<p>'.get_string('groupchosen', 'choosegroup', $chosen->name).
				' ('. get_string('changegroup', 'choosegroup', $chosen->name). ' )</p>';
            print_form($groups,'chooseagroup', $choosegroup, $url, $chosen->id);
            if ($choosegroup->showmembers == CHOOSEGROUP_AFTER){
                echo '<p class="choosegroup_center"><u>'.get_string('owngroupmembers', 'choosegroup', $chosen->name).'</u></p>';
                show_members($chosen->id, $choosegroup->shownames, 'show-users-group');
            }
        } else {
            echo '<p>'.get_string('groupchosen', 'choosegroup', $chosen->name).'</p>';
            if (($is_open && $choosegroup->showmembers < CHOOSEGROUP_CLOSED) ||
            (!$is_open && $choosegroup->showmembers < CHOOSEGROUP_NEVER)) {
                echo '<p class="choosegroup_center"><u>'.get_string('owngroupmembers', 'choosegroup', $chosen->name).'</u></p>';
                if ($choosegroup->shownames){
                  show_members_col($chosen->id);
                } else {
                    show_members($chosen->id, $choosegroup->shownames, 'user-col');
                }
            }
        }
    } else {
        if ($is_open) {
            print_form($groups,'chooseagroup', $choosegroup, $url);
        } else {
            print_string('activityclosed', 'choosegroup');

        }
    }
    print_box_end();
}
print_footer($course);
add_to_log($course->id, 'choosegroup', 'view', "view.php?id={$cm->id}", '', $cm->id, $USER->id);
