<?php
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
