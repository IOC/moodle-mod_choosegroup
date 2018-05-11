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
 * A custom renderer class that extends the plugin_renderer_base and is used by the choosegroup module.
 *
 * @package    mod
 * @subpackage choosegroup
 * @copyright  2013 Institut Obert de Catalunya
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Albert Gasset <albert.gasset@gmail.com>
 * @author     Marc Català <reskit@gmail.com>
 * @author     Manuel Cagigas <sedras@gmail.com>
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/choosegroup/lib.php');

class mod_choosegroup_mod_form extends moodleform_mod {

    public function definition() {
        global $COURSE;
        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name', 'choosegroup'),
                           array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements(get_string('description'));

        $mform->addElement('date_time_selector', 'timeopen', get_string('timeopen', 'choosegroup'), array('optional' => true));
        $mform->setDefault('timeopen', time());
        $mform->addElement('date_time_selector', 'timeclose', get_string('timeclose', 'choosegroup'), array('optional' => true));
        $mform->setDefault('timeclose', time() + 7 * 24 * 3600);

        $options = array('before', 'after', 'closed', 'never');
        foreach ($options as $key => $option) {
            $options[$key] = get_string("showresults:$option", 'choosegroup');
        }
        $mform->addElement('select', 'showmembers', get_string('showmembers', 'choosegroup'), $options);
        $mform->setDefault('showmembers', count($options) - 1);
        $mform->addHelpButton('showmembers', 'showmembers', 'choosegroup');

        $mform->addElement('selectyesno', 'allowupdate', get_string("allowupdate", "choosegroup"));
        $mform->setDefault('allowupdate', 0);
        $mform->addHelpButton('allowupdate', 'allowupdate', 'choosegroup');

        $mform->addElement('selectyesno', 'shownames', get_string("shownames", "choosegroup"));
        $mform->setDefault('shownames', 0);
        $mform->addHelpButton('shownames', 'shownames', 'choosegroup');

        /**********************************************************************************/

        $mform->addElement('header', 'allowgroups', get_string('groups', 'choosegroup'));
        $mform->addHelpButton('allowgroups', 'groups', 'choosegroup');

        $groups = choosegroup_detected_groups($COURSE->id);

        if (empty($groups)) {
            $mform->addElement('static', 'description', get_string('nocoursegroups', 'choosegroup'));
        } else {
            foreach ($groups as $group) {
                $buttonarray = array();
                $buttonarray[] =& $mform->createElement('text', 'lgroup['.$group->id.']', get_string('grouplimit', 'choosegroup'),
                                                        array('size' => 4));
                $buttonarray[] =& $mform->createElement('checkbox', 'ugroup['.$group->id.']', '',
                                                        ' '.get_string('nolimit', 'choosegroup'));
                $mform->setType('lgroup['.$group->id.']', PARAM_INT);
                $mform->setType('ugroup['.$group->id.']', PARAM_INT);
                $mform->addGroup($buttonarray, 'groupelement', $group->name, array(' '), false);
                $mform->disabledIf('lgroup['.$group->id.']', 'ugroup['.$group->id.']', 'checked');
                $mform->setDefault('lgroup['.$group->id.']', 0);
                $mform->addElement('hidden', 'groupid['.$group->id.']', '');
                $mform->setType('groupid['.$group->id.']', PARAM_INT);
            }
        }

        /**********************************************************************************/

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }


    public function data_preprocessing(&$defaultvalues) {
        global $COURSE, $DB;

        if (!$this->current->instance) {
            return;
        }
        $groupsok = $DB->get_records('choosegroup_group', array('choosegroupid' => $this->current->instance), 'groupid', 'id, groupid, maxlimit');
        if (!empty($groupsok)) {
            $groups = choosegroup_detected_groups($COURSE->id, true);
            foreach ($groupsok as $group) {
                if (in_array($group->groupid, $groups)) {
                    $defaultvalues['ugroup['.$group->groupid.']'] = ($group->maxlimit == 0) ? 1 : 0;
                    $defaultvalues['lgroup['.$group->groupid.']'] = $group->maxlimit;
                    $defaultvalues['groupid['.$group->groupid.']'] = $group->id;
                }
            }
        }
    }

    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return false;
        }
        // Set up completion section even if checkbox is not ticked.
        if (!empty($data->completionunlocked)) {
            if (empty($data->completionchoosegroup)) {
                $data->completionchoosegroup = 0;
            }
        }
        return $data;
    }

    public function add_completion_rules() {
        $mform =& $this->_form;

        $mform->addElement('checkbox', 'completionchoosegroup', '', get_string('completionchoosegroup', 'choosegroup'));
        return array('completionchoosegroup');
    }

    public function completion_rule_enabled($data) {
        return !empty($data['completionchoosegroup']);
    }
}
