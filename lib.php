<?php

namespace progressreview;

/**
 * class progressreview
 * Controller for all operations on progressreview data
 *
 * Given a student, session, course and teacher, this will initialise an interface
 * to a progressreview via all associated plugins.
 */
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
    public $plugins;

    /**
     * The type of review this is - PROGRESSREVIEW_SUBJECT or PROGRESSREVIEW_TUTOR
     * @access public
     */
    public $type;

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
    public function __construct( $studentid,  $sessionid,  $courseid,  $teacherid ) {
        global $DB;

        $this->session = $DB->get_record('progressreview_session', array('id' => 'sessionid'));
        $this->teacher = $this->retrieve_teacher($teacherid);
        $this->student = $DB->get_record('user', array('id' => $studentid));
        $this->course = $this->retrieve_course($courseid);

        $params = array('studentid' => $studentid, 'courseid' => $courseid, 'teacherid' => $teacherid, 'sessionid' => $sessionid);
        if ($review = $DB->get_record('progressreview', $params) {
        	$this->id = $review->id;
        	$this->type = $review->reviewtype;
        } else {
        	$review = (object)$params;
        	$review->reviewtype = $this->type = $this->retrieve_type();
        	$this->id = $DB->insert_record('progressreview', $review);
        }

        $this->get_plugins();
        return true;
    } // end of member function __construct

    /**
     * Transfers this review to allow a different teacher to write it
     *
     * @param stdClass teacher The new teacher's record from the user table

     * @return bool indicating success
     * @access public
     */
    public function transfer_to_teacher( $teacher ) {
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
    private function get_plugins( ) {
        global $DB;

        $activeplugins = $DB->get_records('progressreview_activeplugins', array('sessionid' => $this->session->id, 'reviewtype' => $this->type));

        foreach ($activeplugins as $activeplugin) {
        	$this->plugins[$activeplugin->name] = new {$activeplugin->name};
        }
        return true;
    } // end of member function get_plugins

    /**
     * Returns the correct type constant for this review's course
     *
     * @return
     * @access private
     */
    private function retrieve_type( ) {
    } // end of member function retrieve_type

    /**
     * Returns the record in progressreview_teacher for the given user's id, creating
     * the record if required.
     *
     * @param int id The ID of the teacher's user record in user

     * @return object The teacher's record from progressreview_teacher
     * @access private
     */
    private function retrieve_teacher( $id ) {
        global $DB;

        if (!$teacher = $DB->get_record('progressreview_teacher', array('originalid' => $id)) {
        	$teacher = $DB->get_record('user', array('id' => $id), 'id, firstname, lastname');
        	$teacher->originalid = $teacher->id;
        	unset($teacher->id);
        	$teacher->id = $DB->insert_record('progressreview_teacher', $teacher);
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
    private function retrieve_course( $id ) {
        global $DB;

        if (!$course = $DB->get_record('progressreview_course', array('originalid' => $id)) {
        	$course = $DB->get_record('course', array('id', $id), 'id, shortname, fullname');
        	$course->originalid = $course->id;
        	unset($course->id);
        	$course->id = $DB->insert_record('progressreview_course', $course);
        }

        return $course;
    } // end of member function retrieve_course



} // end of progressreview
