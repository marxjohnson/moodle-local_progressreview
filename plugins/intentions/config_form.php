<?php

class progressreview_intentions_config_form extends moodleform {

    public function definition() {
        global $DB;

        $mform = $this->_form;
        $mform->addElement('hidden', 'plugin', 'intentions');
        $mform->addElement('header', 'uploadheader', get_string('configheader', 'progressreview_intentions'));
        $mform->addElement('text', 'tutormask', get_string('tutormask', 'progressreview_intentions'));
        $mform->addHelpButton('tutormask', 'tutormask', 'progressreview_intentions');
        if ($tutormask = get_config('progressreview_intentions', 'tutormask')) {
            $mform->setDefault('tutormask', $tutormask);
        }
        $mform->addElement('filepicker',
                           'csvfile',
                           get_string('csvfile', 'progressreview_intentions'),
                           null,
                           array('accepted_types' => 'csv,txt'));
        $mform->addHelpButton('csvfile', 'csvfile', 'progressreview_intentions');
        $mform->addElement('submit', 'submit', get_string('upload'));

        if ($intentions = $DB->get_records('progressreview_intent', array(), 'currentcode')) {
            $table = new html_table();
            $table->head = array(
                get_string('id', 'progressreview_intentions'),
                get_string('currentcode', 'progressreview_intentions'),
                get_string('newcode', 'progressreview_intentions'),
                get_string('newname', 'progressreview_intentions')
            );
            foreach ($intentions as $intention) {
                unset($intention->timecreated);
                unset($intention->timemodified);
                $table->data[] = (array)$intention;
            }
            $mform->closeHeaderBefore('closeheader');
            $mform->addElement('static', 'closeheader', '');

            $mform->addElement('html', html_writer::table($table));
        }

    }

    public function process($data) {
        global $USER, $DB;
        if (!empty($data->tutormask)) {
            set_config('tutormask', $data->tutormask, 'progressreview_intentions');
        }
        if (!empty($data->csvfile)) {
            if (is_file($data->csvfile)) {
                if (!$fh = fopen($this->filename, 'r')) {
                    throw new Exception(get_string('cantreadcsv', 'progressreview_intentions'));
                }
            } else {
                $fs = get_file_storage();
                $context = get_context_instance(CONTEXT_USER, $USER->id);
                $files = $fs->get_area_files($context->id,
                                             'user',
                                             'draft',
                                             $data->csvfile,
                                             'id DESC',
                                             false);
                if (!$files) {
                    throw new Exception(get_string('cantreadcsv', 'progressreview_intentions'));
                }
                $file = reset($files);
                if (!$fh = $file->get_content_file_handle()) {
                    throw new Exception(get_string('cantreadcsv', 'progressreview_intentions'));
                }
            }

            $errparams = new stdClass;
            $errparams->line = 1;
            while ($row = fgetcsv($fh)) {
                $errparams->num = count($row);
                if ($errparams->num != 3) {
                    $error = get_string('wrongcolcount',
                                        'progressreview_intentions',
                                        $errparams);
                    throw new progressreview_invalidvalue_exception($error);
                }

                $params = array('currentcode' => $row[0]);
                if ($record = $DB->get_record('progressreview_intent', $params)) {
                    $record->newcode = $row[1];
                    $record->newname = $row[2];
                    $record->timemodified = time();
                    $DB->update_record('progressreview_intent', $record);
                } else {
                    $record = (object)array(
                        'currentcode' => $row[0],
                        'newcode' => $row[1],
                        'newname' => $row[2],
                        'timecreated' => time(),
                        'timemodified' => time()
                    );
                    $DB->insert_record('progressreview_intent', $record);
                }
                $errparams->line++;
            }
        }
    }

}
