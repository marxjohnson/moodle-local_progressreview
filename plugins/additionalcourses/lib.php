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
 * Defines the plugin class for the additional courses plugin
 *
 * @package   local_progressreview
 * @subpackage progressreview_additionalcourses
 * @copyright 2012 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die();
}

class progressreview_additionalcourses extends progressreview_plugin_tutor {

    protected $name = 'additionalcourses';

    static public $type = PROGRESSREVIEW_TUTOR;

    protected $valid_properties = array('id', 'reviewid', 'additionid');

    private $additions;

    public function update($additions) {
        global $DB;

        foreach ($additions as $key => $addition) {
            $addition = $this->filter_properties($addition);

            if (!empty($addition->id)) {
                if ($addition->additionid) {
                    if (!$DB->update_record('progressreview_addition_sel', $addition)) {
                        throw new progressreview_autosave_exception('Additional Course Update Failed');
                    }
                    $this->additions[$key]->additionid = $addition->additionid;
                } else {
                    $DB->delete_records('progressreview_addition_sel', array('id' => $key));
                    unset($this->additions[$key]);
                }
                $this->update_timestamp();
            } else {
                $addition->id = $DB->insert_record('progressreview_addition_sel', $addition);

                if ($addition->id) {
                    $this->update_timestamp();
                }
                $this->additions[$key] = $addition;
            }

        }
        return true;
    }

    public function get_review() {
        global $DB;
        if ($this->additions) {
            $courses = array();
            $additionalcourses = $DB->get_records_menu('progressreview_addition', array('active' => 1));
            foreach ($this->additions as $addition) {
                $courses[] = $additionalcourses[$addition->additionid];
            }
            return $courses;
        } else {
            return false;
        }
    }

    public function delete() {
        global $DB;
        foreach ($this->additions as $addition) {
            $DB->delete_records('progressreview_addition_sel', array('id' => $addition->id));
        }
    }

    protected function retrieve_review() {
        global $DB;
        $params = array('reviewid' => $this->progressreview->id);
        $this->additions = array();
        $records = $DB->get_records('progressreview_addition_sel', $params);
        foreach ($records as $record) {
            $this->additions[] = $record;
        }
    }

    public function add_form_fields($mform) {
        global $DB;
        $tutormask = get_config('progressreview_intentions', 'tutormask');
        if (empty($tutormask) || preg_match($tutormask, $this->progressreview->get_course()->shortname) > 0) {

            $attrs = array('class' => 'additionalcourse');
            $options = $DB->get_records_menu('progressreview_addition', array('active' => 1), 'name');
            $options = array(get_string('choosedots')) + $options;
            $stradditional = get_string('additionalcourse', 'progressreview_additionalcourses');
            for ($i=0, $j=1; $i<$j; $i++) {
                $name = 'additionalcourse['.$i.']';
                $mform->addElement('select', $name, $stradditional, $options, $attrs);
                $mform->addHelpButton($name, 'additionalcourse', 'progressreview_additionalcourses');
                $mform->setType($name, PARAM_INT);
            }
        } else {
            $mform->addElement('static', 'notrequired', '', get_string('notrequired', 'progressreview_intentions'));
        }
    }

    public function add_form_data($data) {
        $data->additionalcourse = array();
        if (!empty($this->additions)) {
            foreach ($this->additions as $addition) {
                $data->additionalcourse[] = $addition->additionid;
            }
        }
        return $data;
    }

    public function process_form_fields($data) {

        if (isset($data->additionalcourse)) {
            $additions = array();
            if (!empty($this->additions)) {
                foreach ($this->additions as $addition) {
                    $key = $addition->id;
                    if (array_key_exists($key, $data->additionalcourse)) {
                        $additions[$key] = clone($addition);
                        $additions[$key]->additionid = $data->additionalcourse[$key];
                        unset($data->additionalcourse[$key]);
                    }
                }
            }
            foreach ($data->additionalcourse as $additionalcourse) {
                $additions[] = array(
                    'reviewid' => $this->progressreview->id,
                    'additionid' => $additionalcourse
                );
            }
            return $this->update($additions);
        } else {
            return true;
        }
    }

    public function autosave($field, $value) {

        $fieldparts = explode('_', $field);
        $additions = array();

        $key = $fieldparts[2];
        if (array_key_exists($key, $this->additions)) {
            $additions[$key] = clone($this->additions[$key]);
            $additions[$key]->additionid = $value;
        } else {
            $additions[$key] = array(
                'reviewid' => $this->progressreview->id,
                'additionid' => $value
            );
        }

        return $this->update($additions);
    }

}
