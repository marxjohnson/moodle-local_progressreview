<?php

require_once('../../../config.php');
require_once($CFG->dirroot.'/local/progressreview/lib.php');
require_once($CFG->libdir.'/formslib.php');

$plugin = optional_param('plugin', null, PARAM_ALPHA);

$controller = 'progressreview_controller';

require_login($SITE);
$systemcontext = get_context_instance(CONTEXT_SYSTEM);
require_capability('moodle/local_progressreview:manage', $systemcontext);

$PAGE->set_url('/local/progressreview/');
$PAGE->navbar->add(get_string('pluginname', 'local_progressreview'));
add_to_log(SITEID, 'local_progressreview', 'view', $PAGE->url->out());
$output = $PAGE->get_renderer('local_progressreview');
$content = '';
$form = false;

$plugins = $controller::get_plugins_with_config();
$strplugins = get_string('plugins', 'local_progressreview');
$strprogressreview = get_string('pluginname', 'local_progressreview');

if ($plugin && in_array($plugin, $plugins)) {

    $strpluginname = get_string('pluginname', 'progressreview_'.$plugin);
    $url = new moodle_url('/local/progressreview/plugins/index.php');
    $PAGE->navbar->add($strplugins, $url);
    $PAGE->navbar->add($strpluginname);
    $PAGE->set_url($url, array('plugin' => $plugin));
    $PAGE->set_title($strprogressreview.' - '.$strpluginname);
    $content .= $output->heading($strpluginname, 2);

    require_once($CFG->dirroot.'/local/progressreview/plugins/'.$plugin.'/config_form.php');
    $formclass = 'progressreview_'.$plugin.'_config_form';
    $form = new $formclass;

    if ($data = $form->get_data()) {
        $form->process($data);
        redirect($PAGE->url);
    }

} else {

    $PAGE->navbar->add($strplugins);
    $PAGE->set_url('/local/progressreview/plugins/index.php');
    $PAGE->set_title($strprogressreview.' - '.$strplugins);
    $content .= $output->heading($strplugins, 2);

    $content .= $output->plugin_config_links($plugins);

}


echo $OUTPUT->header();

$output->tabs(3);
echo $content;
if ($form) {
    $form->display();
}

echo $OUTPUT->footer();


?>
