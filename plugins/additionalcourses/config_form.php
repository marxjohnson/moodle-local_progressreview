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
 * Defines the config form to add/edit additional courses
 *
 * @package   local_progressreview
 * @subpackage progressreview_additionalcourses
 * @copyright 2012 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class progressreview_additionalcourses_config_form extends moodleform {

    public function definition() {
        global $DB;
        $mform = $this->_form;

        $mform->addElement('hidden', 'plugin', 'additionalcourses');
        $header = get_string('configheader', 'progressreview_additionalcourses');
        $mform->addElement('header', 'uploadheader', $header);

        $mform->addElement('filepicker',
                           'csvfile',
                           get_string('csvfile', 'progressreview_additionalcourses'),
                           null,
                           array('accepted_types' => 'csv,txt'));
        $mform->addHelpButton('csvfile', 'csvfile', 'progressreview_additionalcourses');
        $mform->addElement('submit', 'submit', get_string('upload'));

        if ($additionalcourses = $DB->get_records('progressreview_addition', array('active' => 1), 'code')) {
            $table = new html_table();
            $table->head = array(
                get_string('id', 'progressreview_additionalcourses'),
                get_string('name', 'progressreview_additionalcourses'),
                get_string('code', 'progressreview_additionalcourses')
            );
            foreach ($additionalcourses as $intention) {
                unset($intention->active);
                $table->data[] = (array)$intention;
            }
            $mform->closeHeaderBefore('closeheader');
            $mform->addElement('static', 'closeheader', '');

            $mform->addElement('html', html_writer::table($table));
        }

    }

    public function process($data) {
        global $USER, $DB;
        if (!empty($data->csvfile)) {
            if (is_file($data->csvfile)) {
                if (!$fh = fopen($this->filename, 'r')) {
                    throw new Exception(get_string('cantreadcsv', 'progressreview_additionalcourses'));
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
                    throw new Exception(get_string('cantreadcsv', 'progressreview_additionalcourses'));
                }
                $file = reset($files);
                if (!$fh = $file->get_content_file_handle()) {
                    throw new Exception(get_string('cantreadcsv', 'progressreview_additionalcourses'));
                }
            }

            $currentrecords = $DB->get_records('progressreview_addition');
            foreach ($currentrecords as $currentrecord) {
                $currentrecord->active = 0;
                $DB->update_record('progressreview_addition', $currentrecord);
            }

            $errparams = new stdClass;
            $errparams->line = 1;
            while ($row = fgetcsv($fh)) {
                $errparams->num = count($row);
                if ($errparams->num != 2) {
                    $error = get_string('wrongcolcount',
                                        'progressreview_additionalcourses',
                                        $errparams);
                    throw new progressreview_invalidvalue_exception($error);
                }

                $record = (object)array(
                    'name' => $row[0],
                    'code' => $row[1],
                );
                $DB->insert_record('progressreview_addition', $record);
                $errparams->line++;
            }
        }
    }
}
