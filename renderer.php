<?php

// Copyright © 2011 Institut Obert de Catalunya

// This file is part of Choose Group.

// Choose Group is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

// Choose Group is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Moodle renderer used to display special elements of the lesson module
 *
 * @package   Choosegroup
 * @copyright 2012 Marc Català <mcatala@ioc.cat>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

class mod_choosegroup_renderer extends plugin_renderer_base {
    /**
     * Prints group form choosegroup
     *
     * @param object $groups
     * @param string $message
     * @param object $choosegroup
     * @param object $url
     * @param int $groupid
     * @return string
     */
    function print_form($groups, $message, $choosegroup, $url, $groupid = false) {
        $output = '';

        if (empty($groups)) {
            $output .= get_string('nogroups', 'choosegroup');
        } else {
            $output .= '<div class="choosegroup_left"><b>' . get_string($message, 'choosegroup') . ':</b></div>';
            if ($choosegroup->showmembers < CHOOSEGROUP_AFTER) {
                $output .= '<div class="choosegroup_right"><b>' . get_string('groupmembers', 'choosegroup') . ':</b></div>';
            }
            $output .= '<div class="choosegroup_clear"></div>';
            $output .= '<form method="post" action="' . s($url->out()) . '">'.
                       '<input type="hidden" name="sesskey" value="'.
                       sesskey() . '"/>';
            foreach ($groups as $key => $group) {
                $vacancies = '';
                $disabled = '';
                $dimmed = '';
                if ($group->maxlimit) {
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

                if ($groupid && $key === $groupid) {
                    $disabled = 'disabled="disabled"';
                    $dimmed = 'class="dimmed"';
                    $vacancies = '(' . get_string('currentgroup', 'choosegroup') . ')';
                }

                $checkbox = "<input $disabled type=\"radio\" name=\"group\" ".
                            "id=\"group-{$group->id}\" value=\"{$group->id}\" />";
                $label = "<label $dimmed for=\"group-{$group->id}\">".
                         s($group->name) . " $vacancies</label>";
                $members = '';
                $hr = '';
                if ($choosegroup->showmembers < CHOOSEGROUP_AFTER) {
                    $members = choosegroup_show_members($group->id, $choosegroup->shownames);
                    $hr ='<hr />';
                }
                $output .= "<div class=\"choosegroup_left\">$checkbox $label</div> $members";
                $output .= "<div class=\"choosegroup_clear\">$hr</div>";
            }
            $output .= '<input type="submit" value="' . get_string('submit') . '"/>'.
                       '</form>';
        }
        return $output;
    }
}

