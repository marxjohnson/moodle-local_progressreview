<?php

define('AJAX_SCRIPT', true);
require_once '../../config.php';
require_once $CFG->dirroot.'/local/progressreview/lib.php';

try {
    $sessionid = required_param('sessionid', PARAM_INT);
    $studentid = required_param('studentid', PARAM_INT);
    $courseid = required_param('courseid', PARAM_INT);
    $teacherid = required_param('teacherid', PARAM_INT);
    $type = required_param('reviewtype', PARAM_INT);
    $plugin = required_param('plugin', PARAM_TEXT);
    $field = required_param('field', PARAM_TEXT);
    $value = required_param('value', PARAM_TEXT);

    progressreview_controller::validate_session($sessionid);
    progressreview_controller::validate_student($studentid);
    progressreview_controller::validate_course($courseid);
    progressreview_controller::validate_teacher($teacherid);
} catch (moodle_exception $e) {
    header('HTTP/1.1 400 Bad Request');
    die($e->getMessage());
}

try {
    require_login($courseid);
} catch (require_login_exception $e) {
    header('HTTP/1.1 403 Forbidden');
    die($e->getMessage());
}

$coursecontext = get_context_instance(CONTEXT_COURSE, $courseid);

try {
    require_capability('moodle/local_progressreview:write', $coursecontext);
} catch (required_capability_exception $e) {
    header('HTTP/1.1 403 Forbidden');
    die($e->getMessage());
}

$progressreview = current(progressreview_controller::get_reviews($sessionid,
                                                                 $studentid,
                                                                 $courseid,
                                                                 $teacherid,
                                                                 $type));

$plugin = $progressreview->get_plugin($plugin);

try {
    $plugin->autosave($field, $value);
} catch (progressreview_autosave_exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    die($e->getMessage());
} catch (progressreview_invalidfield_exception $e) {
    header('HTTP/1.1 400 Bad Request');
    die($e->getMessage());
} catch (dml_write_exception $e) {
    header('HTTP/1.1 400 Bad Request');
    die($e->error);
}
