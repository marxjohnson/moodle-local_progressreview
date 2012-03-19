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
 * Defines config form for Alternative Plans plugin
 *
 * @package   local_progressreview
 * @subpackage progressreview_alternativeplans
 * @copyright 2012 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class progressreview_alternativeplans_config_form extends moodleform {

    public function definition() {
        global $DB, $OUTPUT;
        $mform = $this->_form;

        $id = optional_param('id', false, PARAM_INT);

        $mform->addElement('hidden', 'plugin', 'alternativeplans');
        $mform->addElement('text', 'description', get_string('description', 'progressreview_alternativeplans'));

        if ($id) {
            if ($plan = $DB->get_record('progressreview_altplan', array('id' => $id))) {
                $mform->setDefault('description', $plan->description);
                $mform->addElement('hidden', 'id', $id);
            }
        }

        $mform->addElement('submit', 'submit', get_string('submit'));

        if ($plans = $DB->get_records('progressreview_altplan', array(), 'description')) {
            $table = new html_table();
            $table->head = array(
                get_string('id', 'progressreview_alternativeplans'),
                get_string('description', 'progressreview_alternativeplans'),
                ''
            );
            $editicon = $OUTPUT->pix_icon('edit', get_string('edit'), 'moodle');
            foreach ($plans as $plan) {
                $editparams = array(
                    'plugin' => 'alternativeplans',
                    'id' => $plan->id
                );
                $editurl = new moodle_url('/local/progressreview/plugins/index.php', $editparams);
                $editlink = html_writer::link($editurl, $editicon);
                $table->data[] = array($plan->id, $plan->description, $editlink);
            }
            $mform->addElement('html', html_writer::table($table));
        }

    }

    public function process($data) {
        global $DB;
        if (!empty($data->description)) {
            if (empty($data->id)) {
                $record = (object)array(
                    'description' => $data->description
                );
                $DB->insert_record('progressreview_altplan', $record);
            } else {
                $record = (object)array(
                    'id' => $data->id,
                    'description' => $data->description
                );
                $DB->update_record('progressreview_altplan', $record);
            }
        }

        return redirect(new moodle_url('/local/progressreview/plugins/index.php', array('plugin' => 'alternativeplans')));
    }

}
