<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) { // needs this condition or there is error on login page
    $url = new moodle_url('/local/progressreview/index.php');
    $ADMIN->add('reports', new admin_externalpage('progressreview',
                                                  get_string('pluginname', 'local_progressreview'),
                                                  $url->out(),
                                                  'moodle/local_progressreview:manage'));
}
