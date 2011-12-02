<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Displays current and historic reviews for the specified user
 *
 * @package   local_progressreview
 * @copyright 2011 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/local/progressreview/lib.php');
require_once($CFG->dirroot.'/local/progressreview/renderer.php');

require_login($SITE);
$sessionid = optional_param('sessionid', false, PARAM_INT);
$userid = required_param('userid', PARAM_INT);

$user = progressreview_controller::validate_student($userid);
if($sessions = progressreview_controller::get_sessions_for_student($user)) {
    if (!$sessionid) {
        $sessionid = current($sessions)->id;
    }
    $session = progressreview_controller::validate_session($sessionid);
} else {
    print_error('noreviewsforstudent', 'local_progressreview');
}

$PAGE->set_context(get_context_instance(CONTEXT_USER, $user->id));

if ($user->id == $USER->id) {
    require_capability('moodle/local_progressreview:viewown', $PAGE->context);
} else {
    require_capability('moodle/local_progressreview:view', $PAGE->context);
}

$params = array('userid' => $userid);
$PAGE->set_url('/local/progressreview/user.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('user-profile');

$strreviews = get_string('pluginname', 'local_progressreview');
$PAGE->set_heading(fullname($user).": $strreviews");
$PAGE->set_title(fullname($user).": $strreviews");

$PAGE->navigation->extend_for_user($user);
$output = $PAGE->get_renderer('local_progressreview');

$subjectreviews = progressreview_controller::get_reviews($session->id, $user->id);
$subjectdata = array();
foreach ($subjectreviews as $subjectreview) {
    $subjectdata[] = $subjectreview->get_plugin('subject')->get_review();
}

if ($tutorreview = current(progressreview_controller::get_reviews($session->id, $user->id, null, null, PROGRESSREVIEW_TUTOR))) {
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
}
add_to_log(SITEID, 'local_progressreview', 'view', $PAGE->url->out());

$content = $OUTPUT->heading(fullname($user).' - '.get_string('pluginname', 'local_progressreview'));

$content .= $output->user_session_links($user, $sessions, $sessionid);

$content .= $output->subject_review_table($subjectdata, false, $session->inductionreview, PROGRESSREVIEW_TEACHER);

if ($tutorreview) {
    $content .= $OUTPUT->heading(get_string('tutor', 'local_progressreview').': '.fullname($tutorreview->get_teacher()), 3);

    $tutorreviews = '';
    foreach ($pluginrenderers as $key => $pluginrenderer) {
        $tutorreviews .= $pluginrenderer->review($reviewdata[$key]);
    }

    $content .= $OUTPUT->container($tutorreviews, null, 'tutorreviews');
}

echo $OUTPUT->header();

echo $content;

echo $OUTPUT->footer();


