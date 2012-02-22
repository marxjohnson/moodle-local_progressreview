<?php

if (!defined('MOODLE_INTERNAL')) {
    die();
}

require_once($CFG->dirroot.'/local/progressreview/lib.php');
require_once($CFG->dirroot.'/local/progressreview/plugins/subject/lib.php');

class local_progressreview_lib_test extends UnitTestCaseUsingDatabase {

    private $testdata = array();

    private $DAY;
    private $ONEWEEK;

    public function setUp() {
        $this->DAY = 24*60*60;
        $this->ONEWEEK = 7*$this->DAY;
        $this->switch_to_test_db();
        $this->create_test_tables(array('grade_grades', 'grade_items', 'course', 'user', 'config_plugins', 'scale'), 'lib');
        $this->create_test_table('assignment', 'mod/assignment');
        $tables = array(
            'progressreview',
            'progressreview_session',
            'progressreview_course',
            'progressreview_teachers',
            'progressreview_activeplugins'
        );
        $this->create_test_tables($tables, 'local/progressreview');
        $this->create_test_table('progressreview_subject', 'local/progressreview/plugins/subject');
        $this->testdata['assignment'] = $this->load_test_data('assignment',
            array('course', 'name', 'intro', 'type', 'timedue', 'timeavailable', 'timemodified'), array(
                # Exclude - Due date before homeworkstart
                array(2, 'hw1', 'hw', 'offline',
                strtotime((date('Y')-1).'/08/01'), strtotime((date('Y')-1).'/07/01'), strtotime((date('Y')-1).'/07/01')),
                # Exclude - No available or due date, modified before homeworkstart
                array(2, 'hw2', 'hw', 'offline', 0, 0, strtotime((date('Y')-1).'/08/01')),
                # Include - due date between homeworkstart and current date
                array(2, 'hw3', 'hw', 'offline',
                strtotime((date('Y')-1).'/09/02'), strtotime((date('Y')-1).'/09/01'), strtotime((date('Y')-1).'/09/01')),
                # Include - Due date between homeworkstart and current date
                array(2, 'hw4', 'hw', 'offline',
                strtotime((date('Y')-1).'/09/10'), strtotime((date('Y')-1).'/09/03'), strtotime((date('Y')-1).'/09/03')),
                # Include - Due date between homeworkstart and current date
                array(2, 'hw5', 'hw', 'offline',
                strtotime((date('Y')-1).'/09/11'), strtotime((date('Y')-1).'/09/04'), strtotime((date('Y')-1).'/09/04')),
                # Include - Due date between homeworkstart and current date
                array(2, 'hw6', 'hw', 'offline',
                strtotime((date('Y')-1).'/09/12'), strtotime((date('Y')-1).'/09/05'), strtotime((date('Y')-1).'/09/06')),
                # Exclude - Due date in the future
                array(2, 'hw7', 'hw', 'offline', (time()+$this->DAY), (time()-$this->DAY), (time()-$this->DAY)),
                # Exclude - Wrong course
                array(4, 'hw8', 'hw', 'offline',
                strtotime((date('Y')-1).'/09/11'), strtotime((date('Y')-1).'/09/04'), strtotime((date('Y')-1).'/09/04'))
                # Total - 5 included, 4 excluded
            )
        );
        $this->testdata['grade_items'] = $this->load_test_data('grade_items',
            array('courseid', 'itemname', 'itemtype', 'itemmodule', 'iteminstance', 'timecreated', 'timemodified', 'idnumber', 'scaleid'), array(
                # Exclude - see above
                array('2', 'i1', 'mod', 'assignment', 1, time(), time(), '', 1),
                # Exclude - see above
                array('2', 'i2', 'mod', 'assignment', 2, time(), time(), '', 1),
                array('2', 'i3', 'mod', 'assignment', 4, time(), time(), '', 1),
                array('2', 'i4', 'mod', 'assignment', 5, time(), time(), '', 1),
                array('2', 'i5', 'mod', 'assignment', 6, time(), time(), '', 1),
                array('2', 'i6', 'mod', 'assignment', 7, time(), time(), '', 1),
                # Exclude - see above
                array('2', 'i7', 'mod', 'assignment', 8, time(), time(), '', 1),
                # Exclude - wrong course - see above
                array('3', 'i8', 'mod', 'assignment', 9, time(), time(), '', 1),
                # Exclude - wrong module
                array('2', 'i9', 'mod', 'forum', 1, time(), time(), '', 1),
                # Total - 4 included, 5 excluded
                # Target Grade items:
                array('2', 'Minimum Grade', 'manual', null, null, time(), time(), 'targetgrades_min', 2),
                array('2', 'Target Grade', 'manual', null, null, time(), time(), 'targetgrades_target', 2),
                array('2', 'Current Performance Grage', 'manual', null, null, time(), time(), 'targetgrades_cpg', 2)
            )
        );
        $this->testdata['grade_grades'] = $this->load_test_data('grade_grades',
            array('itemid', 'userid', 'rawgrade', 'finalgrade', 'timecreated', 'timemodified'), array(
                # Exclude - Grade for an old assignment (see above)
                array(1, 2, 3, 3, time(), time()),
                # include - grade above 1, for an included assignment on this course
                array(3, 2, 5, 5, time(), time()),
                # include - grade above 1, for an included assignment on this course
                array(4, 2, 6, 6, time(), time()),
                # Exclude - Grade too low
                array(5, 2, 1, 1, time(), time()),
                # Exclude - Grade for a different user
                array(6, 5, 3, 3, time(), time()),
                # Exclude - Assignment not due - see above
                array(7, 2, 1, 1, time(), time()),
                # Exclude - Grade for assignment on a different course (see above)
                array(8, 2, 5, 5, time(), time()),
                # Exclude - Grade for a non-assignment module (see above)
                array(9, 2, 4, 4, time(), time()),
                # Total - 2 included, 6 excluded
                # Target grade:
                array(10, 2, 3, 3, time(), time())
            )
        );
        $this->testdata['user'] = $this->load_test_data('user',
            array('username', 'firstname', 'lastname', 'password', 'email', 'timecreated', 'timemodified'), array(
                array('admin', 'Admin', '', 'a', 'a@a.com', time(), time()),
                array('student', 'Test', 'Student', 'b', 'b@b.com', time(), time()),
                array('teacher', 'Test', 'Teacher', 'c', 'c@c.com', time(), time())
            )
        );
        $this->testdata['course'] = $this->load_test_data('course',
            array('shortname', 'fullname', 'category', 'timecreated', 'timemodified'), array(
                array('site', 'Site', 0, time(), time()),
                array('course1', 'Course 1', 0, time(), time()),
                array('course2', 'Course 2', 0, time(), time())
            )
        );

        $this->testdata['progressreview_session'] = $this->load_test_data('progressreview_session',
            array(
                'name',
                'deadline_subject',
                'deadline_tutor',
                'lockafterdeadline',
                'scale_behaviour',
                'scale_effort',
                'scale_homework',
                'homeworkstart',
                'inductionreview'),
            array(
                array('Progress Review 1',
                    time()+$this->ONEWEEK,
                    time()+(2*$this->ONEWEEK),
                    0,
                    'good,bad,ugly',
                    'good,bad,ugly',
                    'good,bad,ugly',
                    strtotime((date('Y')-1).'/09/01'),
                    0),
                array('Induction Review 1',
                    time()+$this->ONEWEEK,
                    time()+(2*$this->ONEWEEK),
                    0,
                    'good,bad,ugly',
                    'good,bad,ugly',
                    'good,bad,ugly',
                    strtotime((date('Y')-1).'/09/01'),
                    1)
            )
        );
        $this->testdata['progressreview_activeplugins'] = $this->load_test_data('progressreview_activeplugins',
            array('plugin', 'sessionid', 'reviewtype'), array(
                array('subject', 1, PROGRESSREVIEW_SUBJECT),
                array('tutor', 1, PROGRESSREVIEW_TUTOR),
                array('targets', 1, PROGRESSREVIEW_TUTOR),
                array('subject', 2, PROGRESSREVIEW_SUBJECT),
                array('tutor', 2, PROGRESSREVIEW_TUTOR),
                array('targets', 2, PROGRESSREVIEW_TUTOR)
            )
        );
        $this->testdata['config_plugins'] = $this->load_test_data('config_plugins',
            array('plugin', 'name', 'value'), array(
            array('progressreview_subject', 'version', 1234),
            array('progressreview_tutor', 'version', 1234),
            array('progressreview_tutor', 'foo', 'bar'),
            array('progressreview_targets', 'version', 3456),
            array('block_navigation', 'version', 0001),
            array('block_navigation', 'foo', 'bar'),
            array('report_targetgrades', 'version', 0001)
        ));
    }

    public function tearDown() {
        foreach ($this->testdata as $table => $rows) {
            $this->delete_test_data($table, $rows);
            $this->drop_test_table($table);
        }
        $this->revert_to_real_db();
    }

    public function test_subjectreview() {
        global $DB;
        $student = $DB->get_record('user', array('username' => 'student'));
        $teacher = $DB->get_record('user', array('username' => 'teacher'));
        $course = $DB->get_record('course', array('shortname' => 'course1'));
        $session = $DB->get_record('progressreview_session', array('id' => 1));
        $inductionsession = $DB->get_record('progressreview_session', array('inductionreview' => 1));
        $session->scale_homework = explode(',', $session->scale_homework);
        $session->scale_behaviour = explode(',', $session->scale_behaviour);
        $session->scale_effort = explode(',', $session->scale_effort);
        $progressreview = new progressreview($student->id,
                                             $session->id,
                                             $course->id,
                                             $teacher->id,
                                             PROGRESSREVIEW_SUBJECT);

        // Check that the archive tables have been populated correctly
        $courseparams = array(
            $course->id,
            $DB->sql_compare_text($course->shortname),
            $DB->sql_compare_text($course->fullname)
        );
        $coursewhere = 'originalid = ? AND shortname = ? AND fullname = ?';
        $this->assertTrue($course = $DB->get_record_select('progressreview_course', $coursewhere, $courseparams));

        $teacherparams = array(
            $teacher->id,
            $DB->sql_compare_text($teacher->firstname),
            $DB->sql_compare_text($teacher->lastname)
        );
        $teacherwhere = 'originalid = ? AND firstname = ? and lastname = ?';
        $this->assertTrue($teacher = $DB->get_record_select('progressreview_teachers', $teacherwhere, $teacherparams));

        // Check that the cache has been populated correctly
        $this->assertEqual($student, progressreview_cache::$students[$student->id]);
        $this->assertEqual($session, progressreview_cache::$sessions[$session->id]);
        $this->assertEqual($course, progressreview_cache::$courses[$course->originalid]);
        $this->assertEqual($teacher, progressreview_cache::$teachers[$teacher->originalid]);


        // Check that the Progress review record has been inserted correctly
        $reviewparams = array(
            'sessionid' => $session->id,
            'studentid' => $student->id,
            'courseid' => $course->originalid,
            'teacherid' => $teacher->originalid,
            'reviewtype' => PROGRESSREVIEW_SUBJECT
        );
        $this->assertTrue($DB->record_exists('progressreview', $reviewparams));

        // Check that the Skeleton subject review has been generated correctly.
        $subjectreview = $progressreview->get_plugin('subject')->get_review();

        // As show in setUp above, there are 4 valid homeworks for the student
        $this->assertEqual($subjectreview->homeworktotal, 4);
        // 2 of the homeworks have grades above 1 for this user, 1 has a grade below 1 and 1 has no grade
        $this->assertEqual($subjectreview->homeworkdone, 2);

        $post = array(
            'homeworkdone' => '5',
            'homeworktotal' => '6',
            'behaviour' => '1',
            'effort' => '2',
            'targetgrade' => '6',
            'performancegrade' => '5',
            'comments' => 1.4556
        );

        $cleaned = $progressreview->get_plugin('subject')->clean_params($post);

        $this->assertTrue(is_int($cleaned['homeworkdone']));
        $this->assertTrue(is_int($cleaned['homeworktotal']));
        $this->assertTrue(is_int($cleaned['behaviour']));
        $this->assertTrue(is_int($cleaned['effort']));
        $this->assertTrue(is_int($cleaned['targetgrade']));
        $this->assertTrue(is_int($cleaned['performancegrade']));
        $this->assertTrue(is_string($cleaned['comments']));

        $post['comments'] = '<script>alert("XSS alert!")</script>Test';

        $cleaned = $progressreview->get_plugin('subject')->clean_params($post);

        $this->assertFalse(strpos($cleaned['comments'], '<script>'));

        $inductionreview = new progressreview($student->id,
                                            $inductionsession->id,
                                            $course->id,
                                            $teacher->id,
                                            PROGRESSREVIEW_SUBJECT);

        $cleaned = $inductionreview->get_plugin('subject')->clean_params($post);
        $this->assertFalse(array_key_exists('comments', $cleaned));

        $post = array(
            'homeworkdone' => '',
            'homeworktotal' => '',
            'behaviour' => '',
            'effort' => '',
            'targetgrade' => '',
            'performancegrade' => '',
            'comments' => ''
        );

        $cleaned = $progressreview->get_plugin('subject')->clean_params($post);
        $truecleaned = array_filter($cleaned);
        $this->assertTrue(empty($truecleaned));

        $data = array(
            'reviewid' => $progressreview->id,
            'comments' => 'Aliquam eget aliquet elit. Nullam hendrerit risus vel metus.',
            'behaviour' => 2,
            'effort' => 3,
            'homeworkstandard' => 3,
            'homeworkdone' => 10,
            'homeworktotal' => 11,
            'attendance' => 90,
            'punctuality' => 95,
            'targetgrade' => 5,
            'performancegrade' => 4,
            'sesskey' => 'asd86gd8d8', // Fields that aren't related to subject plugin should be ignored.
            'foo' => 'bar',
            'herp' => 'derp'
        );

        $progressreview->get_plugin('subject')->process_form_fields($data);

        $params = $data;
        unset($params['sesskey']);
        unset($params['foo']);
        unset($params['bar']);
        $params['comments'] = $DB->sql_compare_text($params['comments']);
        $where = 'reviewid = :reviewid AND comments = :comments AND behaviour = :behaviour AND
            effort = :effort AND homeworkstandard = :homeworkstandard AND homeworkdone = :homeworkdone
            AND attendance = :attendance AND punctuality = :punctuality AND targetgrade = :targetgrade
            AND performancegrade = :performancegrade';

        $this->assertTrue($DB->record_exists_select('progressreview_subject', $where, $params));
        $gradeparams = array(
            'userid' => $progressreview->get_student()->id,
            'itemid' => 11,
            'finalgrade' => 5,
            'rawgrade' => 5
        );
        $this->assertTrue($DB->record_exists('grade_grades', $gradeparams));
        $gradeparams['itemid'] = 12;
        $gradeparams['finalgrade'] = 4;
        $gradeparams['rawgrade'] = 4;
        $this->assertTrue($DB->record_exists('grade_grades', $gradeparams));

        $data['targetgrade'] = 6;
        $data['performancegrade'] = 7;

        $progressreview->get_plugin('subject')->process_form_fields($data);

        $params = $data;
        unset($params['sesskey']);
        unset($params['foo']);
        unset($params['bar']);
        $params['comments'] = $DB->sql_compare_text($params['comments']);

        $this->assertTrue($DB->record_exists_select('progressreview_subject', $where, $params));

        $gradeparams['finalgrade'] = 7;
        $gradeparams['rawgrade'] = 7;
        $this->assertTrue($DB->record_exists('grade_grades', $gradeparams));
        $gradeparams['itemid'] = 11;
        $gradeparams['finalgrade'] = 6;
        $gradeparams['rawgrade'] = 6;
        $this->assertTrue($DB->record_exists('grade_grades', $gradeparams));

        $data['homeworkdone'] = 10;
        $data['homeworktotal'] = 5;
        $this->expectException('progressreview_invalidvalue_exception');

        $progressreview->get_plugin('subject')->process_form_fields($data);

    }
}
