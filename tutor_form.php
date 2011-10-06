<?php
require_once($CFG->libdir.'/formslib.php');

class progressreview_tutor_form extends moodleform {

    protected function definition() {
        global $OUTPUT, $PAGE;

        $mform = $this->_form;
        $progressreview = $this->_customdata['progressreview'];
        $student = $progressreview->get_student();
        $session = $progressreview->get_session();

        $mform->addElement('hidden', 'reviewid', $progressreview->id);
        $mform->addElement('hidden', 'editid', $student->id);
        $mform->addElement('hidden', 'courseid', $progressreview->get_course()->originalid);
        $mform->addElement('hidden', 'sessionid', $session->id);
        $mform->addElement('header', 'core', fullname($student));
        $mform->addElement('html', $OUTPUT->user_picture($student));

        $output = $PAGE->get_renderer('local_progressreview');
        $reviews = progressreview_controller::get_reviews($session->id, $student->id);
        foreach($reviews as &$review) {
            $review = $review->get_plugin('subject')->get_review();
        }
        $table = $output->subject_review_table($reviews, false);
        $mform->addElement('html', $table);

        $progressreview->get_plugin('tutor')->add_form_fields($mform);

        $plugins = $progressreview->get_plugins();
        foreach ($plugins as $plugin) {
            $pluginname = $plugin->get_name();
            $legend = get_string('pluginname', 'progressreview_'.$pluginname);
            if ($pluginname != 'tutor') {
                $mform->addElement('header', $pluginname, $legend);
                $plugin->add_form_fields($mform);
            }
        }

        $tutorgroup = progressreview_controller::get_reviews($progressreview->get_session()->id, null, $progressreview->get_course()->originalid, null, PROGRESSREVIEW_TUTOR);
        usort($tutorgroup, function($a, $b) {
            $student_a = $a->get_student();
            $student_b = $b->get_student();
            $lastname = strcmp($student_a->lastname, $student_b->lastname);
            if ($lastname) {
                return $lastname;
            } else {
                return strcmp($student_a->firstname, $student_b->firstname);
            }
        });
        $prev = null;
        $current = null;
        $next = null;
        $found = false;
        reset($tutorgroup);
        foreach($tutorgroup as $review) {
            if ($found) {
                $next = $review;
                break;
            }
            if ($review->id == $progressreview->id) {
                $found = true;
                $prev = $current;
                $current = $review;
                continue;
            }
            if (!$found) {
                $current = $review;
            }
        }

        $mform->closeHeaderBefore('buttons');
        $buttongroup = array();
        if ($prev) {
            $prevstudent = $prev->get_student();
            $mform->addElement('hidden', 'previd', $prevstudent->id);
            $buttongroup[] = $mform->createElement('submit', 'prev', $OUTPUT->larrow().' '.fullname($prevstudent));
        } else {
            $buttongroup[] = $mform->createElement('submit', 'prev', $OUTPUT->larrow().' '.get_string('startofgroup', 'local_progressreview'), array('disabled' => 'disabled'));
        }
        $buttongroup[] = $mform->createElement('submit', 'save', get_string('returntolist', 'local_progressreview'));
        if ($next) {
            $nextstudent = $next->get_student();
            $mform->addElement('hidden', 'nextid', $nextstudent->id);
            $buttongroup[] = $mform->createElement('submit', 'next', fullname($nextstudent).' '.$OUTPUT->rarrow());
        } else {
            $buttongroup[] = $mform->createElement('submit', 'next', get_string('endofgroup', 'local_progressreview').' '.$OUTPUT->rarrow(), array('disabled' => 'disabled'));

        }

        $mform->addGroup($buttongroup, 'buttons');

    }

    public function process($data) {
        $plugins = $this->_customdata['progressreview']->get_plugins();
        $return = true;
        foreach ($plugins as $plugin) {
            $return = $return && $plugin->process_form_fields($data);
        }
        return $return;
    }

    public function set_data($data) {
        $plugins = $this->_customdata['progressreview']->get_plugins();
        foreach ($plugins as $plugin) {
            $data = $plugin->add_form_data($data);
        }

        return parent::set_data($data);
    }
}
