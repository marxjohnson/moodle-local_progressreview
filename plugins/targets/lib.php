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

    public function add_form_fields(&$mform) {
        $count = 0;
        while ($count < 3) {
            $count++;
            $mform->addElement('textarea', 'targets['.($count-1).']', get_string('name', 'ilptarget').' '.$count, array('rows' => 3, 'cols' => 50));
            $years = array(
                'startyear' => date('Y'),
                'stopyear' => date('Y', strtotime('next year'))
            );
            $mform->addElement('date_selector', 'deadlines['.($count-1).']', get_string('deadline', 'ilptarget').' '.$count, $years);
            $mform->setDefault('deadlines['.($count-1).']', strtotime('3 weeks'));
        }
    }

    public function process_form_fields($data) {
        $targets = array();

        $data->deadlines = array();
        for ($i=0,$j=3; $i<$j; $i++) {
            $fieldname = 'deadlines['.$i.']';
            $data->deadlines[$i] = $data->$fieldname;
            unset($data->$fieldname);
        }

        foreach ($data->targets as $number => $target) {
            if (!empty($this->targets[$number])) {
                $update = (object)array(
                    'id' => $this->targets[$number]->id,
                    'targetset' => $target,
                    'deadline' => $data->deadlines[$number],
                    'timemodified' => time(),
                    'setforuserid' => $this->progressreview->get_student()->id,
                    'setbyuserid' => $this->progressreview->get_teacher()->originalid
                );
                $targets[$number] = $update;
            } else {
                if (!empty($target)) {
                    $newtarget = (object)array(
                        'targetset' => $target,
                        'deadline' => $data->deadlines[$number],
                        'timecreated' => time(),
                        'timemodified' => time(),
                        'setforuserid' => $this->progressreview->get_student()->id,
                        'setbyuserid' => $this->progressreview->get_teacher()->originalid
                    );
                    $targets[$number] = $newtarget;
                }
            }
        }
        $this->update($targets);
    }

    public function add_form_data($data) {
        $targets = array();
        $deadlines = array();
        foreach ($this->targets as $number => $target) {
            $targets[$number] = $target->targetset;
            $fieldname = 'deadlines['.$number.']';
            $$fieldname = array(
                'day' => date('d', $target->deadline),
                'month' => date('m', $target->deadline),
                'year' => date('Y', $target->deadline)
            );
            $$fieldname = $target->deadline;
            $data->$fieldname = $$fieldname;
        }
        $data->deadlines = $deadlines;
        $data->targets = $targets;
        return $data;
    }


} // end of progressreview_targets
