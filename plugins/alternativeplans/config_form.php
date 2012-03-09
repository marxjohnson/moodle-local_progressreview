<?php

class progressreview_alternativeplans_config_form extends moodleform {

    public function definition() {
        global $DB, $OUTPUT;
        $mform = $this->_form;

        $id = optional_param('id', false, PARAM_INT);

        $mform->addElement('hidden', 'plugin', 'alternativeplans');
        $mform->addElement('text', 'description', get_string('description', 'progressreview_alternativeplans'));

        if ($id) {
            if ($plan = $DB->get_record('progressreview_altplan', array('id' => $id))) {
                $mform->setDefault('description', $plan->description);
                $mform->addElement('hidden', 'id', $id);
            }
        }

        $mform->addElement('submit', 'submit', get_string('submit'));

        if ($plans = $DB->get_records('progressreview_altplan', array(), 'description')) {
            $table = new html_table();
            $table->head = array(
                get_string('id', 'progressreview_alternativeplans'),
                get_string('description', 'progressreview_alternativeplans'),
                ''
            );
            $editicon = $OUTPUT->pix_icon('edit', get_string('edit'), 'moodle');
            foreach ($plans as $plan) {
                $editparams = array(
                    'plugin' => 'alternativeplans',
                    'id' => $plan->id
                );
                $editurl = new moodle_url('/local/progressreview/plugins/index.php', $editparams);
                $editlink = html_writer::link($editurl, $editicon);
                $table->data[] = array($plan->id, $plan->description, $editlink);
            }
            $mform->addElement('html', html_writer::table($table));
        }

    }

    public function process($data) {
        global $DB;
        if (!empty($data->description)) {
            if (empty($data->id)) {
                $record = (object)array(
                    'description' => $data->description
                );
                $DB->insert_record('progressreview_altplan', $record);
            } else {
                $record = (object)array(
                    'id' => $data->id,
                    'description' => $data->description
                );
                $DB->update_record('progressreview_altplan', $record);
            }
        }

        return redirect(new moodle_url('/local/progressreview/plugins/index.php', array('plugin' => 'alternativeplans')));
    }

}
