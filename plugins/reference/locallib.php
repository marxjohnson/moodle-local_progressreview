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
 * Defines the subject's main (local) class
 *
 * This file needs to be edited to implemented things like attendance calculation specific to
 * the institiution
 *
 * @package   local_progressreview
 * @subpackage progressreview_subject
 * @copyright 2011 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
        $coursecode = $this->progressreview->get_course()->shortname;
        $studentidnum = $this->progressreview->get_student()->idnumber;

        $select = "SELECT DISTINCT
            m.m_id AS \"id\",
            count(rm.rm_id) \"totalmarks\",
            sum(decode(rm.rm_mark,to_char('/'),1,0)) AS \"present\",
            sum(decode(rm.rm_mark,to_char('L'),1,0)) AS \"late\",
            sum(decode(rm.rm_mark,to_char('W'),1,0)) AS \"workplacement\",
            sum(decode(rm.rm_mark,to_char('E'),1,0)) AS \"exam\",
            sum(decode(rm.rm_mark,to_char('T'),1,0)) AS \"trip\",
            sum(decode(rm.rm_mark,to_char('X'),1,0)) AS \"notheld\",
            sum(decode(rm.rm_mark,to_char('A'),1,0)) AS \"absentauth\",
            sum(decode(rm.rm_mark,to_char('O'),1,0)) AS \"absentunauth\",
            sum(decode(rm.rm_mark,to_char('N'),1,0)) AS \"notonprog\",
            sum(decode(rm.rm_mark,to_char('H'),1,0)) AS \"holiday\",
            sum(decode(rm.rm_mark,to_char('P'),1,0)) AS \"personalabs\",
            sum(decode(rm.rm_mark,to_char(' '),1,0)) AS \"unmarked\",
            m.m_reference AS \"classcode\" ";
        $from = "FROM
            {person} p
            JOIN {student} s ON s.s_id = p.p_id
            JOIN {studentregister} sr ON sr.sr_student = p.p_id
            JOIN capa_registermarks rm ON rm.rm_studentregister = sr.sr_id
            JOIN {register} r ON rm.rm_register = r.r_id
            JOIN {activity} a ON a.a_register = r.r_id
            JOIN {moduleactivity} ma ON ma.ma_activity = a.a_id
            JOIN {staffactivity} sa ON sa.sa_activity = a.a_id
            JOIN {moduleenrolment} me ON s.s_studenttutorgroup = me.e_id
            JOIN {module} m ON ma.ma_activitymodule=m.m_id
            JOIN {moduleenrolment} me1 ON me1.e_module = m.m_id AND me1.e_student = p.p_id ";
        $where = "WHERE
            rm.rm_date <= sysdate
            and me1.e_status = to_char(1)
            and m.m_reference = :courseref
            and rm.rm_date >= :termstart
            and rm.rm_date <= sysdate
            and s.s_id = :studentidnum ";
        $group = "GROUP BY p.p_surname,
            p.p_forenames,
            p.p_id,
            m.m_id,
            m.m_name,
            m.m_reference,
            me.e_name,
            me1.e_start,
            me1.e_name,
            me1.e_reference,
            me1.e_id ";

        $params = array('courseref' => $coursecode,
            'termstart' => date('d-M-Y', $this->progressreview->get_session()->homeworkstart),
            'studentidnum' => $studentidnum);

        $udb = unite_connect();
        $marks = $udb->get_record_sql($select.$from.$where.$group, $params);
        $udb->dispose();

        $attendance = new stdClass;
        $totalpresent = $marks->present+$marks->late+$marks->workplacement+$marks->exam+$marks->trip;
        $totalexpected = $marks->totalmarks-$marks->notheld-$marks->notonprog-$marks->unmarked;
        $attendance->attendance = $totalpresent / $totalexpected * 100;
        $attendance->punctuality = $marks->present / ($marks->present+$marks->late) * 100;
        return $attendance;
    }

    protected function retrieve_scaleid() {
        global $DB;
        if ($scaleid = parent::retrieve_scaleid()) {
            return $scaleid;
        }

        $courseid = $this->progressreview->get_course()->originalid;
        $targetparams = array('idnumber' => 'targetgrades_target', 'courseid' => $courseid);
        $formalparams = array('formal' => 1, 'courseid' => $courseid);
        if ($targetitems = $DB->get_records('grade_items', $targetparams)) {
            return current($targetitems)->scaleid;
        } else if ($formalitems = $DB->get_records('grade_items', $formalparams)) {
            return current($formalitems)->scaleid;
        } else {
            return 41;
        }
    }

    protected function retrieve_targetgrades($items = array('target', 'min', 'cpg')) {
        global $DB;
        $grades = (array)parent::retrieve_targetgrades($items);
        $studentid = $this->progressreview->get_student()->id;
        if ($this->progressreview->get_session()->inductionreview) {
            $params = array('fieldid' => 1, 'userid' => $studentid);
            if ($avgcse = $DB->get_record('user_info_data', $params)) {
                $grades['min'] = $avgcse->data;
            }
        }
        return (object)$grades;
    } // end of member function retrieve_targetgrade

}
