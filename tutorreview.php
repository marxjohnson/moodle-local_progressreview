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
 * Displays and processes form for entering Tutor reviews
 *
 * If no student is selected, a summary of review completion for the course is displayed.
 * If a student is selected, the form is displayed.
 *
 * @package   local_progressreview
 * @copyright 2011 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/local/progressreview/lib.php');
require_once($CFG->dirroot.'/local/progressreview/renderer.php');
require_once($CFG->dirroot.'/local/progressreview/tutor_form.php');

$sessionid = required_param('sessionid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$studentid = optional_param('studentid', false, PARAM_INT); // Id of the student we're viewing
$buttons = optional_param('buttons', false, PARAM_CLEAN);
// If we've just moved forwards/backwards, we'll get the student ID from the button that was
// clicked
if (!$studentid) {
    if (isset($buttons['prev'])) {
        $studentid = optional_param('previd', false, PARAM_INT);
    }
}
if (!$studentid) {
    if (isset($buttons['next'])) {
        $studentid = optional_param('nextid', false, PARAM_INT);
    }
}
// If we've just submitted a review, this is the ID of the student that review was for. This will
// be different from the student ID as we'll either have moved forwards/backwards or gone back to
// the list (in which case there will be no student ID).
$editid = optional_param('editid', false, PARAM_INT);
$readonly = false;
$coursecontext = get_context_instance(CONTEXT_COURSE, $courseid);
$categorycontext = get_context_instance_by_id(current(get_parent_contexts($coursecontext)));

if (!$session = $DB->get_record('progressreview_session', array('id' => $sessionid))) {
    print_error('invalidsession', 'local_progressreview');
}

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'local_progressreview');
}

$isteacher = has_capability('moodle/local_progressreview:write', $coursecontext);
$ismanager = has_capability('moodle/local_progressreview:viewall', $categorycontext);
$indexlink = null;
if ($isteacher || $ismanager) {
    $mode = PROGRESSREVIEW_TEACHER;
    if (!$isteacher) {
        $indexlink = new moodle_url('/local/progressreview/index.php');
        if ($studentid) {
            $redirectparams = array(
                'sessionid' => $sessionid,
                'userid' => $studentid
            );
            redirect(new moodle_url('/local/progressreview/user.php', $redirectparams));
        }
    }
} else if (has_capability('moodle/local_progressreview:hasreview', $coursecontext)) {
    $mode = PROGRESSREVIEW_STUDENT;
    if (!$studentid) {
        $redirectparams = array(
            'courseid' => $courseid,
            'studentid' => $USER->id,
            'sessionid' => $sessionid
        );
        $redirecturl = new moodle_url('/local/progressreview/tutorreview.php', $redirectparams);
        redirect($redirecturl);
    }
} else {
    print_error('noaccess');
}

require_login($course);
$PAGE->navbar->add(get_string('pluginname', 'local_progressreview'), $indexlink);
$params = array('sessionid' => $sessionid, 'courseid' => $courseid);
$listurl = new moodle_url('/local/progressreview/tutorreview.php', $params);
$PAGE->set_url($listurl);

$output = $PAGE->get_renderer('local_progressreview');
$content = '';

if ($mode == PROGRESSREVIEW_TEACHER) {

    if ($editid) {
        // A form has been submitted, process it.
        $editstudent = $DB->get_record('user', array('id' => $editid));
        $editreview = new progressreview($editstudent->id,
                                         $sessionid,
                                         $courseid,
                                         $USER->id,
                                         PROGRESSREVIEW_TUTOR);
        $editform = new progressreview_tutor_form(null, array('progressreview' => $editreview));
        try {
            if ($data = $editform->get_data()) {
                $editform->process($data);
                add_to_log($course,
                           'local_progressreview',
                           'update',
                           $PAGE->url->out(),
                           $editstudent->id);
                $strsavedreview = get_string('savedreviewfor',
                                             'local_progressreview',
                                             fullname($editstudent));
                $content .= $OUTPUT->notification($strsavedreview);
            }
        } catch (dml_write_exception $e) {
            add_to_log($course->id,
                       'local_progressreview',
                       'update',
                       $PAGE->url->out(),
                       $student->id.': '.$e->error);
            $strnotsaved = get_string('changesnotsaved', 'local_progressreview');
            $content .= $OUTPUT->error_text($strnotsaved);
            // We've not saved changes and need to display an error, so reset the student ID to
            // that of the student we just edited, so we'll see their form again.
            $studentid = $editid;
        } catch (progressreview_invalidvalue_exception $e) {
            $strnotsaved = get_string('changesnotsaved', 'local_progressreview');
            $content .= $OUTPUT->error_text($strnotsaved.' '.$e->getMessage());
            // We've not saved changes and need to display an error, so reset the student ID to
            // that of the student we just edited, so we'll see their form again.
            $studentid = $editid;
        }
    }

    // Now we know for sure which student we're looking at (if any) we can set the navbar, URL etc.
    if ($studentid) {
        $PAGE->navbar->add($session->name, $PAGE->url);
        $student = $DB->get_record('user', array('id' => $studentid));
        $PAGE->url->params(array('studentid' => $studentid));
        $PAGE->navbar->add(fullname($student));
    } else {
        $PAGE->navbar->add($session->name);
    }

    // If we're *not* viewing the form that we just edited again (i.e. we've moved to another
    // student), unset _POST so that the data we just submitted isn't displayed in the new form
    if ($editid != $studentid) {
        unset($_POST);
    }

    // If there's a student ID, show their review form
    if ($studentid) {
        $review = new progressreview($student->id,
                                     $sessionid,
                                     $courseid,
                                     $USER->id,
                                     PROGRESSREVIEW_TUTOR);
        $form = new progressreview_tutor_form(null, array('progressreview' => $review));
        $data = new stdClass;
        $form->set_data($data);
    } else {
        // If there's no student ID, show the list of students
        $tutorgroup = progressreview_controller::get_reviews($sessionid,
                                                             null,
                                                             $courseid,
                                                             null,
                                                             PROGRESSREVIEW_TUTOR);
        usort($tutorgroup, function($a, $b) {
            $student_a = $a->get_student();
            $student_b = $b->get_student();
            $lastname = strcmp($student_a->lastname, $student_b->lastname);
            if ($lastname) {
                return $lastname;
            } else {
                return strcmp($student_a->firstname, $student_b->firstname);
            }
        });
        $content .= $output->tutorgroup_list($tutorgroup);
    }
}
add_to_log($course->id, 'local_progressreview', 'view', $PAGE->url->out(), $studentid);
if (isset($form)) {
    if (!empty($session->deadline_tutor)) {
        $deadline = userdate($session->deadline_tutor);
        $strdeadline = get_string('completetutorreviewsby', 'local_progressreview', $deadline);
        $content .= $OUTPUT->container($strdeadline, 'reviewnotes');
    }
    if (!empty($session->previoussession)) {
        $previoussession = progressreview_controller::validate_session($session->previoussession);
        $strprevious = get_string('previousfigures', 'local_progressreview', $previoussession->name);
        $content .= $OUTPUT->container($strprevious, 'reviewnotes');
    }
    ob_start();
    $form->display();
    $content .= str_replace('&amp;', '&', ob_get_contents());
    ob_end_clean();
}

echo $OUTPUT->header();

echo $content;

echo $OUTPUT->footer();
