<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/local/progressreview/lib.php');
require_once($CFG->dirroot.'/local/progressreview/course_selector.php');
require_once($CFG->dirroot.'/local/progressreview/renderer.php');
require_once($CFG->dirroot.'/local/progressreview/sessions_form.php');

require_login($SITE);
require_capability('moodle/local_progressreview:manage');

$id = optional_param('id', null, PARAM_INT);
$editid = optional_param('editid', null, PARAM_INT);
$generate = optional_param('generate', false, PARAM_TEXT);
$regenerate = optional_param('regenerate', false, PARAM_TEXT);
$snapshot = optional_param('snapshot', false, PARAM_TEXT);

$params = array_filter(array('id' => $id, 'editid' => $editid));
$PAGE->set_url('/local/progressreview/session.php', $params);
$PAGE->navbar->add(get_string('pluginname', 'local_progressreview'), '/local/progressreview');
$PAGE->navbar->add(get_string('sessions', 'local_progressreview'));

if (!$id) {

    $form = new progressreview_session_form();

    if ($data = $form->get_data()) {
        $sessionid = $form->process($data);
        redirect($PAGE->url->out(true, array('id' => $sessionid)), get_string('sessioncreated', 'local_progressreview'));
        exit();
    }

    if ($editid) {
        $session = $DB->get_record('progressreview_session', array('id' => $editid));
        $form->set_data($session);
    }

} else {
    $output = $PAGE->get_renderer('local_progressreview');
    $session = $DB->get_record('progressreview_session', array('id' => $id));

    $potential_subject_selector = new progressreview_potential_course_selector('potential_subjects');
    $distributed_subject_selector = new progressreview_distributed_course_selector('distributed_subjects', $session->id);

    $excludes = $distributed_subject_selector->find_users();
    foreach ($excludes as $exclude) {
        $potential_subject_selector->exclude(array_keys($exclude));
    }

    $subjects = array();

    if ($generate) {
        $subjects = $potential_subject_selector->get_selected_users(); 
    } else if ($regenerate || $snapshot) {
        $subjects = $distributed_subject_selector->get_selected_users();
        if ($snapshot) {
            foreach ($subjects as $subject) {
                progressreview_controller::snapshot_data($session->id, $subject->id);
            }
            redirect($PAGE->url->out(), get_string('snapshotted', 'local_progressreview'));
            exit();
        }
    }

    if ($subjects) {
        foreach ($subjects as $subject) {
            progressreview_controller::generate_reviews_for_course($subject->id, $session->id, PROGRESSREVIEW_SUBJECT);
        }
        redirect($PAGE->url->out(), get_string('reviewsgenerated', 'local_progressreview'));
        exit();
    }

    $subject_selector = $output->course_selector_form($potential_subject_selector, $distributed_subject_selector, $session->id);
    $content = $subject_selector;
}

echo $OUTPUT->header();

if (isset($form)) {
    $form->display();
} else {
    echo $content;
}

echo $OUTPUT->footer();

