<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

require_once($CFG->dirroot.'/local/progressreview/sessions_form.php');

class sessionform_test extends UnitTestCaseUsingDatabase {

    private $testdata = array();

    public function setUp() {
        $this->switch_to_test_db();
        $this->create_test_table('config_plugins', 'lib');
        $this->create_test_table('progressreview_session', 'local/progressreview');
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
                array('Progress Review 1', time()+(7*24*60*60), time()+(2*7*24*60*60), 0, '', '', '', 0)
            )
        );
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

}
