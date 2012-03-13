<?php

class progressreview_ultimateplans extends progressreview_plugin_tutor {

    protected $name = 'ultimateplans';

    static public $type = PROGRESSREVIEW_TUTOR;

    protected $valid_properties = array('id', 'reviewid', 'ultplanid', 'comments');

    private $ultimateplan;

    public function update($plan) {
        global $DB;
        $plan = $this->filter_properties($plan);

        if ($plan->ultplanid == 0) {
            if (!empty($this->ultimateplan)) {
                $params = array('id' => $this->ultimateplan->id);
                if ($DB->delete_records('progressreview_ultplan_sel', $params)) {
                    $this->ultimateplan = null;
                }
            }
        } else {
            if (empty($plan->id)) {
                $plan->id = $DB->insert_record('progressreview_ultplan_sel', $plan);
            } else {
                $DB->update_record('progressreview_ultplan_sel', $plan);
            }
            foreach ((array)$plan as $field => $datum) {
                $this->ultimateplan->$field = $datum;
            }
        }

        $this->update_timestamp();

        return true;
    }

    public function get_review() {
        global $DB;
        if ($this->ultimateplan) {
            $ultplans = $DB->get_records_menu('progressreview_ultplan');
            $plan = (object)array(
                'plan' => $ultplans[$this->ultimateplan->ultplanid],
                'comments' => $this->ultimateplan->comments
            );
            return $plan;
        } else {
            return false;
        }
    }

    public function delete() {
        global $DB;
        if (!empty($this->ultimateplan->id)) {
            $params = array('id' => $this->ultimateplan->id);
            return $DB->delete_records('progressreview_ultplan_sel', $params);
        }
    }

    public function retrieve_review() {
        global $DB;
        $params = array('reviewid' => $this->progressreview->id);
        $this->ultimateplan = $DB->get_record('progressreview_ultplan_sel', $params);
    }

    public function add_form_fields($mform) {
        global $DB;
        $tutormask = get_config('progressreview_intentions', 'tutormask');
        if (empty($tutormask) || preg_match($tutormask, $this->progressreview->get_course()->shortname) > 0) {
            $options = $DB->get_records_menu('progressreview_ultplan', array(), 'description');
            $options = array(get_string('choosedots')) + $options;
            $attrs = array('class' => 'ultimateplan');
            $strultimateplan = get_string('ultimateplan', 'progressreview_ultimateplans');
            $strcomments = get_string('comments', 'progressreview_ultimateplans');
            $strquestion = get_string('question', 'progressreview_ultimateplans');
            $mform->addElement('static', 'question', '', $strquestion);
            $mform->addElement('select', 'ultimateplan', $strultimateplan, $options, $attrs);
            $attrs['rows'] = 4;
            $attrs['cols'] = 50;
            $mform->addElement('textarea', 'ultimateplan_comments', $strcomments, $attrs);
            $mform->addHelpButton('ultimateplan_comments', 'comments', 'progressreview_ultimateplans');
            $mform->setType('ultimateplan', PARAM_INT);
            $mform->setType('ultimateplan_comments', PARAM_TEXT);
            $mform->disabledIf('ultimateplan_comments', 'ultimateplan', 'eq', 0);
        } else {
            $mform->addElement('static', 'notrequired', '', get_string('notrequired', 'progressreview_intentions'));
        }
    }

    public function add_form_data($data) {
        if ($this->ultimateplan) {
            $data->ultimateplan = $this->ultimateplan->ultplanid;
            $data->ultimateplan_comments = $this->ultimateplan->comments;
        }
        return $data;
    }

    public function validate($data) {
        if (!empty($data->ultimateplan) && empty($data->ultimateplan_comments)) {
            $error = get_string('noblankcomments', 'progressreview_ultimateplans');
            throw new progressreview_invalidvalue_exception($error);
        }
    }

    public function process_form_fields($data) {
        $this->validate($data);
        if (isset($data->ultimateplan)) {
            $ultimateplan = array(
                'reviewid' => $this->progressreview->id,
                'ultplanid' => $data->ultimateplan
            );
            if (!empty($data->ultimateplan_comments)) {
                $ultimateplan['comments'] = $data->ultimateplan_comments;
            }

            if (!empty($this->ultimateplan->id)) {
                $ultimateplan['id'] = $this->ultimateplan->id;
            }

            return $this->update($ultimateplan);
        }
        return true;
    }

    public function autosave($field, $value) {
        if ($field == 'ultimateplan') {
            $data = json_decode($value);
            $this->validate($data);

            $ultimateplan = array(
                'reviewid' => $this->progressreview->id,
                'ultplanid' => $data->ultimateplan,
                'comments' => $data->ultimateplan_comments
            );

            if (!empty($this->ultimateplan)) {
                $ultimateplan['id'] = $this->ultimateplan->id;
            }

            return $this->update($ultimateplan);
        }
        return true;
    }
}
