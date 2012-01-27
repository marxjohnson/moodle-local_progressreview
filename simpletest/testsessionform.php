<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

require_once($CFG->dirroot.'/local/progressreview/sessions_form.php');

define('TIME_ONEWEEK', (7*24*60*60));

class sessionform_test extends UnitTestCaseUsingDatabase {

    private $testdata = array();

    public function setUp() {
        $this->switch_to_test_db();
        $this->create_test_table('config_plugins', 'lib');
        $this->create_test_tables(array('progressreview_session', 'progressreview_activeplugins'), 'local/progressreview');
        $this->testdata['config_plugins'] = $this->load_test_data('config_plugins',
            array('plugin', 'name', 'value'), array(
            array('progressreview_subject', 'version', 1234),
            array('progressreview_tutor', 'version', 1234),
            array('progressreview_tutor', 'foo', 'bar'),
            array('progressreview_targets', 'version', 3456),
            array('block_navigation', 'version', 0001),
            array('block_navigation', 'foo', 'bar'))
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
                'inductionreview'),
            array(
                array('Progress Review 1', time()+TIME_ONEWEEK, time()+(2*TIME_ONEWEEK), 0, '', '', '', 0)
            )
        );
        $this->testdata['progressreview_activeplugins'] = array();
    }

    public function tearDown() {
        foreach($this->testdata as $table => $rows) {
            $this->delete_test_data($table, $rows);
            $this->drop_test_table($table);
        }
        $this->revert_to_real_db();
    }

    public function test_get_plugin_names() {
        $reflection_class = new ReflectionClass('progressreview_session_form');
        $method = $reflection_class->getMethod('get_plugin_names');
        $method->setAccessible(true);
        $form = new progressreview_session_form();

        $this->assertEqual($method->invoke($form), array('subject', 'targets', 'tutor'));

    }

    public function test_process() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/progressreview/lib.php');
        $subjectdeadline = time()+(rand(1,4)*TIME_ONEWEEK);
        $tutordeadline = $subjectdeadline+(TIME_ONEWEEK);
        $form = new progressreview_session_form();

        // Test creating a new session
        $data = (object)array(
            'editid' => 0,
            'name' => 'Progress Review 2',
            'deadline_subject' => $subjectdeadline,
            'deadline_tutor' => $tutordeadline,
            'lockafterdeadline' => 0,
            'scale_behaviour' => 'Good,Bad,Ugly',
            'scale_effort' => 'Good,Bad,Ugly',
            'scale_homework' => 'Good,Bad,Ugly',
            'template_subject' => null,
            'template_tutor' => null,
            'snapshotdate' => null,
            'previoussession' => null,
            'inductionreview' => 0,
            'plugins' => array(
                'subject' => 1,
                'tutor' => 1,
                'targets' => 0
            )
        );

        $record = clone($data);
        unset($record->plugins);
        unset($record->editid);
        $record->id = $form->process($data);

        $sessionrecord = $DB->get_record('progressreview_session', array('id' => $record->id));
        $pluginrecords = $DB->count_records('progressreview_activeplugins', array('sessionid' => $record->id));
        $subjectpluginparams = array($DB->sql_compare_text('subject'), $record->id, PROGRESSREVIEW_SUBJECT);
        $tutorpluginparams = array($DB->sql_compare_text('tutor'), $record->id, PROGRESSREVIEW_TUTOR);
        $targetpluginparams = array($DB->sql_compare_text('targets'), $record->id, PROGRESSREVIEW_TUTOR);
        $pluginwhere = 'plugin = ? AND sessionid = ? AND reviewtype = ?';
        $this->assertEqual($record, $sessionrecord);
        $this->assertEqual($pluginrecords, 2);
        $this->assertTrue($DB->record_exists_select('progressreview_activeplugins', $pluginwhere, $subjectpluginparams));
        $this->assertTrue($DB->record_exists_select('progressreview_activeplugins', $pluginwhere, $tutorpluginparams));
        $this->assertFalse($DB->record_exists_select('progressreview_activeplugins', $pluginwhere, $targetpluginparams));

        // Test editing a session, and activating a previously inactive plugin.
        $data->editid = $record->id;
        $data->name = 'Progress Review 2 Editied';
        $data->deadline_subject++;
        $data->deadline_tutor++;
        $data->lockafterdeadline = 1;
        $data->scale_behaviour .= ',Fugly';
        $data->scale_effort .= ',Fugly';
        $data->scale_homework .= ',Fugly';
        $data->template_subject = 'foo';
        $data->template_tutor = 'bar';
        $data->snapshotdate = time();
        $data->previoussession = 1;
        $data->inductionreview = 1;
        $data->plugins['targets'] = 1;

        $record = clone($data);
        $record->id = $data->editid;
        unset($record->plugins);
        unset($record->editid);
        $this->assertTrue($form->process($data));

        $sessionrecord = $DB->get_record('progressreview_session', array('id' => $record->id));
        $pluginrecords = $DB->count_records('progressreview_activeplugins', array('sessionid' => $record->id));
        $this->assertEqual($record, $sessionrecord);
        $this->assertEqual($pluginrecords, 3);
        $this->assertTrue($DB->record_exists_select('progressreview_activeplugins', $pluginwhere, $subjectpluginparams));
        $this->assertTrue($DB->record_exists_select('progressreview_activeplugins', $pluginwhere, $tutorpluginparams));
        $this->assertTrue($DB->record_exists_select('progressreview_activeplugins', $pluginwhere, $targetpluginparams));

        // Test deactivating a previously active plugin
        $data->plugins['targets'] = 0;

        $record = clone($data);
        $record->id = $data->editid;
        unset($record->plugins);
        unset($record->editid);
        $this->assertTrue($form->process($data));

        $pluginrecords = $DB->count_records('progressreview_activeplugins', array('sessionid' => $record->id));
        $this->assertEqual($pluginrecords, 2);
        $this->assertTrue($DB->record_exists_select('progressreview_activeplugins', $pluginwhere, $subjectpluginparams));
        $this->assertTrue($DB->record_exists_select('progressreview_activeplugins', $pluginwhere, $tutorpluginparams));
        $this->assertFalse($DB->record_exists_select('progressreview_activeplugins', $pluginwhere, $targetpluginparams));

    }

}
