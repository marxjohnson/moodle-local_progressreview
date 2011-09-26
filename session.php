<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/local/progressreview/lib.php');
require_once($CFG->dirroot.'/local/progressreview/renderer.php');
require_once($CFG->dirroot.'/local/progressreview/sessions_form.php');

require_login($SITE);

$id = optional_param('id', null, PARAM_INT);
$editid = optional_param('editid', null, PARAM_INT);

$PAGE->set_url('/local/progressreview/session.php');
$PAGE->navbar->add(get_string('pluginname', 'local_progressreview'), '/local/progressreview');
$PAGE->navbar->add(get_string('sessions', 'local_progressreview'));

if (!$id) {

    $form = new progressreview_session_form();

    if ($data = $form->get_data()) {
        $sessionid = $form->process($data);
        redirect($PAGE->url->out(array('id' => $sessionid)), get_string('sessioncreated', 'local_progressreview'));
        exit();
    }

    if ($editid) {
        $session = $DB->get_record('progressreview_session', array('id' => $editid));
        $form->set_data($session);
    }

} else {
    $output = $PAGE->get_renderer('local_progressreview');
    $session = $DB->get_record('progressreview_session', array('id' => $id));
    $subject_selector = $output->subject_selector($session);
}

echo $OUTPUT->header();

if (isset($form)) {
    $form->display();
} else {
    echo $content;
}

echo $OUTPUT->footer();

