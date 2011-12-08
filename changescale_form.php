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
 * Form for changescale.php
 *
 * Displays a list of available scales for selection.
 *
 * @package   local_progressreview
 * @copyright 2011 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

class progressreview_changescale_form extends moodleform {

    public function definition() {
        global $DB;
        $mform =& $this->_form;
        $mform->addElement('hidden', 'sessionid', $this->_customdata['sessionid']);
        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);

        $fields = 'id, '.$DB->sql_concat('name', '" ("', 'scale', '")"').' AS name';
        $scales = $DB->get_records_menu('scale', array('courseid' => 0), '', $fields);
        $mform->addElement('select', 'scaleid', get_string('scale'), $scales);
        $mform->setDefault('scaleid', $this->_customdata['scaleid']);
        $this->add_action_buttons();
    }


}
