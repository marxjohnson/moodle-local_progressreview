<?php

require_once('../../config.php');
require_once($CFG->dirroot.'/local/progressreview/lib.php');
require_once($CFG->dirroot.'/local/progressreview/renderer.php');

$sessionid = optional_param('sessionid', null, PARAM_INT);
$teacherid = optional_param('teacherid', null, PARAM_INT);
$courseid = optional_param('courseid', null, PARAM_INT);
$studentid = optional_param('studentid', null, PARAM_INT);
$sort = optional_param('sort', null, PARAM_TEXT);
$generate = optional_param('generate', false, PARAM_BOOL);

require_login($SITE);
$systemcontext = get_context_instance(CONTEXT_SYSTEM);
require_capability('moodle/local_progressreview:print', $PAGE->context);

$PAGE->set_url('/local/progressreview/print.php');
$PAGE->navbar->add(get_string('pluginname', 'local_progressreview'));
$PAGE->navbar->add(get_string('print', 'local_progressreview'));
add_to_log(SITEID, 'local_progressreview', 'view', $PAGE->url->out());

$output = $PAGE->get_renderer('local_progressreview');
$content = '';

$content .= $OUTPUT->heading(get_string('printheading', 'local_progressreview'), 2);

$legend = html_writer::tag('legend', get_string('selectcriteria', 'local_progressreview'));
$fieldset = html_writer::tag('fieldset', $legend.$fields);

$formattrs = array('method' => 'post', 'action' => $PAGE->url->out());
$form = html_writer::tag('form', $fieldset, $formattrs);

echo $OUTPUT->header();

$output->tabs(2);
echo $content;

echo $OUTPUT->footer();
