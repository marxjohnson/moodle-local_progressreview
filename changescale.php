<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/local/progressreview/lib.php');
require_once($CFG->dirroot.'/local/progressreview/changescale_form.php');

$sessionid = required_param('sessionid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$coursecontext = get_context_instance(CONTEXT_COURSE, $courseid);

if (!$session = $DB->get_record('progressreview_session', array('id' => $sessionid))) {
    print_error('invalidsession', 'local_progressreview');
}

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'local_progressreview');
}

require_login($course);
require_capability('moodle/local_progressreview:write', $coursecontext);
$PAGE->set_url('/local/progressreview/changescale.php', array('sessionid' => $sessionid, 'courseid' => $courseid));
$PAGE->navbar->add(get_string('pluginname', 'local_progressreview'));
$PAGE->navbar->add($session->name);

$reviews = progressreview_controller::get_reviews($sessionid, null, $courseid);
$scaleid = current($reviews)->get_plugin('subject')->get_scaleid();

$form = new progressreview_changescale_form('', array('sessionid' => $sessionid, 'courseid' => $courseid, 'scaleid' => $scaleid));

$content = '';

if ($data = $form->get_data()) {
    $newscaleid = $data->scaleid;
    foreach($reviews as $review) {
        $review->get_plugin('subject')->update(array('scaleid' => $newscaleid));
    }
    $redirecturl = new moodle_url('/local/progressreview/subjectreview.php', array('sessionid' => $sessionid, 'courseid' => $courseid));
    redirect($redirecturl, get_string('changessaved'), 2);
}

echo $OUTPUT->header();

echo $content;
$form->display();

echo $OUTPUT->footer();


