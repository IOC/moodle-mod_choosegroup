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
 * @package mod
 * @subpackage choosegroup
 * @copyright 2013 Institut Obert de Catalunya
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Marc Català <reskit@gmail.com>
 */

/**
 * Define all the restore steps that will be used by the restore_choosegroup_activity_task
 */

/**
 * Structure step to restore one choosegroup activity
 */
class restore_choosegroup_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('choosegroup', '/activity/choosegroup');
        $paths[] = new restore_path_element('choosegroup_group', '/activity/choosegroup/groups/group');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_choosegroup($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timeopen = $this->apply_date_offset($data->timeopen);
        $data->timeclose = $this->apply_date_offset($data->timeclose);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // insert the choosegroup record
        $newitemid = $DB->insert_record('choosegroup', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_choosegroup_group($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->choosegroupid = $this->get_new_parentid('choosegroup');
        $data->groupid = $this->get_mappingid('group', $data->groupid);

        $newitemid = $DB->insert_record('choosegroup_group', $data);
        $this->set_mapping('choosegroup_group', $oldid, $newitemid);
    }

    protected function after_execute() {
        // Add choosegroup related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_choosegroup', 'intro', null);
    }
}
