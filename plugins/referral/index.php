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
 * Displays the given referral, if the user is allowed to view it
 *
 * @package   local_progressreview
 * @subpackage progressreview_referral
 * @copyright 2012 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../../config.php');
require_once($CFG->dirroot.'/local/progressreview/lib.php');

require_login($SITE);
$reviewid = required_param('reviewid', PARAM_INT);

$reviewrecord = $DB->get_record('progressreview', array('id' => $reviewid));
$progressreview = new progressreview($reviewrecord->studentid,
                                     $reviewrecord->sessionid,
                                     $reviewrecord->courseid,
                                     $reviewrecord->teacherid,
                                     PROGRESSREVIEW_TUTOR);

$referral = $progressreview->get_plugin('referral')->get_review();

if ($referral->userid != $USER->id && !is_siteadmin($USER)) {
    throw new moodle_exception('notuserforreferral', 'progressreview_referral');
    die();
}

$sessionname = $progressreview->get_session()->name;
$studentname = fullname($progressreview->get_student());
$tutorname = fullname($progressreview->get_teacher());
$PAGE->set_url('/local/progressreview/plugins/referral/index.php', array('reviewid' => $reviewid));
$PAGE->navbar->add(get_string('pluginname', 'local_progressreview'));
$PAGE->navbar->add(get_string('pluginname', 'progressreview_referral'));
$PAGE->navbar->add($progressreview->get_session()->name);
$PAGE->navbar->add($studentname);
$PAGE->set_title($studentname.' - '.get_string('referral', 'progressreview_referral'));

$content = '';
$content .= $OUTPUT->heading($studentname.' - '.$sessionname);
$content .= html_writer::tag('p', get_string('writtenby', 'progressreview_referral', $tutorname));
$content .= html_writer::tag('p', $referral->message);

echo $OUTPUT->header();

echo $content;

echo $OUTPUT->footer();
