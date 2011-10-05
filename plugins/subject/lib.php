<?php

/**
 * class progressreview_subject
 * Interface to core data for Subject reviews
 */
abstract class progressreview_subject_template {
    /*** Attributes: ***/

    /**
     * The ID of the review's record in the progressreview_sub table
     * @access public
     */
    public $id;

    /**
     * A reference to the progressreview object for the review that this subject review
     * belongs to.
     * @access private
     */
    protected $progressreview;

    /**
     * The comments entered for this review
     * @access private
     */
    private $comments;

    /**
     * The student's behaviour, as an index on the scale defined in the review's
     * session
     * @access private
     */
    private $behaviour;

    /**
     * The student's effort, as an index on the scale defined in the review's session
     * @access private
     */
    private $effort;

    /**
     * The student's homework standard, as an index on the scale defined in the
     * review's session
     * @access private
     */
    private $homeworkstandard;

    /**
     * The student's homework completion to date
     *
     * Initially auto filled from the gradebook, can be overridden.
     * @access private
     */
    private $homeworkdone;

    /**
     * The student's homework completion to date
     *
     * Initially auto filled from the gradebook, can be overridden.
     * @access private
     */
    private $homeworktotal;

    /**
     * The student's attendance percentage
     *
     * Calculated by the retrieve_attendance function
     * @access private
     */
    private $attendance;

    /**
     * The student's punctuality percentage
     *
     * Calculated by the retrieve_punctuality function
     * @access private
     */
    private $punctuality;

    /**
     * The scaleid used for this course's target grades
     *
     * Determined by retrieve_scale, but can be overridden.
     * @access private
     */
    private $scaleid;

    /**
     * This scale used for this course's target grades.
     *
     * @access private
     */
    private $scale;

    /**
     * The minimum target grade for the student
     *
     * @access private;
     */
    private $minimumgrade;

    /**
     * The student's target grade
     *
     * If the targetgrades plugin is available, this will be set to the calculated
     * value, to be edited.
     * @access private
     */
    private $targetgrade;

    /**
     * The student's current performance grade.
     * @access private
     */
    private $performancegrade;


    /**
     * Initialises the object by storing a reference to the progressreview object and
     * calling retrieve_review()
     *
     * @param progressreview review A reference to the progressreview object that this plugin belongs to.

     * @return
     * @access public
     */
    public function __construct(&$review) {
        $this->progressreview = $review;
        $this->retrieve_review();
    } // end of member function __construct

    /**
     * Returns an object containing the current review statistics and comments.
     *
     * @return
     * @access public
     */
    public function get_review() {
        return (object)array(
            'id' => $this->id,
            'progressreview' => $this->progressreview,
            'comments' => $this->comments,
            'behaviour' => $this->behaviour,
            'effort' => $this->effort,
            'homeworkstandard' => $this->homeworkstandard,
            'homeworkdone' => $this->homeworkdone,
            'homeworktotal' => $this->homeworktotal,
            'attendance' => $this->attendance,
            'punctuality' => $this->punctuality,
            'scale' => $this->scale,
            'minimumgrade' => $this->minimumgrade,
            'targetgrade' => $this->targetgrade,
            'performancegrade' => $this->performancegrade
        );
    } // end of member function get_review

    /**
     * Updates the attributes with the passed values and saves the values to the
     * database.
     *
     * @param stdClass|array $data
     * @access public
     */
    public function update($data) {
        global $DB;
        $valid_properties = array(
            'reviewid',
            'comments',
            'behaviour',
            'effort',
            'homeworkstandard',
            'homeworkdone',
            'homeworktotal',
            'attendance',
            'punctuality',
            'scaleid',
            'minimumgrade',
            'targetgrade',
            'performancegrade'
        );

        if (is_object($data)) {
            $data = (array)$data;
        }

        foreach ($data as $field => $datum) {
            if(in_array($field, $valid_properties)) {
                $this->$field = $datum;
            } else {
                $data[$field] = false;
            }
        }

        $data['minimumgrade'] = false;

        $data = (object)array_filter($data, function($datum) {
            return $datum !== false;
        });
        if (!empty($this->id)) {
            $data->id = $this->id;
            $DB->update_record('progressreview_subject', $data);
            $DB->set_field('progressreview', 'datecreated', time(), array('id' => $this->progressreview->id));
        } else {
            $this->id = $DB->insert_record('progressreview_subject', $data);
            $DB->set_field('progressreview', 'datemodified', time(), array('id' => $this->progressreview->id));
        }
    } // end of member function update



    /**
     * Generates a basic review containing any statistics that can be determined from
     * the database.
     *
     * @return
     * @access private
     */
    private function skeleton_review() {
        $skeleton = array();
        $skeleton['reviewid'] = $this->progressreview->id;
        $homework = $this->retrieve_homework();
        $skeleton['homeworkdone'] = $homework->done;
        $skeleton['homeworktotal'] = $homework->total;
        $attendance = $this->retrieve_attendance();
        $skeleton['attendance'] = $attendance->attendance;
        if (100 < $skeleton['attendance'] || $skeleton['attendance'] < 0) {
            throw new coding_exception('retrieve_attandance implemented incorrectly. It must return a number between 0 and 100 inclusive');
        }
        $skeleton['punctuality'] = $attendance->punctuality;
        if (100 < $skeleton['punctuality'] || $skeleton['punctuality'] < 0) {
            throw new coding_exception('retrieve_punctuality implemented incorrectly. It must return a number between 0 and 100 inclusive');
        }
        $skeleton['scaleid'] = $this->retrieve_scaleid();
        $targetgrades = $this->retrieve_targetgrades();
        $skeleton['minimumgrade'] = $targetgrades->min;
        $skeleton['targetgrade'] = $targetgrades->target;
        $skeleton['performancegrade'] = $targetgrades->cpg;
        $this->update($skeleton);

    } // end of member function skeleton_review

    /**
     * Retrieves the current review from the database, or generates one if required.
     *
     * @return
     * @access private
     */
    private function retrieve_review() {
        global $DB;
        if ($subjectreview = $DB->get_record('progressreview_subject', array('reviewid' => $this->progressreview->id))) {
            foreach ((array)$subjectreview as $property => $value) {
                if ($property != 'reviewid') {
                    $this->$property = $value;
                }
            }
            $targetgrades = $this->retrieve_targetgrades(array('min'));
            $this->minimumgrade = $targetgrades->min;
        } else {
            $this->skeleton_review();
        }
        if (!$this->scaleid) {
            $this->scaleid = $this->retrieve_scaleid();
        }
        if ($this->scaleid) {
            $scalerecord = $DB->get_record('scale', array('id' => $this->scaleid));
            $this->scale = explode(',', $scalerecord->scale);
        } else {
            $this->scale = array();
        }
    } // end of member function retrieve_review

    /**
     * Retrieves the current % attendance for the student on the current course.
     *
     * Must be overidden in {@see progressreview_subject}
     *
     * @return
     * @access private
     */
    protected abstract function retrieve_attendance(); // end of member function retrieve_attendance

    /**
     * Retrieves the current total and completed homework stats for the current student
     * on the current course
     *
     * A homework is counted in the total if its due date has passed. It is counted as
     * completed if it has been graded above the bottom grade on the scale.
     *
     * @return
     * @access private
     */
    protected function retrieve_homework() {
        global $DB;
        $homework = new stdClass;
        $sql = 'SELECT COUNT(*)
                FROM {grade_items} AS i
                    JOIN {assignment} AS a ON i.iteminstance = a.id
                WHERE i.courseid = ?
                    AND i.itemmodule = ?
                    AND (a.timeavailable != ? OR
                        (SELECT COUNT(*)
                         FROM {grade_grades} AS g
                         WHERE g.itemid = i.id) > ?)
                    AND a.timedue < ?';
        $params = array($this->progressreview->get_course()->originalid, 'assignment', 0, 0, time());
        $homework->total = $DB->count_records_sql($sql, $params);

        $sql = 'SELECT COUNT(*)
                FROM {grade_grades} AS g
                    JOIN {grade_items} AS i ON i.id = g.itemid AND i.itemtype = ?
                    JOIN {assignment} AS a ON i.iteminstance = a.id
                WHERE g.userid = ?
                    AND i.courseid = ?
                    AND g.finalgrade > ?
                    AND a.timedue < ?';
        $params = array('mod', $this->progressreview->get_student()->id, $this->progressreview->get_course()->originalid, 1, time());
        $homework->done = $DB->count_records_sql($sql, $params);
        return $homework;
    } // end of member function retrieve_homework

    /**
     * Retrieves the student's current target grade, if the targetgrades report is
     * installed.
     *
     * @return
     * @access private
     */
    protected function retrieve_targetgrades($items = array('target', 'min', 'cpg')) {
        global $DB;
        $grades = array('target' => null, 'min' => null, 'cpg' => null);
        if ($DB->record_exists('config_plugins', array('plugin' => 'report_targetgrades', 'name' => 'version'))) {
            $courseid = $this->progressreview->get_course()->originalid;
            $studentid = $this->progressreview->get_student()->id;
            foreach ($items as $item) {
                if (!in_array($item, array('target', 'min', 'cpg'))) {
                    throw new coding_exception('Invalid item specified. Valid names are target, min and cpg.');
                }
                if($item = $DB->get_record('grade_items', array('courseid' => $courseid, 'idnumber' => 'targetgrades_'.$item))) {
                    $grade = $DB->get_record('grade_grades', array('itemid' => $item->id, 'userid' => $studentid));
                    $grades[$item] = $grade->finalgrade;
                } else {
                    $grades[$item] = null;
                }
            }
        }
        return (object)$grades;
    } // end of member function retrieve_targetgrade

    /**
     * Retrieves the scale for the class's target grades.
     *
     * Uses the targetgrades plugin if available, then makes a guess based on the most
     * used scale in the gradebook.
     *
     * @return
     * @access private
     */
    protected function retrieve_scaleid() {
        global $DB;
        if ($DB->record_exists('config_plugins', array('plugin' => 'report_targetgrades', 'name' => 'version'))) {
            $courseid = $this->progressreview->get_course()->originalid;
            if ($scaleitems = $DB->get_records('grade_items', array('courseid' => $courseid, 'idnumber' => 'targetgrades_target'))) {
                return current($scaleitems)->scaleid;
            }
        }
        return 0;
    } // end of member function retrieve_scaleid

    public function snapshot() {
        $attendance = $this->retrieve_attendance();
        $homework = $this->retrieve_homework();
        $data = array(
            'attendance' => $attendance->attendance,
            'punctuality' => $attendance->punctuality,
            'homeworktotal' => $homework->total,
            'homeworkdone' => $homework->done
        );
        return $this->update($data);
    }

    public function get_scaleid() {
        return $this->scaleid;
    }

} // end of progressreview_subject

require_once($CFG->dirroot.'/local/progressreview/plugins/subject/locallib.php');
