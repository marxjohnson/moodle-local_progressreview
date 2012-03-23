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
 * Handles configuration of plugins
 *
 * If no plugin is selected, a list of plugins with a config_form class defined in a
 * config_form.php is displayed. Once selected, the form itself is displayed/processed by the
 * relevant class.
 *
 * @package   local_progressreview
 * @copyright 2011 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
