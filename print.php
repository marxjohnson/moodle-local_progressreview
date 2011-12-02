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
 * Allows selection of reviews for printing, then produces of PDF of those selected.
 *
 * @package   local_progressreview
 * @copyright 2011 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/user/selector/lib.php');
require_once($CFG->dirroot.'/local/progressreview/renderer.php');
require_once($CFG->dirroot.'/local/progressreview/lib.php');
require_once($CFG->dirroot.'/local/progressreview/fpdf/class.fpdf_table.php');

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
        ini_set('max_execution_time', 0);
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
        $sortedsubjectreviews[$sessid][$studentname][] = $subjectreview->get_plugin('subject')->get_review();
    }

    ksort($sortedtutorreviews);
    $html = '';
    $pdf = pdf_writer::init('A4', 'L');
    $output = $PAGE->get_renderer('local_progressreview_print');
    foreach ($sortedtutorreviews as $sessionreviews) {
        ksort($sessionreviews);
        foreach ($sessionreviews as $student => $tutorreview) {
            $session = $tutorreview->get_session();
            $heading = fullname($tutorreview->get_student()).' - '.$session->name;
            $pdf = $output->heading($heading, 1);
            $subjectdata = array();
            if (isset($sortedsubjectreviews[$session->id][$student])) {
                $subjectdata = $sortedsubjectreviews[$session->id][$student];
                $pdf = $output->subject_review_table($subjectdata);
            }

            $tutorplugins = $tutorreview->get_plugins();

            $reviewdata = array();
            $pluginrenderers = array();
            foreach ($tutorplugins as $plugin) {
                require_once($CFG->dirroot.'/local/progressreview/plugins/'.$plugin->get_name().'/renderer.php');
                $reviewdata[] = $plugin->get_review();
                if (!$pluginrenderers[] = $PAGE->get_renderer('progressreview_'.$plugin->get_name().'_print')) {
                    throw new coding_exception('The progressreview_'.$plugin->get_name().' has no print renderer.
                        It must have a print renderer with at least the review() method defined');
                }
            }

            $pdf = $output->heading(get_string('tutor', 'local_progressreview').': '.fullname($tutorreview->get_teacher()), 3);

            $tutorreviews = '';
            foreach ($pluginrenderers as $key => $pluginrenderer) {
                $pdf = $pluginrenderer->review($reviewdata[$key]);
            }
        $pdf = pdf_writer::page_break();
        }
    }

    /*$pdf->ezText("\n\n".$pdf->messages,10,array('justification'=>'left'));
    $pdfcode = $pdf->output(1);
    $end_time = microtime();
    $pdfcode = str_replace("\n","\n<br>",htmlspecialchars($pdfcode));
    echo '<html><body>';
    echo trim($pdfcode);
    echo '</body></html>';
     */
    pdf_writer::div(pdf_writer::$debug);
    $pdf->Output();

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
