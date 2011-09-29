<?php

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

        $this->session = $DB->get_record('progressreview_session', array('id' => $sessionid));
        $this->teacher = $this->retrieve_teacher($teacherid);
        $this->student = $DB->get_record('user', array('id' => $studentid));
        $this->course = $this->retrieve_course($courseid);

        $this->session->scale_behaviour = explode(',', $this->session->scale_behaviour);
        $this->session->scale_homework = explode(',', $this->session->scale_homework);
        $this->session->scale_effort = explode(',', $this->session->scale_effort);

        $params = array('studentid' => $studentid, 'courseid' => $courseid, 'teacherid' => $teacherid, 'sessionid' => $sessionid);
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

    public function get_plugin($name) {
        return $this->plugins[$name];
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
        return $DB->update_record('progressreview', (object)array('id' => $this->id, 'teacherid' => $this->teacher->id));
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
        $activeplugins = $DB->get_records('progressreview_activeplugins', array('sessionid' => $this->session->id, 'reviewtype' => $this->type));

        foreach ($activeplugins as $activeplugin) {
                require_once($CFG->dirroot.'/local/progressreview/plugins/'.$activeplugin->plugin.'/lib.php');
                $classname = 'progressreview_'.$activeplugin->plugin;
        	$this->plugins[$activeplugin->plugin] = new $classname($this);
        }
        return true;
    } // end of member function get_plugins


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

        if (!$teacher = $DB->get_record('progressreview_teachers', array('originalid' => $id))) {
        	$teacher = $DB->get_record('user', array('id' => $id), 'id, firstname, lastname');
        	$teacher->originalid = $teacher->id;
        	unset($teacher->id);
        	$teacher->id = $DB->insert_record('progressreview_teachers', $teacher);
        }

        return $teacher;
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

        if (!$course = $DB->get_record('progressreview_course', array('originalid' => $id))) {
        	$course = $DB->get_record('course', array('id' => $id), 'id, shortname, fullname');
        	$course->originalid = $course->id;
        	unset($course->id);
        	$course->id = $DB->insert_record('progressreview_course', $course);
        }

        return $course;
    } // end of member function retrieve_course

} // end of progressreview

/**
 * class progressreview_controller
 *
 */
class progressreview_controller {

    /**
     * Returns an array of records for each session in the database
     *
     * @return
     * @access public
     */
    public static function get_sessions() {
        global $DB;
        return $DB->get_records('progressreview_session', array(), 'deadline_tutor DESC');
    } // end of member function get_sessions

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
    public static function get_reviews($sessionid = null,  $studentid = null,  $courseid = null,  $teacherid = null,  $type = PROGRESSREVIEW_SUBJECT) {
        if (!$sessionid && !$studentid && !$courseid && !$teacherid) {
            throw new coding_exception('get_reviews() must be called with at least one ID parameter');
        }
        if (!in_array($type, array(PROGRESSREVIEW_SUBJECT, PROGRESSREVIEW_TUTOR))) {
            throw new coding_exception('$type must be set to PROGRESSREVEW_SUBJECT or PROGRESSREVIEW_TUTOR');
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
                $reviews[$r->id] = new progressreview($r->studentid, $r->sessionid, $r->courseid, $r->teacherid);
            }
        }
        return $reviews;

    } // end of member function get_reviews


    public static function get_course_summaries($sessionid, $type) {
        if (!in_array($type, array(PROGRESSREVIEW_SUBJECT, PROGRESSREVIEW_TUTOR))) {
            throw new coding_exception('$type must be set to PROGRESSREVEW_SUBJECT or PROGRESSREVIEW_TUTOR');
        }

        global $DB;

        if ($type == PROGRESSREVIEW_SUBJECT) {
            $table = '{progressreview_subject}';
        } else {
            $table = '{progressreview_tutor}';
        }

        $total_select = 'SELECT COUNT(*) ';
        $total_from = 'FROM '.$table.' ps1 ';
        $total_where = 'WHERE p.id = ps1.reviewid';
        $total_sql = $total_select.$total_from.$total_where;

        $session = $DB->get_record('progressreview_session', array('id' => $sessionid));
        $completed_from = 'FROM '.$table.' ps2 ';
            $completed_where = 'WHERE p.id = ps2.reviewid
             AND p.datemodified IS NOT NULL
             AND LENGTH(ps2.comments) > 0 ';
        if ($type == PROGRESSREVIEW_SUBJECT && $session->inductionreview) {
            $completed_where = 'WHERE p.datemodified IS NOT NULL
                AND ps2.performancegrade IS NOT NULL';
        }
        $completed_sql = $total_select.$completed_from.$completed_where;

        $teacher_concat = $DB->sql_concat('t.firstname', '" "', 't.lastname');
        $select = 'SELECT p.id, c.id as courseid,
            c.fullname AS name,
            '.$teacher_concat.' AS teacher,
            ('.$total_sql.') AS total,
            ('.$completed_sql.') AS completed ';
        $from = 'FROM {progressreview} p
            JOIN {progressreview_course} c ON p.courseid = c.originalid
            JOIN {progressreview_teachers} t ON p.teacherid = t.originalid ';
        $where = 'WHERE p.sessionid = ? ';
        $group = 'GROUP BY courseid, teacher ';
        $order = 'ORDER BY c.fullname, teacher';
        $params = array($sessionid);

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
        $students = get_users_by_capability($coursecontext, 'moodle/local_progressreview:viewown');
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
} // end of progressreview_controller

