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

function groups_assigned($choosegroup) {
    global $COURSE;

    $groupsok = get_field('choosegroup', 'groups', 'id', $choosegroup->id, 'course', $COURSE->id);
    if (!empty($groupsok)) {
        $groupsok = explode(',', $groupsok);
    }
    else{
        $groupsok = array();
    }

    if (!$records = get_records('groups', 'courseid', $choosegroup->course, 'name')) {
        return false;
    }

    $groups = array();
    foreach ($records as $record) {
        if (in_array($record->id, $groupsok)){
            $record->members = count_records('groups_members',
                                             'groupid', $record->id);
            $record->vacancies = max(0, $choosegroup->grouplimit - $record->members);
            $groups[$record->id] = $record;
        }
    }
    return $groups;
}

function choose($choosegroup, $groups, $groupid, $currentgroup) {
    global $COURSE, $USER;

    if (!confirm_sesskey()){
        return;
    }

    if (!$groups or !isset($groups[$groupid])) {
        return;
    }

    if ($choosegroup->grouplimit &&
    $groups[$groupid]->members >= $choosegroup->grouplimit) {
        return;
    }

    //Firstly remove previous group assignment
    if ($currentgroup) {
        delete_records('groups_members', 'groupid', $currentgroup->id, 'userid', $USER->id);
    }

    $record = (object) array('groupid' => $groupid,
                             'userid' => $USER->id,
                             'timeadded' => time());
    insert_record('groups_members', $record);
}

function choosegroup_add_instance($record) {
    $record->grouplimit = max(0, min(9999, $record->grouplimit));
    $record->timecreated = time();
    $record->timemodified = time();
    $record->groups = choosegroup_prepare_groups($record);
    return insert_record('choosegroup', $record);
}

function choosegroup_delete_instance($id) {
    return delete_records('choosegroup', 'id', $id);
}

function choosegroup_detected_groups($courseid) {
    $records = get_records('groups', 'courseid', $courseid, 'name');
    if (!$records) {
        return array();
    }
    return $records;
}

function choosegroup_update_instance($record) {
    $record->id = $record->instance;
    $record->grouplimit = max(0, min(9999, $record->grouplimit));
    $record->timemodified = time();
    $record->groups = choosegroup_prepare_groups($record);
    return update_record('choosegroup', $record);
}

function choosegroup_prepare_groups($record) {
    $groups = choosegroup_detected_groups($record->course);
    $groupsid = '';
    foreach ($groups as $group){
        $name = "group$group->id";
        if (isset($record->$name) && $record->$name) {
            if (!empty($groupsid)){
                $groupsid .= ",$group->id";
            } else {
                $groupsid = "$group->id";
            }
        }
    }
    return $groupsid;
}

function chosen($groups){
    global $USER;
    $info = new stdClass();
    foreach ($groups as $id=>$group){
        if(record_exists('groups_members','groupid', $id, 'userid', $USER->id)) {
            $info->id = $id;
            $info->name = $group->name;
            return $info;
        }
    }
    return false;
}

function print_form($groups, $message, $choosegroup, $url, $groupid = false) {
    if (empty($groups)) {
        print_string('nogroups', 'choosegroup');
    } else {
        echo '<div class="choosegroup_left"><b>' . get_string($message, 'choosegroup') . ':</b></div>';
        if ($choosegroup->showmembers < CHOOSEGROUP_AFTER) {
            echo '<div class="choosegroup_right"><b>' . get_string('groupmembers', 'choosegroup') . ':</b></div>';
        }
        echo '<div class="choosegroup_clear"></div>';
        echo '<form method="post" action="' . s($url->out()) . '">'
        . '<input type="hidden" name="sesskey" value="'
        . sesskey() . '"/>';
        foreach ($groups as $group) {
            $vacancies = '';
            $disabled = '';
            $dimmed = '';
            if ($choosegroup->grouplimit) {
                if (!$group->vacancies) {
                    $disabled = 'disabled="disabled"';
                    $dimmed = 'class="dimmed"';
                    $vacancies = '(' .  get_string('novacancies', 'choosegroup'). ')';
                }
                else if ($group->vacancies > 1) {
                    $vacancies = '(' .  get_string('vacancies', 'choosegroup',
                    $group->vacancies) . ')';
                } else {
                    $vacancies = '(' .  get_string('vacancy', 'choosegroup',
                    $group->vacancies) . ')';
                }
            }
            	
            if ($groupid && $group->id === $groupid) {
                $disabled = 'disabled="disabled"';
                $dimmed = 'class="dimmed"';
                $vacancies = '(' . get_string('currentgroup', 'choosegroup') . ')';
            }
            	
            $checkbox = "<input $disabled type=\"radio\" name=\"group\" "
            . "id=\"group-{$group->id}\" value=\"{$group->id}\" />";
            $label = "<label $dimmed for=\"group-{$group->id}\">"
            . s($group->name) . " $vacancies</label>";
            $members = '';
            $hr = '';
            if ($choosegroup->showmembers < CHOOSEGROUP_AFTER) {
                $members = show_members($group->id, $choosegroup->shownames);
                $hr ='<hr />';
            }
            echo "<div class=\"choosegroup_left\">$checkbox $label</div> $members";
            echo "<div class=\"choosegroup_clear\">$hr</div>";
        }
        echo '<input type="submit" value="' . get_string('submit') . '"/>'
        .'</form>';
    }
}

function show_members($groupid, $shownames, $class='users-group') {
    global $CFG, $COURSE;

    $members = get_fieldset_select('groups_members', 'userid', 'groupid = '. $groupid);
    echo '<div class="'.$class.'">';
    if (!empty($members)) {
        $userids = implode(",", $members);
        $rs = get_recordset_list('user', 'id', $userids, 'lastname');
        while ($user = rs_fetch_next_record($rs)) {
            $class = ($shownames)?'user-group-names':'user-group';
            echo '<div class="'.$class.'">';
            print_user_picture($user, $COURSE->id);
            if ($shownames) {
                echo '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$COURSE->id.'">'.fullname($user).'</a>';
            }
            echo '</div>';
        }
        rs_close($rs);
    } else {
        print_string('nomembers', 'choosegroup');
    }
    echo '<div class="choosegroup_clear"></div>';
    echo "</div>";
}

function show_members_col($groupid) {
    global $CFG, $COURSE;

    $position = 0;
    $col1 = '';
    $col2 = '';
    $col3 = '';

    $members = get_fieldset_select('groups_members', 'userid', 'groupid = '. $groupid);
    if (!empty($members)) {
        $userids = implode(",", $members);
        $rs = get_recordset_list('user', 'id', $userids, 'lastname');
        while ($user = rs_fetch_next_record($rs)) {
            $txt = '<div class="user-col">'
                   .'<div class="user-col-pic">'
                   .print_user_picture($user, $COURSE->id, null, 0, true)
                   .'</div>'
                   .'<div class="user-col-name">'
                   .'<a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$COURSE->id.'">'.fullname($user,true).'</a>'
                   .'</div>'
                   .'</div>'
                   .'<div class="choosegroup_clear"></div>';
            $position += 1;
            if ($position === 1) {
                  $col1 .= $txt;
            } elseif ($position === 2) {
                  $col2 .= $txt;
            } else {
                  $col3 .= $txt;
                  $position = 0;
            }
        }
        rs_close($rs);
        echo '<div class="user-group-col">'.$col1.'</div>';
        echo '<div class="user-group-col">'.$col2.'</div>';
        echo '<div class="user-group-col">'.$col3.'</div>';
    } else {
        print_string('nomembers', 'choosegroup');
    }
    echo '<div class="choosegroup_clear"></div>';
}
