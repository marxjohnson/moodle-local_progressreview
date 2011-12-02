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
 * Displays forms for creation and configuration of review sessions
 *
 * If no session is specified, a form is displayed to create a new one.
 * If a session is specified for editing, the form is displayed to allow it to be edited.
 * If a session is specified for configuration, a form is displayed to allow reviews to be generated
 * for the session.
 *
 * @package   local_progressreview
 * @copyright 2011 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once($CFG->dirroot.'/local/progressreview/lib.php');
require_once($CFG->dirroot.'/local/progressreview/course_selector.php');
require_once($CFG->dirroot.'/local/progressreview/renderer.php');
require_once($CFG->dirroot.'/local/progressreview/sessions_form.php');

require_login($SITE);
require_capability('moodle/local_progressreview:manage', $PAGE->context);

$id = optional_param('id', null, PARAM_INT);
$editid = optional_param('editid', '', PARAM_INT);
$generate_subject = optional_param('generate_subject', false, PARAM_TEXT);
$regenerate_subject = optional_param('regenerate_subject', false, PARAM_TEXT);
$snapshot = optional_param('snapshot', false, PARAM_TEXT);
$generate_tutor = optional_param('generate_tutor', false, PARAM_TEXT);
$regenerate_tutor = optional_param('regenerate_tutor', false, PARAM_TEXT);

$params = array_filter(array('id' => $id, 'editid' => $editid));
$PAGE->set_url('/local/progressreview/session.php', $params);
$PAGE->navbar->add(get_string('pluginname', 'local_progressreview'), '/local/progressreview');
$PAGE->navbar->add(get_string('sessions', 'local_progressreview'));

if (!$id) {

    $form = new progressreview_session_form();

    if ($data = $form->get_data()) {
        $sessionid = $form->process($data);
        add_to_log(SITEID, 'local_progressreview', 'update', $PAGE->url->out(), $sessionid);
        redirect($PAGE->url->out(true, array('id' => $sessionid)), get_string('sessioncreated', 'local_progressreview'));
        exit();
    }

    if ($editid) {
        $session = $DB->get_record('progressreview_session', array('id' => $editid));
        $session->editid = $session->id;
        $form->set_data($session);
    }

    add_to_log(SITEID, 'local_progressreview', 'view form', $PAGE->url->out(), $editid);
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

    if ($generate_subject) {
        $subjects = $potential_subject_selector->get_selected_users();
    } else if ($regenerate_subject || $snapshot) {
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
    $content = $OUTPUT->heading(get_string('subjectreviews', 'local_progressreview'), 2);
    $content .= $subject_selector;

    // Tutor group selector
    $potential_tutor_selector = new progressreview_potential_course_selector('potential_tutors');
    $distributed_tutor_selector = new progressreview_distributed_course_selector('distributed_tutors', $session->id, PROGRESSREVIEW_TUTOR);

    $excludes = $distributed_tutor_selector->find_users();
    foreach ($excludes as $exclude) {
        $potential_tutor_selector->exclude(array_keys($exclude));
    }

    $tutors = array();

    if ($generate_tutor) {
        $tutors = $potential_tutor_selector->get_selected_users();
    } else if ($regenerate_tutor) {
        $tutors = $distributed_tutor_selector->get_selected_users();
    }

    if ($tutors) {
        add_to_log(SITEID, 'local_progressreview', 'update reviews', $PAGE->url->out(), count($tutors));
        foreach ($tutors as $tutor) {
            progressreview_controller::generate_reviews_for_course($tutor->id, $session->id, PROGRESSREVIEW_TUTOR);
        }
        redirect($PAGE->url->out(), get_string('reviewsgenerated', 'local_progressreview'));
        exit();
    }
    add_to_log(SITEID, 'local_progressreview', 'view', $PAGE->url->out());

    $tutor_selector = $output->course_selector_form($potential_tutor_selector, $distributed_tutor_selector, $session->id, PROGRESSREVIEW_TUTOR);
    $content .= $OUTPUT->heading(get_string('tutorreviews', 'local_progressreview'), 2);
    $content .= $tutor_selector;
}

echo $OUTPUT->header();

if (isset($form)) {
    $form->display();
} else {
    echo $content;
}

echo $OUTPUT->footer();

