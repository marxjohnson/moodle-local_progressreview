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
 * Defines and processes the form for creating and editing sessions in session.php
 *
 * @package   local_progressreview
 * @copyright 2011 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

class progressreview_session_form extends moodleform {

    protected function definition() {
        $mform =& $this->_form;

        $sessions = progressreview_controller::get_sessions();
        $sessionoptions = array('' => get_string('choosedots'));
        foreach ($sessions as $session) {
            $sessionoptions[$session->id] = $session->name;
        }
        $strdealinesubject = get_string('deadline_subject', 'local_progressreview');
        $strdeadlinetutor = get_string('deadline_tutor', 'local_progressreview');
        $strlockafterdeadline = get_string('lockafterdeadline', 'local_progressreview');
        $strscalebehaviour = get_string('scale_behaviour', 'local_progressreview');
        $strscaleeffort = get_string('scale_effort', 'local_progressreview');
        $strscalehomework = get_string('scale_homework', 'local_progressreview');
        $strsnapshotdate = get_string('snapshotdate', 'local_progressreview');
        $strprevioussession = get_string('showdatafrom', 'local_progressreview');
        $strinductionreview = get_string('inductionreview', 'local_progressreview');

        $mform->addElement('hidden', 'editid');
        $mform->addElement('text', 'name', get_string('name', 'local_progressreview'));
        $mform->addElement('date_time_selector', 'deadline_subject', $strdeadlinesubject);
        $mform->addElement('date_time_selector', 'deadline_tutor', $strdeadlinetutor);
        $mform->addElement('advcheckbox', 'lockafterdeadline', $strlockafterdeadline);
        $mform->addElement('text', 'scale_behaviour', $strscalebehaviour);
        $mform->addElement('text', 'scale_effort', $strscaleeffort);
        $mform->addElement('text', 'scale_homework', $strscalehomework);
        $mform->addElement('date_time_selector', 'snapshotdate', $snapshotdate);
        $mform->addElement('select', 'previoussession', $strprevioussession, $sessionoptions);
        $mform->addElement('advcheckbox', 'inductionreview', $strinductionreview);

        $mform->setTypes(array(
            'name' => PARAM_TEXT,
            'deadline_subject' => PARAM_INT,
            'deadline_tutor' => PARAM_INT,
            'lockafterdeadline' => PARAM_BOOL,
            'scale_behaviour' => PARAM_TEXT,
            'scale_effort' => PARAM_TEXT,
            'scale_homework' => PARAM_TEXT,
            'snapshotdate' => PARAM_INT,
            'previoussession' => PARAM_INT,
            'inductionreview' => PARAM_BOOL
        ));
        $this->add_action_buttons();
    }

    private function get_plugin_names() {
        global $DB;
        $where = $DB->sql_like('plugin', '?').' AND name = ?';
        $params = array('progressreview_%', 'version');
        $plugins = $DB->get_records_select('config_plugins', $where, $params, 'plugin', 'plugin');
        $names = array_keys($plugins);
        return $names;
    }

    public function process($data) {
        global $DB;
        if ($data->editid) {
            $data->id = $data->editid;
            unset($data->editid);
            return $DB->update_record('progressreview_session', $data);
        } else {
            $id = $DB->insert_record('progressreview_session', $data);
            $plugins = array(
                (object)array(
                    'plugin' => 'subject',
                    'sessionid' => $id,
                    'reviewtype' => PROGRESSREVIEW_SUBJECT
                ),
                (object)array(
                    'plugin' => 'tutor',
                    'sessionid' => $id,
                    'reviewtype' => PROGRESSREVIEW_TUTOR
                ),
                (object)array(
                    'plugin' => 'targets',
                    'sessionid' => $id,
                    'reviewtype' => PROGRESSREVIEW_TUTOR
                )
            );
            foreach ($plugins as $plugin) {
                $DB->insert_record('progressreview_activeplugins', $plugin);
            }
            return $id;
        }
    }
}
