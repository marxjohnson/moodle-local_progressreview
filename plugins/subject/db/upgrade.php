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
 * @subpackage progressreview_subject
 * @copyright 2011 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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

    if ($oldversion < 2011092900) {

        // Changing nullability of field effort on table progressreview_subject to null
        $table = new xmldb_table('progressreview_subject');
        $field = new xmldb_field('effort', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, null, null, null, 'behaviour');

        // Launch change of nullability for field effort
        $dbman->change_field_notnull($table, $field);

        $field = new xmldb_field('behaviour', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, null, null, null, 'comments');

        // Launch change of nullability for field effort
        $dbman->change_field_notnull($table, $field);

        $field = new xmldb_field('homeworkstandard', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, null, null, null, 'effort');

        // Launch change of nullability for field effort
        $dbman->change_field_notnull($table, $field);

        $field = new xmldb_field('targetgrade', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, null, null, null, 'scaleid');

        // Launch change of nullability for field effort
        $dbman->change_field_notnull($table, $field);

        $field = new xmldb_field('performancegrade', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, null, null, null, 'targetgrade');

        // Launch change of nullability for field effort
        $dbman->change_field_notnull($table, $field);

        // subject savepoint reached
        upgrade_plugin_savepoint(true, 2011092900, 'progressreview', 'subject');

    }
}
