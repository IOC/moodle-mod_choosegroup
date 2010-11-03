<?php

function xmldb_choosegroup_upgrade($oldversion=0) {

    global $CFG, $db;

    $result = true;
    
    if ($result && $oldversion < 2010102800) {
        $table = new XMLDBTable('choosegroup');
        $field = new XMLDBField('shownames');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'allowupdate');
    // Launch add field shownames
        $result = $result && add_field($table, $field);
    }
    return $result;
}