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
 * Displays and processes form for changing grade scales attached to reviews
 *
 * @package   local_progressreview
 * @copyright 2011 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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
$urlparams = array('sessionid' => $sessionid, 'courseid' => $courseid);
$PAGE->set_url('/local/progressreview/changescale.php', $urlparams);
$PAGE->navbar->add(get_string('pluginname', 'local_progressreview'));
$PAGE->navbar->add($session->name);

$reviews = progressreview_controller::get_reviews($sessionid, null, $courseid);
$scaleid = current($reviews)->get_plugin('subject')->get_scaleid();

$formdata = array('sessionid' => $sessionid, 'courseid' => $courseid, 'scaleid' => $scaleid);
$form = new progressreview_changescale_form('', $formdata);

$content = '';

if ($data = $form->get_data()) {
    $newscaleid = $data->scaleid;
    foreach($reviews as $review) {
        $review->get_plugin('subject')->update(array('scaleid' => $newscaleid));
    }
    $redirectparams = array('sessionid' => $sessionid, 'courseid' => $courseid);
    $redirecturl = new moodle_url('/local/progressreview/subjectreview.php', $redirectparams);
    redirect($redirecturl, get_string('changessaved'), 2);
}

echo $OUTPUT->header();

echo $content;
$form->display();

echo $OUTPUT->footer();


