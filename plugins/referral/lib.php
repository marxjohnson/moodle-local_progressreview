<?php

class progressreview_referral extends progressreview_plugin_tutor {

    public $id;

    protected $name = 'referral';

    static public $type = PROGRESSREVIEW_TUTOR;

    protected $valid_properties = array('id', 'reviewid', 'userid', 'message');

    protected $referral;

    public function get_review() {
        global $DB;
        if ($this->referral) {
            $referral = $this->referral;
            $referral->user = $DB->get_record('user', array('id' => $referral->userid));
            return $referral;
        }
    }

    public function retrieve_review() {
        global $DB;
        $params = array('reviewid' => $this->progressreview->id);
        if ($this->referral = $DB->get_record('progressreview_referral', $params)) {
            $this->id = $this->referral->id;
        }
    }

    public function delete() {
        global $DB;
        $params = array('id' => $this->id);
        $this->referral = $DB->delete_records('progressreview_referral', $params);
    }

    public function add_form_fields($mform) {
        global $DB;
        if (!empty($this->referral->userid)) {
            $params = array('id' => $this->referral->userid);
            $user = $DB->get_record('user', array('id' => $this->referral->userid));
        } else {
            $courseid = $this->progressreview->get_course()->originalid;
            $select = 'SELECT u.* ';
            $from = 'FROM mdl_user u
                        JOIN mdl_role_assignments ra ON u.id = ra.userid
                        JOIN mdl_role r ON r.id = ra.roleid
                        JOIN mdl_context con ON con.id = ra.contextid
                        JOIN mdl_course_categories cc ON cc.id = con.instanceid
                        JOIN mdl_course c ON c.category = cc.id ';
            $where = 'WHERE r.shortname = ?
                AND con.contextlevel = ?
                AND c.id = ?';
            $params = array('lam', CONTEXT_COURSECAT, $courseid);
            $user = $DB->get_record_sql($select.$from.$where, $params);
        }

        $mform->addElement('advcheckbox', 'refer', get_string('refer', 'progressreview_referral'), fullname($user));
        $mform->addElement('hidden', 'refer_userid', $user->id);
        $attrs = array('cols' => 50, 'rows' => 4);
        $mform->addElement('textarea', 'refer_message', get_string('message', 'progressreview_referral'), $attrs);
        $mform->disabledIf('refer_message', 'refer', 'neq', 1);
        $mform->addHelpButton('refer_message', 'message', 'progressreview_referral');

    }

    public function add_form_data($data) {
        if (!empty($this->referral)) {
            $data->refer = true;
            $data->refer_message = $this->referral->message;
        }
        return $data;
    }

    public function process_form_fields($data) {
        global $DB;
        if (isset($data->refer_message)) {
            $referral = array(
                'reviewid' => $this->progressreview->id,
                'userid' => $data->refer_userid,
                'message' => $data->refer_message
            );

            if (!empty($this->referral)) {
                $referral['id'] = $this->referral->id;
            } else {
                $user = $DB->get_record('user', array('id' => $referral['userid']));
                $from = 'noreply@moodle.org';
                $subject = get_string('emailsubject', 'progressreview_referral');
                $url = new moodle_url('/local/progressreview/plugins/referral/', array('reviewid' => $this->progressreview->id));
                $messageparams = (object)array(
                    'studentname' => fullname($this->progressreview->get_student()),
                    'tutorname' => fullname($this->progressreview->get_teacher()),
                    'message' => $referral['message'],
                    'link' => $url->out()
                );
                $messagetext = get_string('emailtext', 'progressreview_referral', $messageparams);
                email_to_user($user, $from, $subject, $messagetext);
            }

            return $this->update($referral);
        }
    }
}
