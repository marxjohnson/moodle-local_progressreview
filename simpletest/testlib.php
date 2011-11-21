<?php

if (!defined('MOODLE_INTERNAL')) {
    die();
}

require_once($CFG->dirroot.'/local/progressreview/lib.php');

class local_progressreview_lib_test extends UnitTestCaseUsingDatabase {

    protected function setUp() {
        $this->switch_to_test_cfg();
        $this->switch_to_test_db();
    }

    protected function tearDown() {
        $this->revert_to_real_db();
        $this->revert_to_real_cfg();
    }
}
