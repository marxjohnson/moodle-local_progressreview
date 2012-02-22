<?php

function xmldb_local_progressreview_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2011101318) {
        $table = new xmldb_table('progressreview_subject');
        $field = new xmldb_field('homeworkdone', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'homeworkstandard');

        // Launch change of default for field homeworkdone
        $dbman->change_field_default($table, $field);

        $field = new xmldb_field('homeworktotal', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'homeworkdone');
        $dbman->change_field_default($table, $field);
        // subject savepoint reached
        upgrade_plugin_savepoint(true, 2011101318, 'progressreview', 'subject');
        upgrade_plugin_savepoint(true, 2011101318, 'local', 'progressreview');
    }

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
