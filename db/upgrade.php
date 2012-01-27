<?php

function xmldb_local_progressreview_upgrade($oldversion = 0) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2012012700) {

        // Define field homeworkstart to be added to progressreview_session
        $table = new xmldb_table('progressreview_session');
        $field = new xmldb_field('homeworkstart', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'scale_behaviour');

        // Conditionally launch add field homeworkstart
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // progressreview savepoint reached
        upgrade_plugin_savepoint(true, 2012012700, 'local', 'progressreview');
    }

}
