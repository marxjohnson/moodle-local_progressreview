<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_local_progressreview_uninstall() {
    global $DB, $CFG;

    $dbman = $DB->get_manager();

    $plugindirs = glob($CFG->dirroot.'/local/progressreview/plugins/*', GLOB_ONLYDIR);

    foreach ($plugindirs as $plugindir) {
        $dbman->delete_tables_from_xmldb_file($plugindir.'/db/install.xml');
        $pluginname = 'progressreview_'.end(explode('/', $plugindir));
        if($cfg = (array)get_config($pluginname)) {
            foreach($cfg as $name => $value) {
                unset_config($name, $pluginname);
            }
        }
    }
    return true;
}


