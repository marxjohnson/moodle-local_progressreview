<?php

require_once('../../config.php');
require_once($CFG->dirroot.'/user/selector/lib.php');
require_once($CFG->dirroot.'/local/progressreview/lib.php');
require_once($CFG->dirroot.'/local/progressreview/renderer.php');

$sort = optional_param('sort', null, PARAM_TEXT);
$generate = optional_param('generate', false, PARAM_BOOL);
$continue = optional_param('continue', false, PARAM_BOOL);
$disablememlimit = optional_param('disablememlimit', false, PARAM_BOOL);

require_login($SITE);
$systemcontext = get_context_instance(CONTEXT_SYSTEM);
require_capability('moodle/local_progressreview:print', $PAGE->context);

$PAGE->set_url('/local/progressreview/print.php');
$PAGE->navbar->add(get_string('pluginname', 'local_progressreview'));
$PAGE->navbar->add(get_string('print', 'local_progressreview'));
add_to_log(SITEID, 'local_progressreview', 'view', $PAGE->url->out());
$output = $PAGE->get_renderer('local_progressreview');
$content = '';

$selectoroptions = array('multiselect' => true);
$sessionselect = new progressreview_session_selector('sessionselect', $selectoroptions);
$studentselect = new progressreview_student_selector('studentselect', $selectoroptions);
$courseselect = new progressreview_course_selector('courseselect', $selectoroptions);
$teacherselect = new progressreview_teacher_selector('teacherselect', $selectoroptions);

$content .= $OUTPUT->heading(get_string('printheading', 'local_progressreview'), 2);
if ($generate) {
    confirm_sesskey();
    progressreview_controller::register_print_error_handler();
    if ($disablememlimit) {
        ini_set('memory_limit', -1);
    }
    $sessions = json_decode(optional_param('sessions', '[]', PARAM_RAW));
    $students = json_decode(optional_param('students', '[]', PARAM_RAW));
    $courses = json_decode(optional_param('courses', '[]', PARAM_RAW));
    $teachers = json_decode(optional_param('teachers', '[]', PARAM_RAW));

    $criteria = array();
    $criteria = progressreview_controller::build_print_criteria($criteria, 'sessionid', $sessions);
    $criteria = progressreview_controller::build_print_criteria($criteria, 'studentid', $students);
    $criteria = progressreview_controller::build_print_criteria($criteria, 'courseid', $courses);
    $criteria = progressreview_controller::build_print_criteria($criteria, 'teacherid', $teachers);

    $reviews = array();
    foreach ($criteria as $args) {
        $args = (array)$args;
        $args['type'] = PROGRESSREVIEW_STUDENT;
        $subjectreviews = call_user_func_array('progressreview_controller::get_reviews', $args);
        $args['type'] = PROGRESSREVIEW_TUTOR;
        $tutorreviews = call_user_func_array('progressreview_controller::get_reviews', $args);
        $reviews = array_merge($reviews, $subjectreviews, $tutorreviews);
    }
        var_dump($reviews);

} else if ($continue) {
    confirm_sesskey();
    $sessions = $sessionselect->get_selected_users();
    $students = $studentselect->get_selected_users();
    $courses = $courseselect->get_selected_users();
    $teachers = $teacherselect->get_selected_users();

    $content .= $OUTPUT->heading(get_string('selectedreviews', 'local_progressreview'), 3);
    $content .= $output->print_confirmation($sessions, $students, $courses, $teachers);
} else {
    $content .= $output->print_selectors($sessionselect, $studentselect, $courseselect, $teacherselect);
}


echo $OUTPUT->header();
$output->tabs(2);

echo $content;

echo $OUTPUT->footer();
