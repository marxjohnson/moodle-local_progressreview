<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/local/progressreview/lib.php');
require_once($CFG->dirroot.'/local/progressreview/renderer.php');

$courseid = optional_param('courseid', null, PARAM_INT);
$sessionid = optional_param('sessionid', null, PARAM_INT);

require_login($SITE);
$permissions = array(
    'admin' => false,
    'manager' => false,
    'teacher' => false,
    'student' => false
);

$systemcontext = get_context_instance(CONTEXT_SYSTEM);
if (has_capability('moodle/local_progressreview:manage', $systemcontext)) {
    $permissions['admin'] = true;
}

$categories = $DB->get_records('context', array('contextlevel' => CONTEXT_COURSECAT));
foreach ($categories as $category) {
    if (has_capability('moodle/local_progressreview:viewall', get_context_instance_by_id($category->id))) {
        $permissions['manager'][] = $category->instanceid;
    }
}

if ($courseid) {
    $courses = $DB->get_records('course', array('id' => $courseid));
    $courses[$courseid]->context = get_context_instance(CONTEXT_COURSE, $courseid);
} else {
    $courses = progressreview_controller::get_my_review_courses($sessionid);
}
foreach ($courses as $course) {
    if (has_capability('moodle/local_progressreview:write', $course->context)) {
        $permissions['teacher'][] = $course->id;
    } else if (has_capability('moodle/local_progressreview:viewown', $course->context)) {
        $permissions['student'][] = $course->id;
    }
}

$permissions = array_filter($permissions);
if (empty($permissions)) {
    print_error('noaccess');
}

$PAGE->set_url('/local/progressreview/');
$PAGE->navbar->add(get_string('pluginname', 'local_progressreview'));
add_to_log(SITEID, 'local_progressreview', 'view', $PAGE->url->out());
$output = $PAGE->get_renderer('local_progressreview');
$content = '';

$sessions = progressreview_controller::get_sessions();
$session_links = $output->session_links($PAGE->url, $sessions);

if (isset($permissions['admin'])) {
    $sessions_table = $output->sessions_table($sessions);
    $content .= $sessions_table;
}

if (isset($permissions['manager'])) {
    if (!empty($sessions)) {
        if (!$sessionid) {
            $sessionid = current($sessions)->id;
        }
        foreach ($permissions['manager'] as $categoryid) {
            $category = $DB->get_record('course_categories', array('id' => $categoryid));
            $subjectsummaries = progressreview_controller::get_course_summaries($sessionid, PROGRESSREVIEW_SUBJECT, $category->id);
            $tutorsummaries = progressreview_controller::get_course_summaries($sessionid, PROGRESSREVIEW_TUTOR, $category->id);
            if ($subjectsummaries || $tutorsummaries) {
                $department_table = $output->department_table($category, $subjectsummaries, $tutorsummaries);
                $content .= $department_table;
            }
        }
    } else {
        $content .= $OUTPUT->box($OUTPUT->error_text(get_string('nosessions', 'local_progressreview')));
    }
}

if (isset($permissions['teacher'])) {
    if ($courseid) {
        $course_table = $output->course_table($courses[$courseid]);
        $content .= $course_table;
    } else {
        $courses_table = $output->courses_table($courses);
        $content .= $courses_table;
    }
} else if (isset($permissions['student'])) { // If user has teacher permissions, ignore student permissons
    $content .= $session_links;
    if (!$sessionid) {
        $sessionid = current($sessions)->id;
    }
    $reviews = progressreview_controller::get_reviews($sessionid, $USER->id);
    $user_reviews = $output->user_reviews($reviews);
    $content .= $user_reviews;
}

// Begin output

echo $OUTPUT->header();

echo $content;

echo $OUTPUT->footer();
