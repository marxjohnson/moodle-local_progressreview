<?php

if (!defined('MOODLE_INTERNAL')) {
    die();
}

class progressreview_additionalcourses extends progressreview_plugin_tutor {

    protected $name = 'additionalcourses';

    static public $type = PROGRESSREVIEW_TUTOR;

    protected $valid_properties = array('id', 'reviewid', 'additionid');

    private $additions;

    public function __construct(&$review) {
        parent::__construct($review);
    }

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
        return $this->additions;
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
            array_unshift($options, get_string('choosedots'));
            $stradditional = get_string('additionalcourse', 'progressreview_additionalcourses');
            for ($i=0,$j=1;$i<$j;$i++) {
                $mform->addElement('select', 'additionalcourse['.$i.']', $stradditional, $options, $attrs);
                $mform->addHelpButton('additionalcourse['.$i.']', 'additionalcourse', 'progressreview_additionalcourses');
                $mform->setType('additionalcourse['.$i.']', PARAM_INT);
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
