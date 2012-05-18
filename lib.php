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

/**
 * Build and returns groups array with several info about course groups
 *
 * @global object
 * @global object
 * @param object $choosegroup
 * @return array Info about groups
 */
function groups_assigned($choosegroup) {
    global $COURSE, $DB;

    if(!$groupsok = $DB->get_records_sql("SELECT g.groupid,g.maxlimit
                              FROM {choosegroup_group} g
                         	 JOIN {choosegroup} c ON (g.choosegroupid = c.id
                         	 	AND c.course = ?)
                         	 AND g.choosegroupid = ?", array($COURSE->id, $choosegroup->id))){
        return false;
    }
    if (!$records = $DB->get_records('groups', array('courseid' => $choosegroup->course), 'name')) {
        return false;
    }

    $groups = array();
    foreach ($records as $record){
        $group = new stdClass();
        $group->id = $record->id;
        $group->name = $record->name;
        $groups[$group->id] = $group;
    }

    foreach ($groupsok as $groupok) {
        if (array_key_exists($groupok->groupid, $groups)){
            $groups[$groupok->groupid]->members = $DB->count_records('groups_members',
                                             array('groupid' => $groupok->groupid));
            $groups[$groupok->groupid]->vacancies = max(array(0, $groupok->maxlimit - $groups[$groupok->groupid]->members));
            $groups[$groupok->groupid]->maxlimit = $groupok->maxlimit;
        }
    }

    return $groups;
}

/**
 * Update user's new assignment group
 *
 * @global object
 * @global object
 * @param object $choosegroup
 * @param array $groups
 * @param int $groupid
 * @param object $currentgroup
 */
function choose($choosegroup, $groups, $groupid, $currentgroup) {
    global $DB, $USER;

    if (!confirm_sesskey()){
        return;
    }

    if (!$groups or !isset($groups[$groupid])) {
        return;
    }

    if (($groups[$groupid]->maxlimit > 0) &&
    $groups[$groupid]->members >= $groups[$groupid]->maxlimit) {
        return;
    }

    //Firstly remove previous group assignment
    if ($currentgroup) {
        $DB->delete_records('groups_members', array('groupid' => $currentgroup->id, 'userid' => $USER->id));
    }

    $record = new stdClass();
    $record->groupid = $groupid;
    $record->userid = $USER->id;
    $record->timeadded = time();
    $DB->insert_record('groups_members', $record);
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @global object
 * @param object $choosegroup
 * @return int intance id
 */
function choosegroup_add_instance($choosegroup) {
    global $DB;
    $choosegroup->timecreated = time();
    $choosegroup->timemodified = time();
    $choosegroup->id = $DB->insert_record('choosegroup', $choosegroup);
    foreach ($choosegroup->lgroup as $key => $value) {
        if (isset($choosegroup->ugroup[$key]) || isset($value) && $value <> '0') {
            $group = new stdClass();
            $group->choosegroupid = $choosegroup->id;
            $group->groupid = $key;
            $group->maxlimit = isset($choosegroup->ugroup[$key])?0:$value;
            $DB->insert_record("choosegroup_group", $group);
        }
    }
    return $choosegroup->id;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object
 * @param int $id
 * @return bool success
 */
function choosegroup_delete_instance($id) {
    global $DB;
    $DB->delete_records('choosegroup_group', array('choosegroupid' => $id));
    return $DB->delete_records('choosegroup', array('id' => $id));
}


/**
* @uses FEATURE_GROUPS
* @uses FEATURE_GROUPINGS
* @uses FEATURE_GROUPMEMBERSONLY
* @uses FEATURE_MOD_INTRO
* @uses FEATURE_COMPLETION_TRACKS_VIEWS
* @uses FEATURE_GRADE_HAS_GRADE
* @uses FEATURE_GRADE_OUTCOMES
* @param string $feature FEATURE_xx constant for requested feature
* @return mixed True if module supports feature, null if doesn't know
*/
function choosegroup_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES:    return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

/**
 * Returns all groups in a specified course, if boolean is true returns only group's id
 *
 * @param int $courseid
 * @param bool $onlyids
 * @return object
 */
function choosegroup_detected_groups($courseid, $onlyids = false) {
    global $DB;

    $groups = groups_get_all_groups($courseid);
    if (!is_array($groups)){
        $groups = array();
    }
    if ($onlyids) {
        $groups = array_keys($groups);
    }
    return $groups;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object
 * @param object $choosegroup
 * @return bool success
 */
function choosegroup_update_instance($choosegroup) {
    global $DB;
    $choosegroup->id = $choosegroup->instance;
    $choosegroup->timemodified = time();
    if (isset($choosegroup->lgroup)){
        //update, delete or insert groups
        foreach ($choosegroup->lgroup as $key => $value) {
            $group = new stdClass();
            $group->groupid = $key;
            $group->choosegroupid = $choosegroup->id;
            $group->maxlimit = (isset($choosegroup->ugroup[$key]))?0:max(0, min(9999, $value));
            if (isset($choosegroup->groupid[$key]) && !empty($choosegroup->groupid[$key])){//existing choosegroup_group record
                $group->id = $choosegroup->groupid[$key];
                if (isset($choosegroup->ugroup[$key]) || isset($value) && $value <> '0') {
                    $DB->update_record("choosegroup_group", $group);
                } else { //empty old option - needs to be deleted.
                    $DB->delete_records("choosegroup_group", array("id" => $group->id));
                }
            } else {
                if (isset($choosegroup->ugroup[$key]) || isset($value) && $value <> '0') {
                    $DB->insert_record("choosegroup_group", $group);
                }
            }
        }
    }
    return $DB->update_record('choosegroup', $choosegroup);
}

/**
 * Return user group info, otherwise returns false
 *
 * @global object
 * @global object
 * @param object $groups
 * @return object if user has a group, bool false if not.
 */
function chosen($groups){
    global $DB, $USER;
    if (empty($groups)){
        return false;
    }
    $info = new stdClass();
    foreach ($groups as $id => $group){
        if($DB->record_exists('groups_members', array('groupid' => $id, 'userid' => $USER->id))) {
            $info->id = $id;
            $info->name = $group->name;
            return $info;
        }
    }
    return false;
}


/**
 * Show members for specified group
 *
 * @global object
 * @global object
 * @global object
 * @global object
 * @param int $groupid
 * @param bool $shownames
 * @param string $class
 */
function show_members($groupid, $shownames, $class='users-group') {
    global $CFG, $DB, $COURSE, $OUTPUT;

    $members = $DB->get_fieldset_select('groups_members', 'userid', 'groupid = '. $groupid);
    $output = '<div class="'.$class.'">';
    if (!empty($members)) {
        $rs = $DB->get_recordset_list('user', 'id', $members, 'lastname');
        if ($rs->valid()) {
            foreach ($rs as $user) {
                $class = ($shownames)?'user-group-names':'user-group';
                $output .= '<div class="'.$class.'">';
                $output .= $OUTPUT->user_picture($user, array('courseid' => $COURSE->id));
                if ($shownames) {
                    $output .= '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$COURSE->id.'">'.fullname($user).'</a>';
                }
                $output .= '</div>';
            }
        }
        $rs->close();
    } else {
        $output .= get_string('nomembers', 'choosegroup');
    }
    $output .= '<div class="choosegroup_clear"></div>';
    $output .= "</div>";

    return $output;
}

/**
 * Show members in columns from specified group
 *
 * @global object
 * @global object
 * @global object
 * @global object
 * @param int $groupid
 */
function show_members_col($groupid) {
    global $CFG, $COURSE, $DB, $OUTPUT;

    $output = '';
    $position = 0;
    $col1 = '';
    $col2 = '';
    $col3 = '';

    $members = $DB->get_fieldset_select('groups_members', 'userid', 'groupid = '. $groupid);
    if (!empty($members)) {
        $rs = $DB->get_recordset_list('user', 'id', $members, 'lastname');
        if ($rs->valid()) {
            foreach ($rs as $user) {
                $txt = '<div class="user-col">'.
                       '<div class="user-col-pic">'.
                       $OUTPUT->user_picture($user, array('courseid' => $COURSE->id)).
                       '</div>'.
                       '<div class="user-col-name">'.
                       '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$COURSE->id.'">'.fullname($user,true).'</a>'.
                       '</div>'.
                       '</div>'.
                       '<div class="choosegroup_clear"></div>';
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
        }
        $rs->close();
        $output .= '<div class="user-group-col">'.$col1.'</div>';
        $output .= '<div class="user-group-col">'.$col2.'</div>';
        $output .= '<div class="user-group-col">'.$col3.'</div>';
    } else {
        $output .= get_string('nomembers', 'choosegroup');
    }
    $output .= '<div class="choosegroup_clear"></div>';

    return $output;
}

/**
 * This function gets run whenever group is deleted from course
 *
 * @param object $object
 */
function choosegroup_group_deleted($object){
//id, courseid, name, description, timecreated, timemodified, picture
global $DB;

$params = array('courseid' => $object->courseid, 'groupid' => $object->id);
$choosegroupselect = "IN (SELECT c.id FROM {choosegroup} c WHERE c.course = :courseid)";

$DB->delete_records_select('choosegroup_group', "groupid = :groupid AND choosegroupid $choosegroupselect", $params);
}