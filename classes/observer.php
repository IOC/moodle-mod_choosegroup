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
 * Event observers used in choosegroup.
 *
 * @package    mod_choosegroup
 * @copyright  2014 Institut Obert de Catalunya
 * @author     Marc Català <reskit@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for mod_choosegroup.
 */
class mod_choosegroup_observer {

    /**
     * Triggered via group_deleted event.
     *
     * @param \core\event\group_deleted $event
     */
    public static function group_deleted(\core\event\group_deleted $event) {
        global $DB;

        $group = $event->get_record_snapshot('groups', $event->objectid);
        $params = array('courseid' => $group->courseid, 'groupid' => $group->id);
        $choosegroupselect = "IN (SELECT c.id FROM {choosegroup} c WHERE c.course = :courseid)";

        $DB->delete_records_select('choosegroup_group', "groupid = :groupid AND choosegroupid $choosegroupselect", $params);
    }
}
