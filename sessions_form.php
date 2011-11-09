<?php
require_once($CFG->libdir.'/formslib.php');

class progressreview_session_form extends moodleform {

    protected function definition() {
        $mform =& $this->_form;

        $sessions = progressreview_controller::get_sessions();
        $sessionoptions = array('' => get_string('choosedots'));
        foreach ($sessions as $session) {
            $sessionoptions[$session->id] = $session->name;
        }

        $mform->addElement('hidden', 'editid');
        $mform->addElement('text', 'name', get_string('name', 'local_progressreview'));
        $mform->addElement('date_time_selector', 'deadline_subject', get_string('deadline_subject', 'local_progressreview'));
        $mform->addElement('date_time_selector', 'deadline_tutor', get_string('deadline_tutor', 'local_progressreview'));
        $mform->addElement('advcheckbox', 'lockafterdeadline', get_string('lockafterdeadline', 'local_progressreview'));
        $mform->addElement('text', 'scale_behaviour', get_string('scale_behaviour', 'local_progressreview'));
        $mform->addElement('text', 'scale_effort', get_string('scale_effort', 'local_progressreview'));
        $mform->addElement('text', 'scale_homework', get_string('scale_homework', 'local_progressreview'));
        $mform->addElement('date_time_selector', 'snapshotdate', get_string('snapshotdate', 'local_progressreview'));
        $mform->addElement('select', 'previoussession', get_string('showdatafrom', 'local_progressreview'), $sessionoptions);
        $mform->addElement('advcheckbox', 'inductionreview', get_string('inductionreview', 'local_progressreview'));
        $this->add_action_buttons();
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
                (object)array('plugin' => 'subject', 'sessionid' => $id, 'reviewtype' => PROGRESSREVIEW_SUBJECT),
                (object)array('plugin' => 'tutor', 'sessionid' => $id, 'reviewtype' => PROGRESSREVIEW_TUTOR),
                (object)array('plugin' => 'targets', 'sessionid' => $id, 'reviewtype' => PROGRESSREVIEW_TUTOR)
            );
            foreach ($plugins as $plugin) {
                $DB->insert_record('progressreview_activeplugins', $plugin);
            }
            return $id;
        }
    }
}
