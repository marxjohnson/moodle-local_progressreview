<?php

class progressreview_alternativeplans extends progressreview_plugin_tutor {

    protected $name = 'alternativeplans';

    static public $type = PROGRESSREVIEW_TUTOR;

    protected $valid_properties = array('id', 'reviewid', 'altplanid', 'comments');

    private $alternativeplan;

    public function update($plan) {
        global $DB;
        $plan = $this->filter_properties($plan);

        if ($plan->altplanid == 0) {
            if (!empty($this->alternativeplan)) {
                $params = array('id' => $this->alternativeplan->id);
                if ($DB->delete_records('progressreview_altplan_sel', $params)) {
                    $this->alternativeplan = null;
                }
            }
        } else {
            if (empty($plan->id)) {
                $plan->id = $DB->insert_record('progressreview_altplan_sel', $plan);
            } else {
                $DB->update_record('progressreview_altplan_sel', $plan);
            }
            foreach ((array)$plan as $field => $datum) {
                $this->alternativeplan->$field = $datum;
            }
        }

        $this->update_timestamp();

        return true;
    }

    public function get_review() {
        return $this->alternativeplan;
    }

    public function delete() {
        global $DB;
        if (!empty($this->alternativeplan->id)) {
            $params = array('id' => $this->alternativeplan->id);
            return $DB->delete_records('progressreview_altplan_sel', $params);
        }
    }

    public function retrieve_review() {
        global $DB;
        $params = array('reviewid' => $this->progressreview->id);
        $this->alternativeplan = $DB->get_record('progressreview_altplan_sel', $params);
    }

    public function add_form_fields($mform) {
        global $DB;
        $tutormask = get_config('progressreview_intentions', 'tutormask');
        if (empty($tutormask) || preg_match($tutormask, $this->progressreview->get_course()->shortname) > 0) {
            $options = $DB->get_records_menu('progressreview_altplan', array(), 'description');
            array_unshift($options, get_string('choosedots'));
            $attrs = array('class' => 'alternativeplan');
            $stralternativeplan = get_string('alternativeplan', 'progressreview_alternativeplans');
            $strcomments = get_string('comments', 'progressreview_alternativeplans');
            $strquestion = get_string('question', 'progressreview_alternativeplans');
            $mform->addElement('static', 'question', '', $strquestion);
            $mform->addElement('select', 'alternativeplan', $stralternativeplan, $options, $attrs);
            $attrs['rows'] = 4;
            $attrs['cols'] = 50;
            $mform->addElement('textarea', 'alternativeplan_comments', $strcomments, $attrs);
            $mform->addHelpButton('alternativeplan_comments', 'comments', 'progressreview_alternativeplans');
            $mform->setType('alternativeplan', PARAM_INT);
            $mform->setType('alternativeplan_comments', PARAM_TEXT);
            $mform->disabledIf('alternativeplan_comments', 'alternativeplan', 'eq', 0);
        } else {
            $mform->addElement('static', 'notrequired', '', get_string('notrequired', 'progressreview_intentions'));
        }
    }

    public function add_form_data($data) {
        if ($this->alternativeplan) {
            $data->alternativeplan = $this->alternativeplan->altplanid;
            $data->alternativeplan_comments = $this->alternativeplan->comments;
        }
        return $data;
    }

    public function validate($data) {
        if (!empty($data->alternativeplan) && empty($data->alternativeplan_comments)) {
            $error = get_string('noblankcomments', 'progressreview_alternativeplans');
            throw new progressreview_invalidvalue_exception($error);
        }
    }

    public function process_form_fields($data) {
        $this->validate($data);
        if (isset($data->alternativeplan)) {
            $alternativeplan = array(
                'reviewid' => $this->progressreview->id,
                'altplanid' => $data->alternativeplan
            );
            if (!empty($data->alternativeplan_comments)) {
                $alternativeplan['comments'] = $data->alternativeplan_comments;
            }

            if (!empty($this->alternativeplan->id)) {
                $alternativeplan['id'] = $this->alternativeplan->id;
            }

            return $this->update($alternativeplan);
        }
        return true;
    }

    public function autosave($field, $value) {
        if ($field == 'alternativeplan') {
            $data = json_decode($value);
            $this->validate($data);

            $alternativeplan = array(
                'reviewid' => $this->progressreview->id,
                'altplanid' => $data->alternativeplan,
                'comments' => $data->alternativeplan_comments
            );

            if (!empty($this->alternativeplan)) {
                $alternativeplan['id'] = $this->alternativeplan->id;
            }

            return $this->update($alternativeplan);
        }
        return true;
    }
}
