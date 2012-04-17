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
 * Defines the plugin's uninstall function to ensure all subplugin tables are also removed
 *
 * @package   local_progressreview
 * @copyright 2011 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Finds all installed subplugins and deletes them all
 *
 * @return true
 */
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


