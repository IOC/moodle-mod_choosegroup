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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
* This file keeps track of upgrades to the wiki module
*
* Sometimes, changes between versions involve
* alterations to database structures and other
* major things that may break installations.
*
* The upgrade function in this file will attempt
* to perform all the necessary actions to upgrade
* your older installation to the current version.
*
* @package    mod
* @subpackage choosegroup
* @copyright  2011 Marc Català <mcatala@ioc.cat>
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

function xmldb_choosegroup_upgrade($oldversion) {

    global $CFG, $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    if ($oldversion < 2010102800) {
        $table = new xmldb_table('choosegroup');
        $field = new xmldb_field('shownames');
        $field = new xmldb_field('shownames', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'allowupdate');
        // Launch add field shownames
        $dbman->add_field($table, $field);
        upgrade_mod_savepoint(true, 2010102800, 'choosegroup');
    }

    if ($oldversion < 2012010900) {
        $table = new xmldb_table('choosegroup');
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'intro');

        // Launch add field introformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Define table choosegroup_group to be created
        $table = new xmldb_table('choosegroup_group');

        /// Adding fields to table choosegroup_group
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('choosegroupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('maxlimit', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');

        /// Adding keys to table chat_messages_current
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('choosegroupid', XMLDB_KEY_FOREIGN, array('choosegroupid'), 'choosegroup', array('id'));

        /// Adding indexes to table chat_messages_current
        $table->add_index('choosegroupid', XMLDB_INDEX_NOTUNIQUE, array('choosegroupid'));

        /// create table for choosegroup_group
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        $rs = $DB->get_recordset('choosegroup', null, 'id','id,groups,grouplimit');
        if ($rs->valid()){
            foreach ($rs as $ch) {
                //Avoid empty groups
                if (empty($ch->groups)){
                    continue;
                }
                $groups = explode(",", $ch->groups);
                foreach ($groups as $group){
                    $record = new stdClass();
                    $record->choosegroupid = $ch->id;
                    $record->groupid = $group;
                    $record->maxlimit = $ch->grouplimit;
                    $DB->insert_record('choosegroup_group', $record, false);
                }
            }
        }
        $rs->close();

        // Remove fields groups and grouplimit from choosegroup
        $table = new xmldb_table('choosegroup');
        $field = new xmldb_field('groups');
        $dbman->drop_field($table, $field);
        $field = new xmldb_field('grouplimit');
        $dbman->drop_field($table, $field);

        upgrade_mod_savepoint(true, 2012010900, 'choosegroup');
    }

    if ($oldversion < 2014013000) {

        // Define field completionchoosegroup to be added to choosegroup.
        $table = new xmldb_table('choosegroup');
        $field = new xmldb_field('completionchoosegroup', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'shownames');

        // Conditionally launch add field completionchoosegroup.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Choosegroup savepoint reached.
        upgrade_mod_savepoint(true, 2014013000, 'choosegroup');
    }

    return true;
}
