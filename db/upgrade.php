<?php

function xmldb_local_progressreview_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2011101317) {
        ini_set('max_execution_time', 0); //Big job, disable time limit.
        // Changing the default of field homeworkdone on table progressreview_subject to drop it
        $table = new xmldb_table('progressreview_subject');
        $field = new xmldb_field('homeworkdone', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'homeworkstandard');

        // Launch change of default for field homeworkdone
        $dbman->change_field_default($table, $field);

        $field = new xmldb_field('homeworktotal', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'homeworkdone');
        $dbman->change_field_default($table, $field);


        // Convert old course archive
        $courses = $DB->get_records('termreview_course_history');
        foreach ($courses as $course) {
            if (!$DB->record_exists('progressreview_course', array('originalid' => $course->idnumber))) {
                $prcourse = (object)array(
                    'originalid' => $course->idnumber,
                    'fullname' => $course->fullname,
                    'shortname' => $course->shortname
                );
                $DB->insert_record('progressreview_course', $prcourse);
            }
        }
        unset($courses);

        // Archive teachers where needed and possible
        $select = 'SELECT DISTINCT teacherid ';
        $from = 'FROM {termreview_teacher}';
        $teacherids = $DB->get_records_sql($select.$from);
        $select = 'SELECT DISTINCT tutorid ';
        $from = 'FROM {termreview_tutor}';
        $tutorids = $DB->get_records_sql($select.$from);
        $teacherids = $teacherids + $tutorids;
        $teacherids = array_keys($teacherids);

        foreach ($teacherids as $teacherid) {
            if (!$DB->record_exists('progressreview_teachers', array('originalid' => $teacherid))) {
                if ($teacher = $DB->get_record('user', array('id' => $teacherid), 'id, firstname, lastname')) {
                    $teacher->originalid = $teacher->id;
                    unset($teacher->id);
                    $DB->insert_record('progressreview_teachers', $teacher);
                }
            }
        }
        unset($teacherids);
        unset($tutorids);

        // Convert parent reviews to sessions
        $parentreview_to_session = array();
        $parentreviews = $DB->get_records('termreview', array('course' => 1));
        foreach ($parentreviews as $parentreview) { 
        upgrade_set_timeout();
            $session = array(
                'name' => $parentreview->name,
                'deadline_subject' => $parentreview->deadline_subject,
                'deadline_tutor' => $parentreview->deadline_tutor,
                'lockafterdeadline' => $parentreview->lockafterdeadline,
                'scale_behaviour' => $parentreview->behaviour_scale,
                'scale_effort' => $parentreview->effort_scale,
                'scale_homework' => $parentreview->hwstandard_scale,
                'template_subject' => $parentreview->template_subject,
                'template_tutor' => $parentreview->template_tutor,
                'snapshotdate' => $parentreview->snapshotdate,
                'previoussession' => $parentreview->previousreview,
                'inductionreview' => false
            );
            $where = '';
            $params = array();
            foreach ($session as $field => $value) {
                if (!empty($where)) {
                    $where .= ' AND ';
                }
                if ($field == 'template_subject' || $field == 'template_tutor') {
                    $field = $DB->sql_compare_text($field);
                }
                $where .= $field.' = ?';
                $params[] = $value;
            }

            $activeplugins = array(
                (object)array(
                    'plugin' => 'subject',
                    'reviewtype' => 2),
                (object)array(
                    'plugin' => 'tutor',
                    'reviewtype' => 1),
                (object)array(
                    'plugin' => 'targets',
                    'reviewtype' => 1)
            );

            if (!$DB->record_exists_select('progressreview_session', $where, $params)) {
                if (!$parentreview_to_session[$parentreview->id] = $DB->insert_record('progressreview_session', (object)$session)) {
                    print_r($session);
                    echo 'Session record conversion failed!';
                    return false;
                }
                foreach($activeplugins as $plugin) {
                    $plugin->sessionid = $parentreview_to_session[$parentreview->id];
                    $DB->insert_record('progressreview_activeplugins', $plugin);
                }
            }
        }
        unset($parentreviews);

        // For each session, convert old teacher reviews to new subject reviews and old tutor review to new tutor reviews
        foreach ($parentreview_to_session as $parentreviewid => $sessionid) {
            $select = 'SELECT tt.*, t.course, t.parentid ';
            $from = 'FROM {termreview} as t
                JOIN {termreview_teacher} tt ON t.id = tt.reviewid
                JOIN {progressreview_course} c ON t.course = c.originalid ';
            $where = 'WHERE t.parentid = ?';
            $teacherreviews = $DB->get_records_sql($select.$from.$where, array($parentreviewid));

            foreach ($teacherreviews as $teacherreview) {
                if ($teacherreview->studentid) {
                    $progressreview = array(
                        'sessionid' => $sessionid,
                        'teacherid' => $teacherreview->teacherid,
                        'studentid' => $teacherreview->studentid,
                        'courseid' => $teacherreview->course,
                        'reviewtype' => 2
                    );
                    if (!$DB->record_exists('progressreview', $progressreview)) {
                        $progressreview['datecreated'] = $progressreview['datemodified'] = time();
                        $progressreview = (object)$progressreview;
                        if (!$progressreview->id = $DB->insert_record('progressreview', $progressreview)) {
                            print_r($progressreview);
                            echo 'Progressreview record conversion failed';
                            return false;
                        }
                    } else {
                        continue;
                    }

                    $subjectreview = array(
                        'reviewid' => $progressreview->id,
                        'comments' => $teacherreview->comments,
                        'behaviour' => $teacherreview->behaviour,
                        'effort' => $teacherreview->effort,
                        'homeworkstandard' => $teacherreview->hwstandard,
                        'homeworkdone' => $teacherreview->hwdone,
                        'homeworktotal' => $teacherreview->hwtotal,
                        'attendance' => $teacherreview->attendance,
                        'punctuality' => $teacherreview->punctuality,
                        'scaleid' => $teacherreview->gradescale,
                        'targetgrade' => $teacherreview->gradetarget,
                        'performancegrade' => $teacherreview->gradeperform
                    );
                    if (empty($subjectreview['homeworkdone'])) {
                        $subjectreview['homeworkdone'] = 0;
                    }
                    if (empty($subjectreview['homeworktotal'])) {
                        $subjectreview['homeworktotal'] = 0;
                    }

                    $where = '';
                    $params = array();
                    foreach ($subjectreview as $field => $value) {
                        if (!empty($where)) {
                            $where .= ' AND ';
                        }
                        if ($field == 'comments') {
                            $field = $DB->sql_compare_text($field);
                        }
                        $where .= $field.' = ?';
                        $params[] = $value;
                    }

                    if (!$DB->record_exists_select('progressreview_subject', $where, $params)) {
                        $subjectreview = (object)$subjectreview;
                        if(!$subjectreview->id = $DB->insert_record('progressreview_subject', $subjectreview)) {
                            print_r($subjectreview);
                            echo 'Subject Review record conversion failed';
                            return false;
                        }
                    }
                }
            }

            unset($teacherreviews);

            $select = 'SELECT tt.*, t.course, t.parentid ';
            $from = 'FROM {termreview} as t
                JOIN {termreview_tutor} tt ON t.id = tt.reviewid
                JOIN {progressreview_course} c ON t.course = c.originalid ';
            $where = 'WHERE t.parentid = ?';
            $tutorreviews = $DB->get_records_sql($select.$from.$where, array($parentreviewid));

            foreach ($tutorreviews as $tutorreview) {
                if($tutorreview->studentid) {
                    $progressreview = array(
                        'sessionid' => $sessionid,
                        'teacherid' => $tutorreview->tutorid,
                        'studentid' => $tutorreview->studentid,
                        'courseid' => $tutorreview->course,
                        'reviewtype' => 1
                    );
                    if (!$DB->record_exists('progressreview', $progressreview)) {
                        $progressreview['datecreated'] = $progressreview['datemodified'] = time();
                        $progressreview = (object)$progressreview;
                        if (!$progressreview->id = $DB->insert_record('progressreview', $progressreview)) {
                            print_r($progressreview);
                            echo 'Progressreview record conversion failed';
                            return false;
                        }
                    } else {
                        continue;
                    }

                    $newtutorreview = array(
                        'reviewid' => $progressreview->id,
                        'comments' => $tutorreview->summary
                    );

                    $where = '';
                    $params = array();
                    foreach ($newtutorreview as $field => $value) {
                        if (!empty($where)) {
                            $where .= ' AND ';
                        }
                        if ($field == 'comments') {
                            $field = $DB->sql_compare_text($field);
                        }
                        $where .= $field.' = ?';
                        $params[] = $value;
                    }

                    if (!$DB->record_exists_select('progressreview_tutor', $where, $params)) {
                        $newtutorreview = (object)$newtutorreview;
                        if(!$newtutorreview->id = $DB->insert_record('progressreview_tutor', $newtutorreview)) {
                            print_r($newtutorreview);
                            echo 'Tutor Review record conversion failed';
                            return false;
                        }
                    } else {
                        continue;
                    }

                    $targetids = explode(',', $tutorreview->targets);

                    foreach ($targetids as $targetid) {
                        if($targetid) {
                            $target = array(
                                'reviewid' => $progressreview->id,
                                'targetid' => $targetid
                            );
                            if (!$DB->record_exists('progressreview_targets', $target)) {
                                $target = (object)$target;
                                if (!$target->id = $DB->insert_record('progressreview_targets', $target)) {
                                    print_r($target);
                                    echo 'Target conversion failed';
                                    return false;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    // subject savepoint reached
    upgrade_plugin_savepoint(true, 2011101317, 'progressreview', 'subject');
    upgrade_plugin_savepoint(true, 2011101317, 'local', 'progressreview');
    return true;
}
