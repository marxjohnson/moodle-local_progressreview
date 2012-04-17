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
 * Defines the plugin's upgrade functions
 *
 * @package   local_progressreview
 * @copyright 2011 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Runs any upgrade needed to upgrade from $oldversion to the current version
 *
 * @param int $oldversion
 */
function xmldb_local_progressreview_upgrade($oldversion = 0) {
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

    if ($oldversion < 2012041700) {

        // Define field deadline_active to be added to progressreview_session
        $table = new xmldb_table('progressreview_session');
        $field = new xmldb_field('deadline_active', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'deadline_tutor');

        // Conditionally launch add field deadline_active
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // progressreview savepoint reached
        upgrade_plugin_savepoint(true, 2012041700, 'local', 'progressreview');
    }

}
