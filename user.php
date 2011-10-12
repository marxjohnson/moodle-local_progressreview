<?php

require_once('../../config.php');
require_once($CFG->dirroot.'/local/progressreview/lib.php');
require_once($CFG->dirroot.'/local/progressreview/renderer.php');

$sessionid = required_param('sessionid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

$session = progressreview_controller::validate_session($sessionid);
$user = progressreview_controller::validate_student($userid);

$PAGE->set_context(get_context_instance(CONTEXT_USER, $user->id));

if ($user->id == $USER->id) {
    require_capability('moodle/local_progressreview:viewown', $PAGE->context);
} else {
    require_capability('moodle/local_progressreview:view', $PAGE->context);
}

$params = array(
    'sessionid' => $sessionid,
    'userid' => $userid
);
$PAGE->set_url('/local/progressreview/user.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('user-profile');

$PAGE->blocks->add_region('side-pre');
$strreviews = get_string('pluginname', 'local_progressreview');
$PAGE->set_heading(fullname($user).": $strreviews");

$PAGE->navigation->extend_for_user($user);
$output = $PAGE->get_renderer('local_progressreview');

$sessions = progressreview_controller::get_sessions_for_student($user);

$subjectreviews = progressreview_controller::get_reviews($session->id, $user->id);
$subjectdata = array();
foreach ($subjectreviews as $subjectreview) {
    $subjectdata[] = $subjectreview->get_plugin('subject')->get_review();
}

$tutorreview = current(progressreview_controller::get_reviews($session->id, $user->id, null, null, PROGRESSREVIEW_TUTOR));
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

$content = '';


    $content .= $output->user_session_links($user, $sessions, $sessionid);
$content .= $output->subject_review_table($subjectdata, false);

$content .= $OUTPUT->heading(get_string('tutor', 'local_progressreview').': '.fullname($tutorreview->get_teacher()), 3);

$tutorreviews = '';
foreach ($pluginrenderers as $key => $pluginrenderer) {
    $tutorreviews .= $pluginrenderer->review($reviewdata[$key]);
}

$content .= $OUTPUT->container($tutorreviews, null, 'tutorreviews');

echo $OUTPUT->header();

echo $content;

echo $OUTPUT->footer();


