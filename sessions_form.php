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

/**
 * Defines the form for creating or editing a session
 *
 */
class progressreview_session_form extends moodleform {

    /**
     * Defines the form's fields
     *
     * Defines a form with fields for entering a name, deadlines, scales (as csv lists),
     * dates for statistics, and whether the session is an induction review.
     * Also displays a checkbox for each installed subplugin, so that it can be set as active
     * for this session.
     */
    protected function definition() {
        $mform =& $this->_form;

        $sessions = progressreview_controller::get_sessions();
        $sessionoptions = array('' => get_string('choosedots'));
        foreach ($sessions as $session) {
            $sessionoptions[$session->id] = $session->name;
        }
        $strdeadlinesubject = get_string('deadline_subject', 'local_progressreview');
        $strdeadlinetutor = get_string('deadline_tutor', 'local_progressreview');
        $strlockafterdeadline = get_string('lockafterdeadline', 'local_progressreview');
        $strscalebehaviour = get_string('scale_behaviour', 'local_progressreview');
        $strscaleeffort = get_string('scale_effort', 'local_progressreview');
        $strscalehomework = get_string('scale_homework', 'local_progressreview');
        $strsnapshotdate = get_string('snapshotdate', 'local_progressreview');
        $strprevioussession = get_string('showdatafrom', 'local_progressreview');
        $strhomeworkstart = get_string('homeworkstart', 'local_progressreview');
        $strinductionreview = get_string('inductionreview', 'local_progressreview');

        $mform->addElement('hidden', 'editid');
        $mform->addElement('header', 'general', get_string('general'));
        $mform->addElement('text', 'name', get_string('name', 'local_progressreview'));
        $mform->addElement('date_time_selector', 'deadline_subject', $strdeadlinesubject);
        $mform->addElement('date_time_selector', 'deadline_tutor', $strdeadlinetutor);
        $mform->addElement('advcheckbox', 'lockafterdeadline', $strlockafterdeadline);
        $mform->addElement('text', 'scale_behaviour', $strscalebehaviour);
        $mform->addElement('text', 'scale_effort', $strscaleeffort);
        $mform->addElement('text', 'scale_homework', $strscalehomework);
        $mform->addElement('date_selector', 'homeworkstart', $strhomeworkstart);
        $mform->addElement('date_time_selector', 'snapshotdate', $strsnapshotdate);
        $mform->addElement('select', 'previoussession', $strprevioussession, $sessionoptions);
        $mform->addElement('advcheckbox', 'inductionreview', $strinductionreview);

        $mform->addHelpButton('homeworkstart', 'homeworkstart', 'local_progressreview');

        $mform->setTypes(array(
            'name' => PARAM_TEXT,
            'deadline_subject' => PARAM_INT,
            'deadline_tutor' => PARAM_INT,
            'lockafterdeadline' => PARAM_BOOL,
            'scale_behaviour' => PARAM_TEXT,
            'scale_effort' => PARAM_TEXT,
            'scale_homework' => PARAM_TEXT,
            'homeworkstart' => PARAM_INT,
            'snapshotdate' => PARAM_INT,
            'previoussession' => PARAM_INT,
            'inductionreview' => PARAM_BOOL
        ));

        $pluginnames = $this->get_plugin_names();
        $mform->addElement('header', 'plugins', get_string('selectplugins', 'local_progressreview'));
        foreach ($pluginnames as $pluginname) {
            $mform->addElement('advcheckbox',
                               'plugins['.$pluginname.']',
                               get_string('pluginname', 'progressreview_'.$pluginname));
        }
        $mform->setDefault('plugins[tutor]', 1);
        $mform->setDefault('plugins[subject]', 1);
        $mform->disabledIf('plugins[tutor]', 'plugins[subject]');
        $mform->disabledIf('plugins[subject]', 'plugins[tutor]');
        $this->add_action_buttons();
    }

    /**
     * Helper function to find the names of all installed subplugins
     *
     * @return array of all the plugin component names, minus the progressreview_ prefix
     */
    private function get_plugin_names() {
        global $DB;
        $where = $DB->sql_like('plugin', '?').' AND name = ?';
        $params = array('progressreview_%', 'version');
        $plugins = $DB->get_records_select('config_plugins', $where, $params, 'plugin', 'plugin');
        $names = array_keys($plugins);
        foreach ($names as &$name) {
            $name = str_replace('progressreview_', '', $name);
        }
        return $names;
    }

    /**
     * Processes data posted through the form
     *
     * Creates or updates a record for the session, and creates or removes
     * records for the active plugins.
     *
     * @param object $data
     * @return int|bool false on failure, true on a successful update, record ID on a successful insert
     */
    public function process($data) {
        global $CFG, $DB;
        if ($data->editid) {
            $record = clone($data);
            $record->id = $data->editid;
            unset($record->editid);
            foreach ($data->plugins as $pluginname => $active) {
                $aparams = array(
                    'plugin' => $DB->sql_compare_text($pluginname),
                    'sessionid' => $record->id
                );
                $awhere = 'plugin = :plugin AND sessionid = :sessionid';
                if ($active) {
                    if (!$DB->record_exists_select('progressreview_activeplugins', $awhere, $aparams)) {
                        require_once($CFG->dirroot.'/local/progressreview/plugins/'.$pluginname.'/lib.php');
                        $class = 'progressreview_'.$pluginname;
                        $activeplugin = (object)$aparams;
                        $activeplugin->reviewtype = $class::$type;
                        if (!$DB->insert_record('progressreview_activeplugins', $activeplugin)) {
                            return false;
                        }
                    }
                } else {
                    if ($activerecord = $DB->get_record_select('progressreview_activeplugins', $awhere, $aparams)) {
                        $params = array('id' => $activerecord->id);
                        if (!$DB->delete_records('progressreview_activeplugins', $params)) {
                            return false;
                        }
                    }
                }
            }
            return $DB->update_record('progressreview_session', $record);
        } else {
            $record = clone($data);
            unset($record->plugins);
            $record->id = $DB->insert_record('progressreview_session', $record);
            $plugins = array();
            foreach (array_keys(array_filter($data->plugins)) as $pluginname) {
                require_once($CFG->dirroot.'/local/progressreview/plugins/'.$pluginname.'/lib.php');
                $class = 'progressreview_'.$pluginname;
                $plugins[] = (object)array(
                    'plugin' => $pluginname,
                    'sessionid' => $record->id,
                    'reviewtype' => $class::$type
                );
            }
            foreach ($plugins as $plugin) {
                $DB->insert_record('progressreview_activeplugins', $plugin);
            }
            return $record->id;
        }
    }
}
