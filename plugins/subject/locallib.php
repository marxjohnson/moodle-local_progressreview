<?php
require_once($CFG->dirroot.'/local/intranet/locallib.php');
$UDB = unite_connect();
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
    private function retrieve_attendance() {
        global $UDB;
        $sql = "SELECT DISTINCT
                m.m_id AS \"id\",
                (count(rm.rm_id)-(sum(decode(rm.rm_mark,to_char(' '),1,0))+(sum(decode(rm.rm_mark,'N',1,0))+sum(decode(rm.rm_mark,to_char('X'),1,0))))) AS \"totalpossible\",
                (sum(decode(rm.rm_mark,to_char('/'),1,0))+sum(decode(rm.rm_mark,to_char('T'),1,0))+sum(decode(rm.rm_mark,to_char('W'),1,0))+sum(decode(rm.rm_mark,to_char('E'),1,0))+sum(decode(rm.rm_mark,to_char('L'),1,0))) AS \"totalpresent\",
                sum(decode(rm.rm_mark,to_char('/'),1,0)) AS \"present\",
                sum(decode(rm.rm_mark,to_char('L'),1,0)) AS \"late\",
                m.m_reference AS \"classcode\"
            FROM
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
                JOIN {moduleenrolment} me1 ON me1.e_module = m.m_id AND me1.e_student = p.p_id
            WHERE
                rm.rm_date <= sysdate
                and me1.e_status = to_char(1)
                and me1.e_reference = :coursecode
                and rm.rm_date >= :termstart
                and s.s_id = :studentidnum
             GROUP BY p.p_surname,
                    p.p_forenames,
                    p.p_id,
                    m.m_id,
                    m.m_name,
                    m.m_reference,
                    me.e_name,
                    me1.e_start,
                    me1.e_name,
                    me1.e_reference,
                    me1.e_id
            ORDER BY 1,2,14";

        $params = array('coursecode' => $this->progressreview->get_course()->shortname,
            'termstart' => '1-Sep-2011',
            'studentidnum' => $this->progressreview->get_student()->idnumber);

        $marks = $UDB->get_records_sql($sql, $params);
        $attendance = new stdClass;
        $attendance->attendance = $marks->totalpresent / $marks->totalpossible * 100;
        $attendance->punctuality = $marks->present / ($marks->present-$marks->late) * 100;
        return $attendance;
    }

    private function retrieve_performancegrade() {
        return 0;
    }
}