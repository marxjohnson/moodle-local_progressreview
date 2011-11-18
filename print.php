<?php

require_once('../../config.php');
require_once($CFG->dirroot.'/user/selector/lib.php');
require_once($CFG->dirroot.'/local/progressreview/lib.php');
require_once($CFG->dirroot.'/local/progressreview/renderer.php');
require_once($CFG->dirroot.'/local/progressreview/pdf/class.ezpdf.php');

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

    $subjectreviews = array();
    $tutorreviews = array();
    foreach ($criteria as $args) {
        $args = (array)$args;
        $args['type'] = PROGRESSREVIEW_SUBJECT;
        $newsubjectreviews = call_user_func_array('progressreview_controller::get_reviews', $args);
        if ($newsubjectreviews) {
            $subjectreviews = array_merge($subjectreviews, $newsubjectreviews);
        }
        $args['type'] = PROGRESSREVIEW_TUTOR;
        $newtutorreviews = call_user_func_array('progressreview_controller::get_reviews', $args);
        if ($newtutorreviews) {
            $tutorreviews = array_merge($tutorreviews, $newtutorreviews);
        }
    }

    $sortedtutorreviews = array();
    $sortedsubjectreviews = array();

    foreach ($tutorreviews as $tutorreview) {
        $sessid = $tutorreview->get_session()->id;
        $student = $tutorreview->get_student();
        $studentname = $student->lastname.$student->firstname.$student->id;
        if (!array_key_exists($sessid, $sortedtutorreviews)) {
            $sortedtutorreviews[$sessid] = array();
        }
        $sortedtutorreviews[$sessid][$studentname] = $tutorreview;
    }

    foreach ($subjectreviews as $subjectreview) {
        $sessid = $subjectreview->get_session()->id;
        $course = $subjectreview->get_course()->shortname;
        $student = $subjectreview->get_student();
        $studentname = $student->lastname.$student->firstname.$student->id;
        $teacher = $subjectreview->get_teacher();
        $teachername = $teacher->lastname.$teacher->firstname.$teacher->id;
        if (!array_key_exists($sessid, $sortedsubjectreviews)) {
            $sortedsubjectreviews[$sessid] = array();
        }
        if (!array_key_exists($studentname, $sortedsubjectreviews[$sessid])) {
            $sortedsubjectreviews[$sessid][$studentname] = array();
        }
        $sortedsubjectreviews[$sessid][$studentname][] = $subjectreview->get_plugin('subject')->get_review();;
    }

    ksort($sortedtutorreviews);
    $html = '';
    $output = $PAGE->get_renderer('local_progressreview');
    foreach ($sortedtutorreviews as $sessionreviews) {
        ksort($sessionreviews);
        foreach ($sessionreviews as $student => $tutorreview) {
            $heading = fullname($tutorreview->get_student()).' - '.get_string('pluginname', 'local_progressreview');
            $html .= $OUTPUT->heading($heading);
            $subjectdata = array();
            $session = $tutorreview->get_session();
            if (isset($sortedsubjectreviews[$session->id][$student])) {
                $subjectdata = $sortedsubjectreviews[$session->id][$student];
                $html .= $output->subject_review_table($subjectdata, false, $session->inductionreview);
            }

            $tutorplugins = $tutorreview->get_plugins();

            $reviewdata = array();
            $pluginrenderers = array();
            foreach ($tutorplugins as $plugin) {
                $reviewdata[] = $plugin->get_review();
                if (!$pluginrenderers[] = $PAGE->get_renderer('progressreview_'.$plugin->get_name())) {
                    throw new coding_exception('The progressreview_'.$plugin->get_name().' has no renderer.  It
                        must have a renderer with at least the review() method defined');
                }
            }

            $html .= $OUTPUT->heading(get_string('tutor', 'local_progressreview').': '.fullname($tutorreview->get_teacher()), 3);

            $tutorreviews = '';
            foreach ($pluginrenderers as $key => $pluginrenderer) {
                $tutorreviews .= $pluginrenderer->review($reviewdata[$key]);
            }

            $html .= $OUTPUT->container($tutorreviews, null, 'tutorreviews');
        }
    }

    $filename = '/tmp/'.md5($html).'.html';
    file_put_contents($filename, $html);
    echo 'Wrote reviews to '.$filename;
    exit();

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
