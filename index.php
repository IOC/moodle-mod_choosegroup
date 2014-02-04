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
 * @author     Albert Gasset <albert.gasset@gmail.com>
 * @author     Marc Català <reskit@gmail.com>
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT);   // course

$PAGE->set_url('/mod/choosegroup/index.php', array('id' => $id));

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

require_course_login($course);
$PAGE->set_pagelayout('incourse');
add_to_log($course->id, 'choosegroup', 'view all', "index.php?id=$course->id", '');


// Get all required stringschoosegroup

$strchoosegroups = get_string('modulenameplural', 'choosegroup');
$strchoosegroup  = get_string('modulename', 'choosegroup');

// Print the header
$PAGE->set_title($strchoosegroups);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strchoosegroups);
echo $OUTPUT->header();


// Get all the appropriate data

if (! $choosegroups = get_all_instances_in_course('choosegroup', $course)) {
    notice(get_string('thereareno', 'moodle', $strchoosegroups), "../../course/view.php?id=$course->id");
}


$usesections = course_format_uses_sections($course->format);
if ($usesections) {
    $sections = get_all_sections($course->id);
}

// Print the list of instances (your module will probably extend this)

$timenow  = time();
$strname  = get_string('name');
$strweek  = get_string('week');
$strtopic = get_string('topic');

$table = new html_table();

if ($course->format == 'weeks') {
    $table->head  = array ($strweek, $strname);
    $table->align = array ('center', 'left');
} else if ($course->format == 'topics') {
    $table->head  = array ($strtopic, $strname);
    $table->align = array ('center', 'left', 'left', 'left');
} else {
    $table->head  = array ($strname);
    $table->align = array ('left', 'left', 'left');
}

foreach ($choosegroups as $choosegroup) {
    if (!$choosegroup->visible) {
        // Show dimmed if the mod is hidden
        $link = '<a class="dimmed" href="view.php?id='.$choosegroup->coursemodule.'">'.format_string($choosegroup->name).'</a>';
    } else {
        // Show normal if the mod is visible
        $link = '<a href="view.php?id='.$choosegroup->coursemodule.'">'.format_string($choosegroup->name).'</a>';
    }

    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = array ($choosegroup->section, $link);
    } else {
        $table->data[] = array ($link);
    }
}

echo "<br />";
echo html_writer::table($table);

echo $OUTPUT->footer();
