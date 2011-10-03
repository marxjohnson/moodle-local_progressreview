<?php


class progressreview_targets extends progressreview_plugin {

    /** Aggregations: */

    /** Compositions: */

     /*** Attributes: ***/

    protected $name = 'targets';

    protected $type = PROGRESSREVIEW_TUTOR;

    protected $valid_properties = array(
        'id',
        'name',
        'targetset',
        'deadline',
        'datecreated',
        'datemodified',
        'setforuserid',
        'setbyuserid'
    );
    /**
     * snapshot of records from ilptarget_posts for each target
     * @access private
     */
    private $targets;

    /**
     * Return the targets' data as an array of records
     *
     * @return
     * @access public
     */
    public function update($data) {
        global $DB;
        if (is_object($data)) {
            $data = (array)$data;
        }

        foreach ($data as $field => $datum) {
            if(!in_array($field, $this->valid_properties)) {
                $data[$field] = false;
            }
        }

        $data = (object)array_filter($data, function($datum) {
            return $datum !== false;
        });

        if (!empty($data->id)) {
            $result = $DB->update_record('ilptarget', $data);
            $DB->set_field('progressreview', 'datecreated', time(), array('id' => $this->progressreview->id));
        } else {
            $result = $DB->insert_record('ilptarget', $data);
            $data->id = $result;
            $DB->set_field('progressreview', 'datemodified', time(), array('id' => $this->progressreview->id));
            $DB->inset_record('progressreview_targets', (object)array('targetid' => $data->id, 'reviewid' => $this->progressreview->id));
        }
        foreach ((array)$data as $field => $datum) {
            $this->target[$data->id]->$field = $datum;
        }
        return $result;
    } // end of member function update

    public function get_review() {
        return $this->targets;
    } // end of member function get_targets

    protected function retrieve_review() {
        global $DB;
        $select = 'SELECT ip.* ';
        $from = 'FROM {ilptarget_posts} ip
        	JOIN {progressreview_targets} pt ON ip.id = pt.targetid ';
        $where = 'WHERE pt.reviewid = ?';
        $this->targets = $DB->get_records_sql($select.$from.$where, array($this->progressreview->id));
    }

    public function add_form_fields(&$form) {
        $mform =& $form->_form;
        $count = 0;
        foreach($this->targets as $target) {
            $count++;
            $mform->addElement('textarea', 'targets['.$target->id.']', get_string('target', 'ilptarget').' '.$count);
            $mform->addElement('date_selector', 'deadlines['.$target->id.']', get_string('deadline', 'ilptarget').' '.$count);
        }
        while ($count < 3) {
            $count++;
            $mform->addElement('textarea', 'targets[]', get_string('target', 'ilptarget').' '.$count);
            $mform->addElement('date_selector', 'deadlines[]', get_string('target', 'ilptarget').' '.$count);
        }
    }

    public function process_form_fields($data) {
        foreach ($data->targets as $id => $target) {
            if (array_key_exists($id, $this->targets)) {
                $update = (object)array(
                    'id' => $id,
                    'targetset' => $target,
                    'deadline' => $data->deadlines[$id],
                    'timemodified' => time(),
                    'setforuserid' => $this->progressreview->get_student()->id,
                    'setbyuserid' => $this->progressreview->get_teacher()->originalid
                );
                $this->update($update);
            } else {
                $newtarget = (object)array(
                    'targetset' => $target,
                    'deadline' => $data->deadlines[$id],
                    'timecreated' => time(),
                    'timemodified' => time(),
                    'setforuserid' => $this->progressreview->get_student()->id,
                    'setbyuserid' => $this->progressreview->get_teacher()->originalid
                );
                $this->update($newtarget);
            }
        }
    }



} // end of progressreview_targets
