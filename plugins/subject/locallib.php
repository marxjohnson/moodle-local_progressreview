<?php
require_once($CFG->dirroot.'/local/intranet/locallib.php');
/**
 * Child of progressreview_subject_template, allowing methods to be customised
 *
 * Since methods for determining statistics are likely to differ between institutions,
 * {@see progressreview_subject_template} leaves some methods undefined.
 *
 * Sensible defaults are defined for these functions here, but this file is intended
 * for customisation.
 *
 */
class progressreview_subject extends progressreview_subject_template {

    /**
     * Return the attendance and punctuality as percentages.
     *
     * @todo Modify to use the attendance module by default
     */
    protected function retrieve_attendance() {
        ini_set('memory_limit','512M');
        $coursecode = $this->progressreview->get_course()->shortname;
        $studentidnum = $this->progressreview->get_student()->idnumber;

        $marks = progressreview_attendance_helper::attendance_for_student_in_class($coursecode, $studentidnum);
        $attendance = new stdClass;
        $attendance->attendance = $marks->totalpresent / $marks->totalpossible * 100;
        $attendance->punctuality = $marks->present / ($marks->present+$marks->late) * 100;
        return $attendance;
    }

    protected function retrieve_scaleid() {
        global $DB;
        if ($scaleid = parent::retrieve_scaleid()) {
            return $scaleid;
        }

        $courseid = $this->progressreview->get_course()->originalid;
        if ($targetitems = $DB->get_records('grade_items', array('idnumber' => 'targetgrades_target', 'courseid' => $courseid))) {
            return current($targetitems)->scaleid;
        } else if ($formalitems = $DB->get_records('grade_items', array('formal' => 1, 'courseid' => $courseid))) {
            return current($formalitems)->scaleid;
        } else {
            return 41;
        }
    }

    protected function retrieve_targetgrades($items = array('target', 'min', 'cpg')) {
        global $DB;
        $grades = (array)parent::retrieve_targetgrades($items);
        $studentid = $this->progressreview->get_student()->id;
        if($avgcse = $DB->get_record('user_info_data', array('fieldid' => 1, 'userid' => $studentid))) {
            $grades['min'] = $avgcse->data;
        }
        return (object)$grades;
    } // end of member function retrieve_targetgrade

}


class progressreview_attendance_helper {

    static $attendance;

    static public function attendance_for_student_in_class($classcode, $studentidnum) {

        if (empty(self::$attendance)) {
            self::$attendance = array();
        }

        if (!isset(self::$attendance[$classcode])) {
            self::attendance_for_class($classcode);
        }

        return self::$attendance[$classcode][$studentidnum];
    }

    private function attendance_for_class($coursecode) {

        $select = "SELECT DISTINCT
            t3.sr_id,
            t3.sr_weekpattern AS \"allmarks\",
            replace(replace(replace(replace(replace(t3.sr_weekpattern,' '),'P'),'H'),'O'),'A') AS \"totalpresent\",
            replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(t3.sr_weekpattern,' '),'X'),'P'),'H'),'O'),'A'),'T'),'E'),'W'),'L') AS \"present\",
            replace(replace(replace(replace(replace(replace(replace(replace(replace(replace(t3.sr_weekpattern,' '),'X'),'P'),'H'),'O'),'A'),'T'),'E'),'W'),'/') AS \"late\",
            replace(t3.sr_weekpattern,' ') AS \"totalpossible\",
            t1.p_id AS \"studentid\" ";
        $from = "FROM {activity} t2,
            {register} t4,
            {moduleactivity} t7,
            {studentregister} t3,
            {moduleenrolment} t5,
            {module} t6,
            {person} t1 ";
        $where = "WHERE (t3.sr_student=t1.p_id)
            and (t5.e_status=to_char(1))
            and (t5.e_student=t1.p_id(+))
            and (t5.e_module=t6.m_id(+))
            and (t7.ma_activitymodule=t6.m_id)
            and (t7.ma_activity=t2.a_id)
            and (t3.sr_activity=t2.a_id(+))
            and (t3.sr_register=t4.r_id(+))
            and (t4.r_reference = :coursecode)";

        $params = array('coursecode' => $coursecode);
        $UDB = unite_connect();
        $studentmarks = $UDB->get_records_sql($select.$from.$where, $params);
        $UDB->dispose();

        self::$attendance[$coursecode] = array();
        foreach ($studentmarks as $studentmark) {
            if (!isset(self::$attendance[$coursecode][$studentmark->studentid])) {
                $marks = new stdClass();
                $marks->totalpresent = strlen($studentmark->totalpresent);
                $marks->totalpossible = strlen($studentmark->totalpossible);
                $marks->present = strlen($studentmark->present);
                $marks->late = strlen($studentmark->late);
                self::$attendance[$coursecode][$studentmark->studentid] = $marks;
            } else {
                self::$attendance[$coursecode][$studentmark->studentid]->totalpresent += strlen($studentmark->totalpresent);
                self::$attendance[$coursecode][$studentmark->studentid]->totalpossible += strlen($studentmark->totalpossible);
                self::$attendance[$coursecode][$studentmark->studentid]->present += strlen($studentmark->present);
                self::$attendance[$coursecode][$studentmark->studentid]->late += strlen($studentmark->late);
            }
        }
    }
}
