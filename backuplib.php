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

function choosegroup_backup_mods($bf,$preferences) {
        
    global $CFG;

    $status = true;

    //Iterate over choosegroup table
    $choosegroups = get_records ("choosegroup","course",$preferences->backup_course,"id");
    if ($choosegroups) {
        foreach ($choosegroups as $choosegroup) {
            if (backup_mod_selected($preferences,'choosegroup',$choosegroup->id)) {
                $status = choosegroup_backup_one_mod($bf,$preferences,$choosegroup);
            }
        }
    }
    return $status;
}

function choosegroup_backup_one_mod($bf,$preferences,$choosegroup) {

    global $CFG;
    
    if (is_numeric($choosegroup)) {
        $choosegroup = get_record('choosegroup','id',$choosegroup);
    }
    
    $status = true;

    //Start mod
    fwrite ($bf,start_tag("MOD",3,true));
        
    //Print choosegroup data
    fwrite ($bf,full_tag("ID",4,false,$choosegroup->id));
    fwrite ($bf,full_tag("MODTYPE",4,false,"choosegroup"));
    fwrite ($bf,full_tag("NAME",4,false,$choosegroup->name));
    fwrite ($bf,full_tag("INTRO",4,false,$choosegroup->intro));
    fwrite ($bf,full_tag("GROUPS",4,false,choosegroup_groups_name($choosegroup->groups)));
    fwrite ($bf,full_tag("GROUPLIMIT",4,false,$choosegroup->grouplimit));
    fwrite ($bf,full_tag("SHOWMEMBERS",4,false,$choosegroup->showmembers));
    fwrite ($bf,full_tag("ALLOWUPDATE",4,false,$choosegroup->allowupdate));
    fwrite ($bf,full_tag("TIMEOPEN",4,false,$choosegroup->timeopen));
    fwrite ($bf,full_tag("TIMECLOSE",4,false,$choosegroup->timeclose));
    fwrite ($bf,full_tag("TIMECREATED",4,false,$choosegroup->timecreated));
    fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$choosegroup->timemodified));

    //End mod
    $status = fwrite ($bf,end_tag("MOD",3,true));

    return $status;
}
    
//Return an array of info (name,value)
function choosegroup_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {

    if (!empty($instances) && is_array($instances) && count($instances)) {
        $info = array();
        foreach ($instances as $id => $instance) {
            $info += choosegroup_check_backup_mods_instances($instance,$backup_unique_code);
        }
        return $info;
    }
    //First the course data
    $info[0][0] = get_string("modulenameplural","choosegroup");
    if ($ids = choice_ids ($course)) {
        $info[0][1] = count($ids);
    } else {
        $info[0][1] = 0;
    }

    return $info;
}
    
//Return an array of info (name,value)
function choosegroup_check_backup_mods_instances($instance,$backup_unique_code) {
    //First the course data
    $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
    $info[$instance->id.'0'][1] = '';

    return $info;
}
    
// INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

//Returns an array of choosegroups id
function choosegroup_ids ($course) {

    global $CFG;

    return get_records_sql ("SELECT a.id, a.course
                             FROM {$CFG->prefix}choosegroup a
                             WHERE a.course = '$course'");
}
    
function choosegroup_groups_name($groups) {
    $names = '';
    if (!empty($groups)){
        $groups = explode(',', $groups);
        foreach ($groups as $group){
            if (empty($names)) {
                $names = get_field('groups','name','id',$group);
            } else {
                $names .= ',' . get_field('groups','name','id',$group);
            }
        }
    }
    return $names;
}
