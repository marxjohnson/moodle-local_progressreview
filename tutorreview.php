<?php

require_once('../../config.php');
require_once($CFG->dirroot.'/local/progressreview/lib.php');
require_once($CFG->dirroot.'/local/progressreview/renderer.php');
require_once($CFG->dirroot.'/local/progressreview/tutor_form.php');

$sessionid = required_param('sessionid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$studentid = optional_param('studentid', false, PARAM_INT);
$buttons = optional_param('buttons', false, PARAM_CLEAN);
if (!$studentid) {
    if (isset($buttons['prev'])) {
        $studentid = optional_param('previd', false, PARAM_INT);
    }
}
if (!$studentid) {
    if (isset($buttons['next'])) {
        $studentid = optional_param('nextid', false, PARAM_INT);
    }
}
$editid = optional_param('editid', false, PARAM_INT);
$coursecontext = get_context_instance(CONTEXT_COURSE, $courseid);

if (!$session = $DB->get_record('progressreview_session', array('id' => $sessionid))) {
    print_error('invalidsession', 'local_progressreview');
}

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'local_progressreview');
}

if (has_capability('moodle/local_progressreview:write', $coursecontext)) {
    $mode = PROGRESSREVIEW_TEACHER;
} else if (has_capability('moodle/local_progressreview:viewown', $coursecontext)) {
    $mode = PROGRESSREVIEW_STUDENT;
} else {
    print_error('noaccess');
}

require_login($course);
$PAGE->navbar->add(get_string('pluginname', 'local_progressreview'));
$listurl = new moodle_url('/local/progressreview/tutorreview.php', array('sessionid' => $sessionid, 'courseid' => $courseid));
$PAGE->set_url($listurl);
if ($studentid) {
    $PAGE->navbar->add($session->name, $PAGE->url);
    $student = $DB->get_record('user', array('id' => $studentid));
    $PAGE->url->params(array('studentid' => $studentid));
    $PAGE->navbar->add(fullname($student));
} else {
    $PAGE->navbar->add($session->name);
}

$output = $PAGE->get_renderer('local_progressreview');
$content = '';

if ($mode == PROGRESSREVIEW_TEACHER) {

    if ($editid) {
        $editstudent = $DB->get_record('user', array('id' => $editid));
        $editreview = new progressreview($editstudent->id, $sessionid, $courseid, $USER->id, PROGRESSREVIEW_TUTOR);
        $editform = new progressreview_tutor_form(null, array('progressreview' => $editreview));
        if ($data = $editform->get_data()) {
            $editform->process($data);
            add_to_log($course, 'local_progressreview', 'update', $PAGE->url->out(), $editstudent->id);
            $content .= $OUTPUT->notification(get_string('savedreviewfor', 'local_progressreview', fullname($editstudent)));
        }
    }
    if ($editid != $studentid) {
        unset($_POST);
    }
    if ($studentid) {
        $review = new progressreview($student->id, $sessionid, $courseid, $USER->id, PROGRESSREVIEW_TUTOR);
        $form = new progressreview_tutor_form(null, array('progressreview' => $review));
        $data = new stdClass;
        $form->set_data($data);
    } else {
        $tutorgroup = progressreview_controller::get_reviews($sessionid, null, $courseid, null, PROGRESSREVIEW_TUTOR);
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
        $content .= $output->tutorgroup_list($tutorgroup);
    }

}
add_to_log($course->id, 'local_progressreview', 'view', $PAGE->url->out(), $studentid);
if (isset($form)) {
    if (!empty($session->deadline_tutor)) {
        $deadline = userdate($session->deadline_tutor);
        $strdeadline = get_string('completetutorreviewsby', 'local_progressreview', $deadline);
        $content .= $OUTPUT->container($strdeadline, 'reviewnotes');
    }
    if (!empty($session->previoussession)) {
        $previoussession = progressreview_controller::validate_session($session->previoussession);
        $strprevious = get_string('previousfigures', 'local_progressreview', $previoussession->name);
        $content .= $OUTPUT->container($strprevious, 'reviewnotes');
    }
    ob_start();
    $form->display();
    $content .= str_replace('&amp;', '&', ob_get_contents());
    ob_end_clean();
}

echo $OUTPUT->header();

echo $content;

echo $OUTPUT->footer();
