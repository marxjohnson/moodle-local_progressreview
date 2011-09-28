<?php

function xmldb_progressreview_subject_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();
    if ($oldversion < 2011092800) {

        // Changing nullability of field scaleid on table progressreview_subject to not null
        $table = new xmldb_table('progressreview_subject');
        $field = new xmldb_field('scaleid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, false, null, null, 'punctuality');

        // Launch change of nullability for field scaleid
        $dbman->change_field_notnull($table, $field);

        // subject savepoint reached
        upgrade_plugin_savepoint(true, 2011092800, 'progressreview', 'subject');
    }
}
