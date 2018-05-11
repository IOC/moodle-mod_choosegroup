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
 * @package    mod
 * @subpackage choosegroup
 * @copyright  2013 Institut Obert de Catalunya
 * @author     Albert Gasset <albert.gasset@gmail.com>
 * @author     Marc Català <reskit@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/mod/choosegroup/lib.php');

$id = required_param('id', PARAM_INT);   // Course id.

$PAGE->set_url('/mod/choosegroup/index.php', array('id' => $id));

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

require_course_login($course);
$PAGE->set_pagelayout('incourse');

$event = \mod_choosegroup\event\course_module_instance_list_viewed::create(array(
    'context' => context_course::instance($course->id)
));
$event->trigger();


// Get all required stringschoosegroup.

$strchoosegroups = get_string('modulenameplural', 'choosegroup');
$strchoosegroup  = get_string('modulename', 'choosegroup');

// Print the header.
$PAGE->set_title($strchoosegroups);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strchoosegroups);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($strchoosegroups));


// Get all the appropriate data.

if (! $choosegroups = get_all_instances_in_course('choosegroup', $course)) {
    notice(get_string('thereareno', 'moodle', $strchoosegroups), "../../course/view.php?id=$course->id");
}

$usesections = course_format_uses_sections($course->format);

$groupnames = array();

$groups = groups_get_user_groups($course->id);

if ($groups[0]) {
    foreach ($groups[0] as $group) {
        $groupnames[] = groups_get_group_name($group);
    }
} else {
    $groupnames[] = get_string('groupsnone');
}

// Print the list of instances.

$table = new html_table();

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_'.$course->format);
    $table->head  = array ($strsectionname, get_string("name"), get_string("group"));
    $table->align = array ("center", "left", "left");
} else {
    $table->head  = array (get_string("name"), get_string("group"));
    $table->align = array ("left", "left");
}

$currentsection = "";

foreach ($choosegroups as $choosegroup) {
    if ($usesections) {
        $printsection = "";
        if ($choosegroup->section !== $currentsection) {
            if ($choosegroup->section) {
                $printsection = get_section_name($course, $choosegroup->section);
            }
            if ($currentsection !== "") {
                $table->data[] = 'hr';
            }
            $currentsection = $choosegroup->section;
        }
    }
    if (!$choosegroup->visible) {
        // Show dimmed if the mod is hidden.
        $link = '<a class="dimmed" href="view.php?id='.$choosegroup->coursemodule.'">'.format_string($choosegroup->name).'</a>';
    } else {
        // Show normal if the mod is visible.
        $link = '<a href="view.php?id='.$choosegroup->coursemodule.'">'.format_string($choosegroup->name).'</a>';
    }

    if ($usesections) {
        $table->data[] = array ($printsection, $link, implode(' ', $groupnames));
    } else {
        $table->data[] = array ($link);
    }
}

echo "<br />";
echo html_writer::table($table);

echo $OUTPUT->footer();
