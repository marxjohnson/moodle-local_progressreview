<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/local/progressreview/lib.php');

$controller = 'progressreview_controller';

require_login($SITE);
$courseid = required_param('courseid', PARAM_INT);
$teacherid = required_param('teacherid', PARAM_INT);
$sessionid = required_param('sessionid', PARAM_INT);
$confirm = optional_param('confirm', false, PARAM_BOOL);

$urlparams = array(
    'courseid' => $courseid,
    'teacherid' => $teacherid,
    'sessionid' => $sessionid
);
$PAGE->set_url('/local/progressreview/delete.php', $urlparams);

$PAGE->navbar->add(get_string('pluginname', 'local_progressreview'), '/local/progressreview/index.php');
$PAGE->navbar->add(get_string('delete'));

$session = $controller::validate_session($sessionid);
$teacher = $controller::validate_teacher($teacherid);
$course = $controller::validate_course($courseid);

$originalcourse = $DB->get_record('course', array('id' => $course->originalid));
if ($originalcourse) {
    $categorycontext = get_context_instance(CONTEXT_COURSECAT, $originalcourse->category);
} else {
    $categorycontext = null;
}

if (!$categorycontext || !has_capability('moodle/local_progressreview:viewall', $categorycontext)) {
    require_capability('moodle/local_progressreview:manage', $PAGE->context);
}

$progressreviews = $controller::get_reviews($session->id,
                                            null,
                                            $course->originalid,
                                            $teacher->originalid);
if (empty($progressreviews)) {
   $progressreviews = $controller::get_reviews($session->id,
                                               null,
                                               $course->originalid,
                                               $teacher->originalid,
                                               PROGRESSREVIEW_TUTOR);
   if (empty($progressreviews)) {
       print_error('nodeletereviews', 'local_progressreview');
   }
}

$content = '';

if ($confirm) {
    if (confirm_sesskey()) {
        foreach ($progressreviews as $progressreview) {
            try {
                $progressreview->delete();
                $info = $session->id.'/'.$course->id.'/'.$teacher->id;
                add_to_log(SITEID, 'local_progressreview', 'delete', '/local/progressreview', $info);
            } catch (dml_exception $e) {
                $content .= $OUTPUT->error_text($e->getMessage());
            }
        }
    }
    if (empty($content)) {
        $redirecturl = new moodle_url('/local/progressreview/index.php');
        redirect($redirecturl);
        exit();
    }
} else {
    $progressreview = current($progressreviews);
    $confirmurl = clone($PAGE->url);
    $confirmurl->params(array('confirm' => 1));
    $cancelurl = new moodle_url('/local/progressreview/index.php');
    $messageparams = (object)array(
        'session' => html_writer::tag('strong', $progressreview->get_session()->name),
        'course' => html_writer::tag('strong', $progressreview->get_course()->fullname),
        'teacher' => html_writer::tag('strong', fullname($progressreview->get_teacher()))
    );
    $message = get_string('confirmdelete', 'local_progressreview', $messageparams);
    $content .= $OUTPUT->confirm($message, $confirmurl, $cancelurl);
}

echo $OUTPUT->header();

echo $content;

echo $OUTPUT->footer();
