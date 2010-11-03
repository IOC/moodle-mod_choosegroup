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

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/choosegroup/lib.php');

class mod_choosegroup_mod_form extends moodleform_mod {

    function definition() {
        global $COURSE;
        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name', 'choosegroup'),
                           array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255),
                        'maxlength', 255, 'client');

        $mform->addElement('htmleditor', 'intro',
                           get_string('intro', 'choosegroup'));
        $mform->setType('intro', PARAM_RAW);
        $mform->addRule('intro', get_string('required'),
                        'required', null, 'client');
        $mform->setHelpButton('intro', array('writing', 'richtext'),
                              false, 'editorhelpbutton');

        $mform->addElement('text', 'grouplimit',
                           get_string('grouplimit', 'choosegroup'),
                           array('size' => 4));
        $mform->setType('grouplimit', PARAM_INT);
        $mform->setDefault('grouplimit', 0);

        $mform->setHelpButton('grouplimit', array('grouplimit', get_string('grouplimit','choosegroup'), 'choosegroup'));

        $mform->addElement('date_time_selector', 'timeopen', get_string('timeopen', 'choosegroup'), array('optional'=>true));
        $mform->setDefault('timeopen', time());
        $mform->addElement('date_time_selector', 'timeclose', get_string('timeclose', 'choosegroup'), array('optional'=>true));
        $mform->setDefault('timeclose', time()+7*24*3600);

        $options = array('before', 'after', 'closed', 'never');
        foreach ($options as $key=>$option) {
            $options[$key] = get_string("showresults:$option", 'choosegroup');
        }
        $mform->addElement('select', 'showmembers', get_string('showmembers', 'choosegroup'), $options);
        $mform->setDefault('showmembers', count($options)-1);
        $mform->setHelpButton('showmembers', array('showmembers', get_string('showmembers','choosegroup'), 'choosegroup'));
		
        $mform->addElement('selectyesno', 'allowupdate', get_string("allowupdate", "choosegroup"));
        $mform->setDefault('allowupdate', 0);
        $mform->setHelpButton('allowupdate', array('allowupdate', get_string('allowupdate','choosegroup'), 'choosegroup'));

        $mform->addElement('selectyesno', 'shownames', get_string("shownames", "choosegroup"));
        $mform->setDefault('shownames', 0);
        $mform->setHelpButton('shownames', array('shownames', get_string('shownames','choosegroup'), 'choosegroup'));
		
        /**********************************************************************************/

        $mform->addElement('header', 'allowgroups', get_string('groups', 'choosegroup'));
        $mform->setHelpButton('allowgroups', array('groups', get_string('groups','choosegroup'), 'choosegroup'));
		
        $groups = choosegroup_detected_groups($COURSE->id);
		
        if (empty($groups)) {
            $mform->addElement('static', 'description', get_string('nocoursegroups', 'choosegroup'));
        } else {
            foreach ($groups as $group){
                $mform->addElement('advcheckbox', 'group'.$group->id, $group->name, null, array('group' => 1));
            }
            $this->add_checkbox_controller(1,null,null);
        }

        /**********************************************************************************/
        $features = new stdClass;
        $features->groups = true;
        $features->groupings = true;
        $features->groupmembersonly = true;
        $this->standard_coursemodule_elements($features);

        $this->add_action_buttons();
    }

    function data_preprocessing(&$default_values) {
        global $COURSE;
        $groupsok = get_field('choosegroup', 'groups', 'id', $this->_instance);
        if (!empty($groupsok)) {
            $groupsok = explode(',', $groupsok);
            $groups = choosegroup_detected_groups($COURSE->id);
            foreach ($groups as $group) {
                if (in_array($group->id, $groupsok)){
                    $default_values['group'.$group->id] = 1;
                }
            }
        }
    }
}
