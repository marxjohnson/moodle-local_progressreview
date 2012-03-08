<?php

function xmldb_progressreview_intentions_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2012030600) {

        // Define field cont to be added to progressreview_intent_select
        $table = new xmldb_table('progressreview_intent_select');
        $field = new xmldb_field('cont', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'istop');

        // Conditionally launch add field cont
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // intentions savepoint reached
        upgrade_plugin_savepoint(true, 2012030600, 'progressreview', 'intentions');
    }

}
