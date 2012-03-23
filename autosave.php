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
 * Autosave script to be called by AJAX
 *
 * Checks all the required parameters are present and valid, and
 * passes the data to the appropriate plugin for processing.
 *
 * @package   local_progressreview
 * @copyright 2011 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Uncommenting the following line will force autosave to fail, which is useful for testing
// the manual fallback.
// header('HTTP/1.1 400 Bad Request');die('{"errortype":"progressreview_autosave_exception","message":"epic fail"}');

define('AJAX_SCRIPT', true);
require_once('../../config.php');
require_once($CFG->dirroot.'/local/progressreview/lib.php');

try {
    $sessionid = required_param('sessionid', PARAM_INT);
    $studentid = required_param('studentid', PARAM_INT);
    $courseid = required_param('courseid', PARAM_INT);
    $teacherid = required_param('teacherid', PARAM_INT);
    $type = required_param('reviewtype', PARAM_INT);
    $plugin = required_param('plugin', PARAM_TEXT);
    $field = urldecode(required_param('field', PARAM_TEXT));
    $value = urldecode(required_param('value', PARAM_TEXT));

    progressreview_controller::validate_session($sessionid);
    progressreview_controller::validate_student($studentid);
    progressreview_controller::validate_course($courseid);
    progressreview_controller::validate_teacher($teacherid);

    require_login($courseid);

    $coursecontext = get_context_instance(CONTEXT_COURSE, $courseid);

    require_capability('moodle/local_progressreview:write', $coursecontext);

    $progressreview = current(progressreview_controller::get_reviews($sessionid,
                                                                     $studentid,
                                                                     $courseid,
                                                                     $teacherid,
                                                                     $type));

    $plugin = $progressreview->get_plugin($plugin);

    $plugin->validate(array($field => $value));
    $plugin->autosave($field, $value);

} catch (moodle_exception $e) {
    add_to_log($courseid, 'local_progressreview', 'update failed', '', get_class($e));
    header('HTTP/1.1 400 Bad Request');
    progressreview_controller::xhr_response($e);
} catch (progressreview_invalidfield_exception $e) {
    add_to_log($courseid, 'local_progressreview', 'update failed', '', get_class($e));
    header('HTTP/1.1 400 Bad Request');
    progressreview_controller::xhr_response($e);
} catch (dml_write_exception $e) {
    add_to_log($courseid, 'local_progressreview', 'update failed', '', get_class($e));
    header('HTTP/1.1 400 Bad Request');
    progressreview_controller::xhr_response($e);
} catch (require_login_exception $e) {
    add_to_log($courseid, 'local_progressreview', 'update failed', '', get_class($e));
    header('HTTP/1.1 403 Forbidden');
    progressreview_controller::xhr_response($e);
} catch (required_capability_exception $e) {
    add_to_log($courseid, 'local_progressreview', 'update failed', '', get_class($e));
    header('HTTP/1.1 403 Forbidden');
    progressreview_controller::xhr_response($e);
} catch (progressreview_invalidvalue_exception $e) {
    add_to_log($courseid, 'local_progressreview', 'update failed', '', get_class($e));
    header('HTTP/1.1 400 Bad Request');
    progressreview_controller::xhr_response($e);
}
