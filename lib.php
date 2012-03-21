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
 * Main library for the plugin
 *
 * Defines constants, exceptions, and the following classes:
 * progressreview
 * progressreview_controller (static)
 * progressreview_cache (static)
 * progressreview_plugin
 * print_criterion
 * progressreview_print_selector and subclasses:
 *  progressreview_session_selector
 *  progressreview_course_selector
 *  progressreview_teacher_selector
 *  progressreview_student_selector
 * pdf_writer (static)
 *
 * @package   local_progressreview
 * @copyright 2011 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * class progressreview
 * Controller for all operations on progressreview data
 *
 * Given a student, session, course and teacher, this will initialise an interface
 * to a progressreview via all associated plugins.
 */
const PROGRESSREVIEW_TUTOR = 1;
const PROGRESSREVIEW_SUBJECT = 2;

const PROGRESSREVIEW_TEACHER = 0;
const PROGRESSREVIEW_STUDENT = 1;

class progressreview {

     /*** Attributes: ***/

    /**
     * The ID of the record for this review in the progressreview table
     * @access public
     */
    public $id;

    /**
     * An array containing the object for each plugin active in this review's session
     * @access public
     */
    private $plugins;

    /**
     * The type of review this is - PROGRESSREVIEW_SUBJECT or PROGRESSREVIEW_TUTOR
     * @access public
     */
    private $type;

    /**
     * The record from the progressreview_session table for this review's session
     * @access private
     */
    private $session;

    /**
     * The record from progressreview_teacher for the teacher who this review is
     * written by
     * @access private
     */
    private $teacher;

    /**
     * The record from mdl_student for the student who this review is being written for
     * @access private
     */
    private $student;

    /**
     * The record from progressreview_course for the course this review is for
     * @access private
     */
    private $course;

    /**
     * The progress review object for this teacher/student/course in the previous session
     * @access private
     */
    private $previous_review;


    /**
     * The constructor, initialises the interface.
     *
     * Uses each ID passed to fill the $session, $teacher, $student and $course
     * attributes with the appropriate records, then calls get_plugins to initialise
     * the plugins required for this review session and the review type.
     *
     * @param int studentid

     * @param int sessionid

     * @param int courseid

     * @param int teacherid

     * @return true
     * @access public
     */
    public function __construct($studentid,  $sessionid,  $courseid,  $teacherid, $type = null) {
        global $DB;

        try {
            $this->session = $this->retrieve_session($sessionid);
            $this->teacher = $this->retrieve_teacher($teacherid);
            $this->student = $this->retrieve_student($studentid);
            $this->course = $this->retrieve_course($courseid);
        } catch (progressreview_nouser_exception $e) {
            throw $e;
        }
        $this->previous_review = null;

        if (!is_array($this->session->scale_behaviour)) {
            $this->session->scale_behaviour = explode(',', $this->session->scale_behaviour);
        }
        if (!is_array($this->session->scale_homework)) {
            $this->session->scale_homework = explode(',', $this->session->scale_homework);
        }
        if (!is_array($this->session->scale_effort)) {
            $this->session->scale_effort = explode(',', $this->session->scale_effort);
        }

        $params = array(
            'studentid' => $studentid,
            'courseid' => $courseid,
            'teacherid' => $teacherid,
            'sessionid' => $sessionid
        );
        if ($review = $DB->get_record('progressreview', $params)) {
        	$this->id = $review->id;
        	$this->type = $review->reviewtype;
        } else {
            $review = (object)$params;
            if ($type) {
        	$review->reviewtype = $this->type = $type;
                $this->id = $DB->insert_record('progressreview', $review);
            } else {
                throw new coding_exception('You must specify a type when creating a review');
            }
        }

        $this->init_plugins();

        return true;
    } // end of member function __construct

    public function get_student() {
        return $this->student;
    }

    public function get_teacher() {
        return $this->teacher;
    }

    public function get_session() {
        return $this->session;
    }

    public function get_course() {
        return $this->course;
    }

    public function get_type() {
        return $this->type;
    }

    public function get_plugin($name) {
        return $this->plugins[$name];
    }

    public function get_plugins() {
        return $this->plugins;
    }

    public function get_previous() {
        if (is_null($this->previous_review)) {
            $this->previous_review = current(progressreview_controller::get_reviews(
                $this->session->previoussession,
                $this->student->id,
                $this->course->originalid,
                $this->teacher->originalid,
                $this->type
            ));
        }
        return $this->previous_review;
    }

    /**
     * Transfers this review to allow a different teacher to write it
     *
     * @param stdClass teacher The new teacher's record from the user table

     * @return bool indicating success
     * @access public
     */
    public function transfer_to_teacher($teacher) {
        global $DB;

        $this->teacher = $this->retrieve_teacher($teacherid);
        $params = (object)array('id' => $this->id, 'teacherid' => $this->teacher->id);
        return $DB->update_record('progressreview', $params);
    } // end of member function transfer_to_teacher



    /**
     * Initialises the required plugins for this review
     *
     * Based on the plugins indicated in progressreview_activeplugins that apply to the
     * type of this review, this method initialises an instance of each and stores them
     * in the $plugins
     *
     * @return true
     * @access private
     */
    private function init_plugins() {
        global $DB, $CFG;
        $params = array('sessionid' => $this->session->id, 'reviewtype' => $this->type);
        $activeplugins = $DB->get_records('progressreview_activeplugins', $params);
        foreach ($activeplugins as $activeplugin) {
            require_once($CFG->dirroot.'/local/progressreview/plugins/'.$activeplugin->plugin.'/lib.php');
            $classname = 'progressreview_'.$activeplugin->plugin;
            $this->plugins[$activeplugin->plugin] = new $classname($this);
        }
        return true;
    } // end of member function get_plugins

    private function retrieve_session($id) {
        global $DB;
        if (!array_key_exists($id, progressreview_cache::$sessions)) {
            $session = $DB->get_record('progressreview_session', array('id' => $id));
            progressreview_cache::$sessions[$id] = $session;
        }

        return progressreview_cache::$sessions[$id];
    }

    private function retrieve_student($id) {
        global $DB;
        if (!array_key_exists($id, progressreview_cache::$students)) {
            if (!$student = $DB->get_record('user', array('id' => $id))) {
                throw new progressreview_nouser_exception();
            }
            progressreview_cache::$students[$id] = $student;
        }

        return progressreview_cache::$students[$id];
    }

    /**
     * Returns the record in progressreview_teacher for the given user's id, creating
     * the record if required.
     *
     * @param int id The ID of the teacher's user record in user

     * @return object The teacher's record from progressreview_teacher
     * @access private
     */
    private function retrieve_teacher($id) {
        global $DB;

        if (!array_key_exists($id, progressreview_cache::$teachers)) {
            $params = array('originalid' => $id);
            if (!$teacher = $DB->get_record('progressreview_teachers', $params)) {
                $params = array('id' => $id);
                if (!$teacher = $DB->get_record('user', $params, 'id, firstname, lastname')) {
                    throw new progressreview_nouser_exception();
                }
                $teacher->originalid = $teacher->id;
                unset($teacher->id);
                $teacher->id = $DB->insert_record('progressreview_teachers', $teacher);
            }
            progressreview_cache::$teachers[$id] = $teacher;
        }

        return progressreview_cache::$teachers[$id];
    } // end of member function retrieve_teacher

    /**
     * Returns the record for the course from progressreview_course, creates the record
     * from data in course if required.
     *
     * @param int id The id of the course in the course table

     * @return object the course's record from progressreview_course
     * @access private
     */
    private function retrieve_course($id) {
        global $DB;

        if (!array_key_exists($id, progressreview_cache::$courses)) {
            if (!$course = $DB->get_record('progressreview_course', array('originalid' => $id))) {
                $course = $DB->get_record('course', array('id' => $id), 'id, shortname, fullname');
                $course->originalid = $course->id;
                unset($course->id);
                $course->id = $DB->insert_record('progressreview_course', $course);
            }
            progressreview_cache::$courses[$id] = $course;
        }

        return progressreview_cache::$courses[$id];
    } // end of member function retrieve_course

    /**
     * Deletes all data associated with this review
     */
    public function delete() {
        global $DB;
        try {
            foreach ($this->plugins as $plugin) {
                $plugin->delete();
            }
            if (!$DB->delete_records('progressreview', array('id' => $this->id))) {
                $error = 'Couldn\'t delete review record '.$this->id;
                throw new progressreview_nodelete_exception($error);
            }
        } catch (dml_exception $e) {
            $error = 'Couldn\'t delete review record '.$this->id.': '.$e->getMessage();
            throw new progressreview_nodelete_exception($error);
        }
    }

} // end of progressreview

/**
 * class progressreview_controller
 *
 */
class progressreview_controller {

    public static function validate_session($id) {
        global $DB;
        if (array_key_exists($id, progressreview_cache::$sessions)) {
            return progressreview_cache::$sessions[$id];
        } else if ($session = $DB->get_record('progressreview_session', array('id' => $id))) {
            progressreview_cache::$sessions[$id] = $session;
            return progressreview_cache::$sessions[$id];
        } else {
            throw new moodle_exception('invalidsession', 'local_progressreview', '', $id);
        }
    }

    public static function validate_course($id) {
        global $DB;
        if (array_key_exists($id, progressreview_cache::$courses)) {
            return progressreview_cache::$courses[$id];
        } else if ($course = $DB->get_record('progressreview_course', array('originalid' => $id))) {
            progressreview_cache::$courses[$id] = $course;
            return progressreview_cache::$courses[$id];
        } else if ($course = $DB->get_record('course', array('id' => $id), 'id, shortname, fullname')) {
            $course->originalid = $course->id;
            unset($course->id);
            $course->id = $DB->insert_record('progressreview_course', $course);
            progressreview_cache::$courses[$id] = $course;
            return progressreview_cache::$courses[$id];
        } else {
            throw new moodle_exception('invalidcourse', 'local_progressreview', '', $id);
        }
    }

    public static function validate_student($id) {
        global $DB;
        if (array_key_exists($id, progressreview_cache::$students)) {
            return progressreview_cache::$students[$id];
        } else if ($student = $DB->get_record('user', array('id' => $id))) {
            progressreview_cache::$students[$id] = $student;
            return progressreview_cache::$students[$id];
        } else {
            throw new moodle_exception('invalidstudent', 'local_progressreview', '', $id);
        }
    }

    public static function validate_teacher($id) {
        global $DB;
        if (array_key_exists($id, progressreview_cache::$teachers)) {
            return progressreview_cache::$teachers[$id];
        } else if ($teacher = $DB->get_record('progressreview_teachers', array('originalid' => $id))) {
            progressreview_cache::$teachers[$id] = $teacher;
            return progressreview_cache::$teachers[$id];
        } else if ($teacher = $DB->get_record('user', array('id' => $id), 'id, firstname, lastname')) {
            $teacher->originalid = $teacher->id;
            unset($teacher->id);
            $teacher->id = $DB->insert_record('progressreview_teachers', $teacher);
            progressreview_cache::$teachers[$id] = $teacher;
            return progressreview_cache::$teachers[$id];
        } else {
            throw new moodle_exception('invalidteacher', 'local_progressreview', '', $id);
        }
    }

    /**
     * Returns an array of records for each session in the database
     *
     * @return
     * @access public
     */
    public static function get_sessions() {
        global $DB;
        if (empty(progressreview_cache::$sessions)) {
            $sessions = $DB->get_records('progressreview_session', array(), 'deadline_tutor DESC');
            progressreview_cache::$sessions = $sessions;
        }
        return progressreview_cache::$sessions;
    } // end of member function get_sessions

    /**
     * Get just the sessions where $student has reviews
     */
    public static function get_sessions_for_student($student) {
        global $DB;
        $select = 'SELECT DISTINCT ps.* ';
        $from = 'FROM {progressreview_session} ps
            JOIN {progressreview} p ON p.sessionid = ps.id ';
        $where = 'WHERE p.studentid = ? ';
        $order = 'ORDER BY ps.deadline_tutor DESC';
        return $DB->get_records_sql($select.$from.$where.$order, array($student->id));
    }

    /**
     * Returns an array of all progressreview objects for the given conditions
     *
     * @param int sessionid
     * @param int studentid
     * @param int courseid
     * @param int teacherid
     * @param int type
     * @return
     * @access public
     */
    public static function get_reviews($sessionid = null,
                                       $studentid = null,
                                       $courseid = null,
                                       $teacherid = null,
                                       $type = PROGRESSREVIEW_SUBJECT) {

        if (!$sessionid && !$studentid && !$courseid && !$teacherid) {
            $error = 'get_reviews() must be called with at least one ID parameter';
            throw new coding_exception($error);
        }
        if (!in_array($type, array(PROGRESSREVIEW_SUBJECT, PROGRESSREVIEW_TUTOR))) {
            $error = '$type must be set to PROGRESSREVEW_SUBJECT or PROGRESSREVIEW_TUTOR';
            throw new coding_exception($error);
        }

        global $DB;

        $params = array_filter(
            array(
                'sessionid' => $sessionid,
                'studentid' => $studentid,
                'courseid' => $courseid,
                'teacherid' => $teacherid,
                'reviewtype' => $type
            )
        );

        $reviews = array();
        if($review_records = $DB->get_records('progressreview', $params)) {
            foreach ($review_records as $r) {
                try {
                    $reviews[$r->id] = new progressreview($r->studentid,
                                                          $r->sessionid,
                                                          $r->courseid,
                                                          $r->teacherid);
                } catch (progressreview_nouser_exception $e) {}
            }
        }
        return $reviews;

    } // end of member function get_reviews

    public static function delete_reviews($sessionid,
                                          $studentid = null,
                                          $courseid = null,
                                          $teacherid = null,
                                          $type = PROGRESSREVIEW_SUBJECT) {

        $reviews = self::get_reviews($sessionid, $studentid, $courseid, $teacherid, $type);
        foreach ($reviews as $review) {
            try {
                $review->delete();
            } catch (progressreview_nodelete_exception $e) {
                throw $e;
            }
        }
    }

    public static function get_course_summaries($session, $type, $categoryid = null) {
        if (!in_array($type, array(PROGRESSREVIEW_SUBJECT, PROGRESSREVIEW_TUTOR))) {
            $error = '$type must be set to PROGRESSREVEW_SUBJECT or PROGRESSREVIEW_TUTOR';
            throw new coding_exception($error);
        }

        global $DB;

        if ($type == PROGRESSREVIEW_SUBJECT) {
            $table = '{progressreview_subject}';
        } else {
            $table = '{progressreview_tutor}';
        }

        $params = array();
        $concat_sql = $DB->sql_concat('t.firstname', '" "', 't.lastname');
        $select = 'SELECT
                    p.id,
                    p.sessionid as sessionid,
                    s.name as sessionname,
                    c.id as courseid,
                    c.fullname AS name,
	            '.$concat_sql.' AS teacher,
                    t.originalid AS teacherid,
                    COUNT(*) AS total,
                    COUNT(p1.id) AS completed ';
        $from = 'FROM
                    {progressreview} p
                    JOIN {course} c ON c.id = p.courseid
                    JOIN {progressreview_teachers} t ON t.originalid = p.teacherid
                    JOIN {progressreview_session} s ON s.id = p.sessionid
                    LEFT JOIN '.$table.' p1 ON p1.reviewid = p.id ';
        if ($type == PROGRESSREVIEW_SUBJECT && $session->inductionreview) {
            $from .= 'AND p1.performancegrade IS NOT NULL ';
        } else {
            $from .= 'AND comments IS NOT NULL AND comments != ? ';
            $params = array($DB->sql_empty());
        }

        $where = 'WHERE
            p.sessionid = ?
            AND p.reviewtype = ? ';
        $params = array_merge($params, array($session->id, $type));

        if ($categoryid) {
            $where .= 'AND c.category = ? ';
        }
        $params[] = $categoryid;

        $group = 'GROUP BY courseid, teacherid ';
        $order = 'ORDER BY name, teacher ';

        return $DB->get_records_sql($select.$from.$where.$group.$order, $params);
    }

    public static function get_my_review_courses($sessionid) {
        global $DB, $USER;
        $courses = enrol_get_my_courses();
        $courseids = array_keys($courses);
        $params = array($sessionid, $USER->id, $USER->id);
        list($in_sql, $in_params) = $DB->get_in_or_equal($courseids);
        $select = 'SELECT pc.* ';
        $from = 'FROM {progressreview} p
            JOIN {progressreview_course} pc ON p.courseid = pc.originalid ';
        $where = 'WHERE p.sessionid = ?
            AND (p.teacherid = ? OR p.studentid = ?)
            AND p.courseid '.$in_sql.' ';
        $order = 'ORDER BY pc.shortname';
        return $DB->get_records_sql($select.$from.$where.$order, array_merge($params, $in_params));
    }

    public static function get_plugins_for_session($sessionid, $type = null) {
        global $DB;
        $params = array('sessionid' => $sessionid);
        if ($type) {
            array('reviewtype' => $type);
        }
        return $DB->get_records('progressreview_activeplugins', $params);
    }

    /**
     * Creates reviews for each student and teacher in the given course and session
     *
     * This function is potentially expensive so use it sparingly
     * Starts by checking if each student has a review for each teacher. If any are missing,
     * a progressreview object is instantiated to generate the record for the review.
     *
     * @param int $courseid
     * @param int $sessionid
     * @return true;
     **/
    public static function generate_reviews_for_course($courseid, $sessionid, $reviewtype = null) {
        global $DB;
        $coursecontext = get_context_instance(CONTEXT_COURSE, $courseid);
        $students = get_users_by_capability($coursecontext, 'moodle/local_progressreview:hasreview');
        $teachers = get_users_by_capability($coursecontext, 'moodle/local_progressreview:write');
        foreach ($students as $student) {
            foreach ($teachers as $teacher) {
                $params = array(
                    'studentid' => $student->id,
                    'sessionid' => $sessionid,
                    'courseid' => $courseid,
                    'teacherid' => $teacher->id
                );
                if (!$DB->record_exists('progressreview', $params)) {
                    if ($reviewtype) {
                        $params['type'] = $reviewtype;
                    } else {
                        $typeargs = array_combine(array('courseid', 'sessionid'), func_get_args());
                        if ($reviews = $DB->get_records('progressreview', $typeargs)) {
                            $params['type'] = current($reviews)->type;
                        } else {
                            $params['type'] = self::retrieve_type($courseid);
                        }
                    }
                    $rc = new ReflectionClass('progressreview');
                    $review = $rc->newInstanceArgs($params);
                }
            }
        }
        return true;
    }

    /**
     * Returns the correct type constant for this review's course
     *
     * This is currently specific to Taunton's College and should be changed
     *
     * @return
     * @todo Allow to be easily overridden for specific use cases.
     * @access private
     */
    private function retrieve_type($courseid) {
        global $DB;
        $course = $DB->get_record('course', array('id' => $courseid));
        if (strpos($course->shortname, '/') === false) {
            return PROGRESSREVIEW_TUTOR;
        } else {
            return PROGRESSREVIEW_SUBJECT;
        }
    } // end of member function retrieve_type

    /**
     * Snapshots current statistics for all subject reviews in the given session.
     *
     * Designed to be run by the cron job
     *
     * @param int sessionid
     * @access public
     */
    public static function snapshot_data($sessionid, $courseid = null) {
        $reviews = self::get_reviews($sessionid, null, $courseid, null, PROGRESSREVIEW_SUBJECT);
        foreach ($reviews as $review) {
            $review->get_plugin('subject')->snapshot();
        }
    } // end of member function snapshot_data_for_session

    public static function build_print_criteria($criteria, $field, $values) {
        if ($values) {
            if (empty($criteria)) {
                foreach ($values as $value) {
                    $criteria[] = new print_criterion($field, $value);
                }
            } else {
                $tempcriteria = array();
                foreach ($values as $value) {
                    foreach ($criteria as $criterion) {
                        $tempcriterion = clone($criterion);
                        $tempcriterion->$field = $value;
                        $tempcriteria[] = $tempcriterion;
                    }
                }
                $criteria = $tempcriteria;
            }
        }
        return $criteria;
    }

    public static function get_plugins_with_config() {
        global $CFG;

        $basedir = $CFG->dirroot.'/local/progressreview/plugins';
        $files = scandir($basedir);
        $pluginswithconfig = array();

        foreach ($files as $plugin) {
            if (substr($plugin, 0, 1) != '' && is_dir($basedir.'/'.$plugin)) {
                $configlib = $basedir.'/'.$plugin.'/config_form.php';
                if (is_file($configlib)) {
                    require_once($configlib);
                    if (class_exists('progressreview_'.$plugin.'_config_form')) {
                        $pluginswithconfig[] = $plugin;
                    }
                }
            }
        }
        return $pluginswithconfig;
    }

    public static function print_error_handler($strerror, $strlabel) {
        global $OUTPUT;
        $isError = false;
        if ($error = error_get_last()){
            switch($error['type']){
                case E_ERROR:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                    $isError = true;
                    break;
            }
        }

        if ($isError){
            $memory = strpos($error['message'], 'Allowed memory size') !== false;
            $exectime = strpos($error['message'], 'Maximum execution time') !== false;
            if ($memory || $exectime) {
                echo $strerror.'<br />';
                $params = array(
                    'sessions' => $_POST['sessions'],
                    'students' => $_POST['students'],
                    'courses' => $_POST['courses'],
                    'teachers' => $_POST['teachers'],
                    'groupby' => $_POST['groupby'],
                    'generate' => true,
                    'disablememlimit' => true,
                    'sesskey' => sesskey()
                );
                $url = new moodle_url('/local/progressreview/print.php', $params);
                echo html_writer::link($url, $strlabel);
            } else {
                echo "Script execution halted ({$error['message']} on line {$error['line']} of {$error['file']})";
            }
        }
    }

    public static function register_print_error_handler() {
        ini_set('display_errors', 0);
        $limit = ini_get('memory_limit');
        $strerror = get_string('outofmemory', 'local_progressreview', $limit);
        $strlabel = get_string('disablememlimit', 'local_progressreview');
        register_shutdown_function('progressreview_controller::print_error_handler',
                                   $strerror,
                                   $strlabel);
    }

    public static function xhr_response($e) {
        $response = (object)array(
            'errortype' => get_class($e),
            'message' => $e->getMessage()
        );
        if ($e instanceof dml_write_exception) {
            $response->message .= ' '.$e->error;
        }
        $response->message .= ' '.get_string('rednotsaved', 'local_progressreview');
        die(json_encode($response));
    }
} // end of progressreview_controller

class progressreview_cache {

    public static $sessions = array();
    public static $students = array();
    public static $courses = array();
    public static $teachers = array();
    public static $scales = array();

}

abstract class progressreview_plugin {

    /**
     * The name of the plugin, the same as the folder it lives in
     */
    protected $name;

    /**
     * The type of progressreview plugin this is
     * either PROGRESSREVIEW_SUBJECT or PROGRESSREVIEW_TUTOR
     */
    static public $type;

    /**
     * The progressreview object for the review this instance
     * belongs to
     */
    protected $progressreview;

    /**
     * Array of property name that will be handled by the update()
     * method, all others will be ignored.
     */
    protected $valid_properties;

    public function __construct(&$review) {
        $this->progressreview = $review;
        $this->retrieve_review();
    }

    public function get_name() {
        return $this->name;
    }


    public function validate($data) {
        return true;
    }


    protected function filter_properties($data) {
        if (is_object($data)) {
            $data = (array)$data;
        }

        foreach ($data as $field => $datum) {
            if(in_array($field, $this->valid_properties)) {
                $this->$field = $datum;
            } else {
                $data[$field] = false;
            }
        }

        $data = (object)array_filter($data, function($datum) {
            return $datum !== false;
        });
        return $data;
    }

    protected function update_timestamp() {
        global $DB;
        if (!$DB->set_field('progressreview', 'datemodified', time(), array('id' => $this->progressreview->id))) {
            throw new progressreview_autosave_exception('Timestamp Update Failed');
        }
    }

    /**
     * Updates the object's properties and the record for this plugin instance with the given data
     */
    public function update($data) {
        global $DB;

        $data = $this->filter_properties($data);

        if (empty($data)) {
            throw new progressreview_invalidfield_exception('Invalid Field Name');
        }

        $params = array('id' => $this->progressreview->id);
        if (!empty($this->id)) {
            $data->id = $this->id;
            $this->update_timestamp();
            return $DB->update_record('progressreview_'.$this->name, $data);
        } else {
            $this->update_timestamp();
            $this->id = $DB->insert_record('progressreview_'.$this->name, $data);
            return $this->id;
        }
    } // end of member function update

    public function require_js() {}

    public function autosave($field, $value) {
        try {
            $data = array($field => $value);
            $this->validate($data);
            $success = $this->update($data);
        } catch (progressreview_invalidfield_exception $e) {
            throw $e;
        } catch (dml_write_exception $e) {
            throw $e;
        }
        if (!$success) {
            throw new progressreview_autosave_exception('Autosave Failed');
        }
    }


    /**
     * Retrieves this plugin's data for the current review and stores in the the object
     */
    abstract protected function retrieve_review();

    /**
     * Returns an object containing the data required for rendering this plugin's widgets
     */
    abstract function get_review();

    /**
     * Deletes all data for this plugin associated with the current review.
     */
    abstract function delete();

    /**
     * Processes the data for this plugin returned from the form
     */
    abstract function process_form_fields($data);

}

/**
 * Class to define template for tutor review plugins
 *
 * Tutor plugins need a couple of extra functions for adding fields and data
 * to the mform.
 */
abstract class progressreview_plugin_tutor extends progressreview_plugin {

    /**
     * Adds the fields this plugin needs to the review form
     */
    abstract public function add_form_fields($mform);

    /**
     * Add data for fields to $data
     */
    abstract public function add_form_data($data);

}

/**
 * Class to define template for subject review plugins
 *
 * Subject review plugins need a couple of extra functions as they don't use mform
 */
abstract class progressreview_plugin_subject extends progressreview_plugin {

    /**
     * Cleans data posted from the plugin's fields
     */
    abstract function clean_params($post);

    /**
     * Returns an array of html_table_rows containing form fields to be added to the form table
     */
    abstract function add_form_rows();

    /**
     * Returns an array of html_table_rows to be added to report tables
     */
    abstract function add_table_rows($groupby, $showincomplete = true);

}

class print_criterion {
    public $sessionid;
    public $studentid;
    public $courseid;
    public $teacherid;
    public $type;

    public function __construct($field, $value) {
        $this->sessionid = null;
        $this->studentid = null;
        $this->courseid = null;
        $this->teacherid = null;
        $this->$field = $value;
    }
}

if (class_exists('user_selector_base')) {

    abstract class progressreview_print_selector extends user_selector_base {

        protected $filters;

        public function __construct($name, $options = array(), $filters = array()) {
            $this->filters = array(
                'sessionid' => array(),
                'courseid' => array(),
                'studentid' => array(),
                'teacherid' => array()
            );
            array_filter($filters, function($filter) use (&$filters) {
                if (in_array(key($filters), array_keys($this->filters))) {
                    $return = true;
                } else {
                    $return = false;
                }
                next($filters);
                return $return;
            });
            $this->filters = $filters;
            return parent::__construct($name, $options);
        }

        protected function get_options() {
            $options = parent::get_options();
            $options['file'] = 'local/progressreview/lib.php';
            return $options;
        }

        protected function where_clause($conditions) {
            if (!empty($conditions)) {
                $where = 'WHERE 1 ';
                foreach ($conditions as $condition) {
                    if (!empty($condition)) {
                        $where = 'AND '.$condition.' ';
                    }
                }
            } else {
                $where = '';
            }
            return $where;
        }

        protected function add_search($search, $field) {
            global $DB;
            $sql = '';
            $param = '';
            if (!empty($search)) {
                $sql = $DB->sql_like($field, '?');
                $param = '%'.$search.'%';
            }
            return array($sql, array($param));
        }

        protected function add_filters($exclude = '') {
            global $DB;
            $sql = '';
            $params = array();
            foreach ($this->filters as $field => $ids) {
                if ($field != $exclude && !empty($ids)) {
                    list($insql, $inparams) = $DB->get_in_or_equal($ids);
                    $sql = $field.' '.$insql.' ';
                    $params = array_merge($params, $inparams);
                }
            }
            return array($sql, $params);
        }

    }

    class progressreview_session_selector extends progressreview_print_selector {
        public function find_users($search = '') {
            global $DB;
            $select = 'SELECT DISTINCT
                s.id AS id,
                s.name AS lastname,
                "" AS firstname,
                "" AS email ';
            $from = 'FROM {progressreview_session} s JOIN {progressreview} p ON s.id = p.sessionid ';
            $params = array();
            $conditions = array();
            $order = 'ORDER BY s.deadline_tutor DESC';

            list($conditions[], $searchparams) = $this->add_search($search, 'name');
            list($conditions[], $filterparams) = $this->add_filters('sessionid');
            $params = array_merge($params, $searchparams, $filterparams);
            $where = $this->where_clause($conditions);

            $options = $DB->get_records_sql($select.$from.$where.$order, $params);
            $optgroupname = get_string('sessions', 'local_progressreview');
            return array($optgroupname => $options);
        }
    }
    class progressreview_student_selector extends progressreview_print_selector {
        public function find_users($search) {
            global $DB;
            $select = 'SELECT DISTINCT u.id , u.firstname, u.lastname, u.email ';
            $from = 'FROM {user} u JOIN {progressreview} p ON u.id = p.studentid ';
            $params = array();
            $conditions = array();
            $order = 'ORDER BY lastname ASC, firstname ASC';

            $fullname = $DB->sql_concat_join('" "', array('firstname', 'lastname'));
            list($conditions[], $searchparams) = $this->add_search($search, $fullname);
            list($conditions[], $filterparams) = $this->add_filters('studentid');
            $params = array_merge($params, $searchparams, $filterparams);
            $where = $this->where_clause($conditions);

            $options = $DB->get_records_sql($select.$from.$where.$order, $params);
            $optgroupname = get_string('students', 'local_progressreview');

            return array($optgroupname => $options);
        }
    }

    class progressreview_course_selector extends progressreview_print_selector {
        public function find_users($search) {
            global $DB;
            $select = 'SELECT DISTINCT
                c.id,
                c.shortname AS lastname,
                "" AS firstname,
                c.fullname AS email ';
            $from = 'FROM {course} c JOIN {progressreview} p ON c.id = p.courseid ';
            $params = array();
            $conditions = array();
            $order = 'ORDER BY lastname ASC';

            $fullname = $DB->sql_concat_join('" "', array('c.shortname', 'c.fullname'));
            list($conditions[], $searchparams) = $this->add_search($search, $fullname);
            list($conditions[], $filterparams) = $this->add_filters('courseid');
            $params = array_merge($params, $searchparams, $filterparams);
            $where = $this->where_clause($conditions);

            $options = $DB->get_records_sql($select.$from.$where.$order, $params);
            $optgroupname = get_string('courses', 'local_progressreview');

            return array($optgroupname => $options);
        }
    }

    class progressreview_teacher_selector extends progressreview_print_selector {
        public function find_users($search) {
            global $DB;
            $select = 'SELECT DISTINCT u.id , u.firstname, u.lastname, u.email ';
            $from = 'FROM {user} u JOIN {progressreview} p ON u.id = p.teacherid ';
            $params = array();
            $conditions = array();
            $order = 'ORDER BY lastname ASC, firstname ASC';

            $fullname = $DB->sql_concat_join('" "', array('firstname', 'lastname'));
            list($conditions[], $searchparams) = $this->add_search($search, $fullname);
            list($conditions[], $filterparams) = $this->add_filters('teacherid');
            $params = array_merge($params, $searchparams, $filterparams);
            $where = $this->where_clause($conditions);

            $options = $DB->get_records_sql($select.$from.$where.$order, $params);
            $optgroupname = get_string('teachers', 'local_progressreview');

            return array($optgroupname => $options);
        }
    }
}

class pdf_writer {

    static $pdf;
    static $debug = '';

    private static function parse_colour($color) {
        if (strlen($color) != 6) {
            return false;
        }
        $red = hexdec(substr($color, 0, 2));
        $green = hexdec(substr($color, 2, 2));
        $blue = hexdec(substr($color, 4, 2));
        return array('red' => $red, 'green' => $green, 'blue' => $blue);
    }

    /**
     * Initialise the PDF, create an inital page and set an inital font
     */
    public static function init($size = 'A4', $orientation = 'P', stdClass $font = null) {
        global $CFG;
        self::$pdf = new fpdf_table($orientation, 'pt', $size);
        self::$pdf->AddPage();
        if (empty($font)) {
            $font = (object)array('family' => 'Helvetica', 'size' => 12);
        }
        self::change_font($font);

        return self::$pdf;
    }

    /**
     * Sets font family, size, decoration and colour as specified
     */
    public static function change_font(stdClass $font) {
        $family = isset($font->family) ? $font->family : '';
        $size = isset($font->size) ? $font->size : '';
        $decoration = isset($font->decoration) ? $font->decoration : '';
        $colour = isset($font->colour) ? self::parse_colour($font->colour) : '';

        if (!empty($colour)) {
            self::$pdf->SetTextColor($color['red'], $colour['green'], $colour['blue']);
        }

        self::$pdf->SetFont($family, $decoration, $size);
        return self::$pdf;
    }

    public static function change_line_style(stdClass $style) {
        $colour = isset($font->colour) ? self::parse_colour($style->colour) : '';
        $width = isset($font->width) ? $style->width : '';
        if (!empty($colour)) {
            self::$pdf->SetDrawColor($colour['red'], $colour['green'], $colour['blue']);
        }
        if (!empty($width)) {
            self::$pdf->SetLineWidth($width);
        }
    }

    /**
     * Arbitrary cells containing text, optionally with borders and backgrounds
     *
     * @param $text The text to display, can include line breaks with <br /> or \n
     * @param $options an associative array of options, in the format 'option' => $value
     *  Possible options:
     *  font - object defining any of family, size and decoration, and colour as properties.
     *  fill - HTML-style hex string of RGB for background colour.
     *  width - the cell width, defaults to 0 (whole page)
     *  height - the cell height, defaults to 0
     *  border - 0 for no border, 1 for all borders, combination of LTRB for Left, Top, Right,
     *      Bottom, defaults to 0
     *  align - L for left, C for center, R for right, defaults to L
     *
     */
    public static function cell($text, $options = array()) {
        $width = array_key_exists('width', $options) ? $options['width'] : '';
        $height = array_key_exists('height', $options) ? $options['height'] : self::$pdf->FontSize*1.2;
        $border = array_key_exists('border', $options) ? $options['border'] : '';
        $fill = array_key_exists('fill', $options) ? $options['fill'] : 0;
        $breakafter = array_key_exists('breakafter', $options) ? $options['breakafter'] : 0;
        $align = array_key_exists('align', $options) ? $options['align'] : 'L';
        if (array_key_exists('font', $options) && !empty($options['font'])) {
            self::change_font($options['font']);
        }
        if (array_key_exists('fill', $options)) {
            $fill = 1;
            $colour = self::parse_colour($options['fill']);
            self::$pdf->SetFillColor($colour['red'], $colour['green'], $colour['blue']);
        }

        if (empty($border)) {
            $borders = 0;
        } else {
            $style = new stdClass;
            $borders = $border->borders;
            $style->width = isset($border->width) ? $border->width : null;
            $style->colour = isset($border->colour) ? $border->colour : null;
            self::$pdf->change_line_style($style);
        }

        $text = str_replace('<br />', "\n", $text);
        if (strpos($text, "\n") === false) {
            return self::$pdf->Cell($width, $height, $text, $borders, $breakafter, $align, $fill);
        } else {
            return self::$pdf->MultiCell($width, $height, $text, $borders, $align, $fill);
        }
    }

    /**
     * Adds a cell with no line break following (like an HTML span)
     */
    public static function span($text, $options = array()) {
        $options['breakafter'] = 0;
        return self::cell($text, $options);
    }

    /**
     * Adds a cell with a line break following (line an HTML div)
     */
    public static function div($text, $options = array()) {
        $options['breakafter'] = 1;
        return self::cell($text, $options);
    }


    public static function link($url, $text, $height = 10) {
        if (typeof($url) == 'moodle_url') {
            $url = $url->out();
        }
        self::$pdf->Write($height, $text, $url);
        return self::$pdf;
    }

    public static function image($path,
                                 $x = null,
                                 $y = null,
                                 $width = 0,
                                 $height = 0,
                                 $format = null,
                                 $url = '') {
        if (get_class($url) == 'moodle_url') {
            $url = $url->out();
        }
        if (get_class($path) == 'moodule_url') {
            $path = $path->out();
        }
        self::$pdf->Image($path, $x, $y, $width, $height, $format, $url);
        return self::$pdf;
    }

    public static function alist(array $items, $options = array(), $ordered = false) {
       //Save x
        $bak_x = $pdf->x;
        if ($ordered) {
            $bullet = '•';
        } else {
            $bullet = 1;
        }

        for ($i=0; $i<sizeof($items); $i++) {
            //Get bullet width including margin
            $blt_width = $this->GetStringWidth($bullet . $options['margin'])+$this->cMargin*2;

            // SetX
            self::$pdf->SetX($bak_x);

            //Output indent
            if ($options['indent'] > 0) {
                self::$pdf->Cell($options['indent']);
            }

            //Output bullet
            self::$pdf->Cell($blt_width,$h,$bullet . $options['margin'],0,'',$fill);

            //Output text
            self::$pdf->MultiCell($w-$blt_width,$h,$items[$i],$border,$align,$fill);

            //Insert a spacer between items if not the last item
            if ($i != sizeof($items)-1) {
                self::$pdf->Ln($options['spacer']);
            }

            //Increment bullet if it's a number
            if (is_numeric($bullet)) {
                $bullet++;
            }
        }

        //Restore x
        self::$pdf->x = $bak_x;
        return $pdf;
    }

    public static function table(html_table $table) {
        $default_header_type = array(
            'WIDTH' => 6, //cell width
            'T_COLOR' => array(0,0,0), //text color
            'T_SIZE' => 8, //font size
            'T_FONT' => 'Arial', //font family
            'T_ALIGN' => 'C', //horizontal alignment, possible values: LRC (left, right, center)
            'V_ALIGN' => 'M', //vertical alignment, possible values: TMB(top, middle, bottom)
            'T_TYPE' => 'B', //font type
            'LN_SIZE' => 10, //line size for one row
            'BG_COLOR' => array(255, 255, 255), //background color
            'BRD_COLOR' => array(0,0,0), //border color
            'BRD_SIZE' => 0.2, //border size
            'BRD_TYPE' => '1', //border type, can be: 0, 1 or a combination of: "LRTB"
            'TEXT' => '', //text
        );

        $default_data_type = array(
            'T_COLOR' => array(0,0,0), //text color
            'T_SIZE' => 8, //font size
            'T_FONT' => 'Arial', //font family
            'T_ALIGN' => 'L', //horizontal alignment, possible values: LRC (left, right, center)
            'V_ALIGN' => 'M', //vertical alignment, possible values: TMB(top, middle, bottom)
            'T_TYPE' => '', //font type
            'LN_SIZE' => 10, //line size for one row
            'BG_COLOR' => array(255,255,255), //background color
            'BRD_COLOR' => array(0,0,0), //border color
            'BRD_SIZE' => 0.1, //border size
            'BRD_TYPE' => 'LR', //border type, can be: 0, 1 or a combination of: "LRTB"
        );

        $table_type = array(
            'TB_ALIGN' => 'L', //table align on page
            'L_MARGIN' => 25, //space to the left margin
            'BRD_COLOR' => array(0,0,0), //border color
            'BRD_SIZE' => '0.3', //border size
        );

        $head = $table->head;
        $data = $table->data;

        self::$pdf->tbInitialize(count($data[0]), true, true);
        self::$pdf->tbSetTableType($table_type);

        // Colors, line width and bold font
        self::$pdf->SetTextColor(0);
        self::$pdf->SetLineWidth(.3);
        self::$pdf->SetFont('');

        // prepare table data and populate missing properties with reasonable defaults
        if (!empty($table->align)) {
            foreach ($table->align as $key => $aa) {
                if ($aa) {
                    $table->align[$key] = $aa;  // Fix for RTL languages
                } else {
                    $table->align[$key] = null;
                }
            }
        }
        if (!empty($table->size)) {
            foreach ($table->size as $key => $ss) {
                if ($ss) {
                    $table->size[$key] = $ss;
                } else {
                    $table->size[$key] = null;
                }
            }
        }
        if (!empty($table->head)) {
            foreach ($table->head as $key => $val) {
                if (!isset($table->align[$key])) {
                    $table->align[$key] = null;
                }
                if (!isset($table->size[$key])) {
                    $table->size[$key] = null;
                }
                if (!isset($table->wrap[$key])) {
                    $table->wrap[$key] = null;
                }

            }
        }

        // explicitly assigned properties override those defined via $table->attributes
        $attributes = array_merge($table->attributes, array(
                'width'         => $table->width,
                'cellpadding'   => $table->cellpadding,
                'cellspacing'   => $table->cellspacing
            ));

        $countcols = 0;

        if (!empty($table->head)) {
            $countcols = count($table->head);

            $keys = array_keys($table->head);
            $lastkey = end($keys);

            $headers = array();
            foreach ($table->head as $key => $heading) {
                $headers[$key] = $default_header_type;
                // Convert plain string headings into html_table_cell objects
                if (!($heading instanceof html_table_cell)) {
                    $headingtext = $heading;
                    $heading = new html_table_cell();
                    $heading->text = $headingtext;
                    $heading->header = true;
                }

                if ($heading->header !== false) {
                    $heading->header = true;
                }

                if ($heading->header && empty($heading->scope)) {
                    $heading->scope = 'col';
                }

                if (isset($table->headspan[$key]) && $table->headspan[$key] > 1) {
                    $heading->colspan = $table->headspan[$key];
                    $countcols += $table->headspan[$key] - 1;
                }

                if (isset($table->size[$key])) {
                    $headers[$key]['WIDTH'] = $table->size[$key];
                }
                if (isset($table->align[$key])) {
                    $headers[$key]['T_ALIGN'] = $table->align[$key];
                }
                $headers[$key]['TEXT'] = $heading->text;
                $headers[$key]['COLSPAN'] = $heading->colspan;
            }
            self::$pdf->tbSetHeaderType(array($headers), true);
            self::$pdf->tbDrawHeader();
        }

        $fill = true;

        if (!empty($table->data)) {
            $oddeven    = 1;
            $keys       = array_keys($table->data);
            $lastrowkey = end($keys);

            foreach ($table->data as $key => $row) {
                self::$pdf->SetFont('', '');

                if ($fill) {
                    $rowfill = array(224,235,225);
                } else {
                    $rowfill = array(255,255,255);
                }

                // Convert array rows to html_table_rows and cell strings to html_table_cell objects
                if (!($row instanceof html_table_row)) {
                    $newrow = new html_table_row();

                    foreach ($row as $item) {
                        if (!($item instanceof html_table_cell)) {
                            $cell = new html_table_cell();
                            $cell->text = $item;
                            $newrow->cells[] = $cell;
                        } else {
                            $newrow->cells[] = $item;
                        }
                    }
                    $row = $newrow;
                }

                // fpdf_table requires an equal number of cells in each row, regardless of colspan
                foreach ($row->cells as $key => $cell) {
                    if ($cell instanceof html_table_cell && $cell->colspan > 1) {
                        $spancount = $cell->colspan;
                        $firstcells = array_slice($row->cells, 0, $key+1);
                        $lastcells = array_slice($row->cells, $key);
                        $extracells = array();
                        for ($i = $spancount; $i > 1; $i--) {
                            $extracells[] = '';
                        }
                        $row->cells = array_merge($firstcells, $extracells, $lastcells);
                    }
                }

                $cells = array();
                foreach ($row->cells as $key => $cell) {
                    $cells[$key] = $default_data_type;
                    $cells[$key]['BG_COLOR'] = $rowfill;

                    if (!($cell instanceof html_table_cell)) {
                        $mycell = new html_table_cell();
                        $mycell->text = $cell;
                        $cell = $mycell;
                    }

                    if ($cell->header === true) {
                        $cells[$key]['T_TYPE'] = 'B';
                    }

                    $cells[$key]['COLSPAN'] = $cell->colspan;
                    $cells[$key]['TEXT'] = $cell->text;

                }

                self::$pdf->tbSetDataType($cells);
                self::$pdf->tbDrawData($cells);
                $fill = !$fill;
            }
        }
        //output the table data to the pdf
        self::$pdf->tbOuputData();

        //draw the Table Border
        self::$pdf->tbDrawBorder();
        return self::$pdf;
    }

    /**
     * Start a new page
     */
    public static function page_break() {
        self::$pdf->AddPage();
        return self::$pdf;
    }
}

class progressreview_invalidfield_exception extends Exception {};
class progressreview_invalidvalue_exception extends Exception {};
class progressreview_autosave_exception extends Exception {};
class progressreview_nouser_exception extends Exception {};
