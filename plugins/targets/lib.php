<?php


class progressreview_targets extends progressreview_plugin {

    /** Aggregations: */

    /** Compositions: */

     /*** Attributes: ***/

    protected $name = 'targets';

    static public $type = PROGRESSREVIEW_TUTOR;

    protected $valid_properties = array(
        'id',
        'name',
        'targetset',
        'deadline',
        'timecreated',
        'timemodified',
        'setforuserid',
        'setbyuserid'
    );
    /**
     * snapshot of records from ilptarget_posts for each target
     * @access private
     */
    private $targets;

    public function update($targets) {
        global $DB;
        foreach ($targets as $number => $target) {
            if (is_object($target)) {
                $target = (array)$target;
            }

            foreach ($target as $field => $datum) {
                if(!in_array($field, $this->valid_properties)) {
                    $target[$field] = false;
                }
            }

            $target = (object)array_filter($target, function($datum) {
                return $datum !== false;
            });

            if (!empty($target->id)) {
                if (!$DB->update_record('ilptarget_posts', $target)) {
                    throw new progressreview_autosave_exception('Target Update Failed');
                }
                if (!$DB->set_field('progressreview', 'datemodified', time(), array('id' => $this->progressreview->id))) {
                    throw new progressreview_autosave_exception('Timestamp Update Failed');
                }
            } else {
                $target->data1 = $DB->sql_empty();
                $target->data2 = $DB->sql_empty();
                $target->id = $DB->insert_record('ilptarget_posts', $target);

                if ($target->id) {
                    if (!$DB->set_field('progressreview', 'datemodified', time(), array('id' => $this->progressreview->id))) {
                        throw new progressreview_autosave_exception('Timestamp Update Failed');
                    }
                    $insert = (object)array('targetid' => $target->id, 'reviewid' => $this->progressreview->id);
                    if (!$DB->insert_record('progressreview_targets', $insert)) {
                        throw new progressreview_autosave_exception('Target Creation Failed');
                    }
                }
            }
            foreach ((array)$target as $field => $datum) {
                $this->target[$number]->$field = $datum;
            }
        }
    } // end of member function update

    /**
     * Return the targets' data as an array of records
     *
     * @return
     * @access public
     */
    public function get_review() {
        return $this->targets;
    } // end of member function get_targets

    public function delete() {
        global $DB;
        foreach ($this->targets as $target) {
            $DB->delete_records('progressreview_tutor', array('id' => $target->id));
        }
    }

    protected function retrieve_review() {
        global $DB;
        $select = 'SELECT ip.* ';
        $from = 'FROM {ilptarget_posts} ip
        	JOIN {progressreview_targets} pt ON ip.id = pt.targetid ';
        $where = 'WHERE pt.reviewid = ?';
        $this->targets = array_merge($DB->get_records_sql($select.$from.$where, array($this->progressreview->id)));
    }

    public function add_form_fields(&$mform) {
        $count = 0;
        while ($count < 3) {
            $count++;
            $mform->addElement('textarea',
                               'targets['.($count-1).']',
                               get_string('name', 'ilptarget').' '.$count,
                               array('rows' => 3, 'cols' => 50, 'class' => 'targets'));
            $years = array(
                'startyear' => date('Y'),
                'stopyear' => date('Y', strtotime('next year'))
            );
            $mform->addElement('date_selector',
                               'deadlines['.($count-1).']',
                               get_string('deadline', 'ilptarget').' '.$count,
                               $years,
                               array('class' => 'targets deadline'.($count-1)));
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

    public function autosave($field, $value) {
        try {
            $number = substr($field, -1);
            $field1 = substr($field, 0, -1);
            if ($field1 == 'targets') {
                $field1 = 'targetset';
                $field2 = 'deadline';
                $value2 = strtotime('2 weeks');
            } else {
                $field1 = 'deadline';
                $field2 = 'targetset';
                $value2 = '';
            }
            $targets = array();
            if (!empty($this->targets[$number])) {
                $update = (object)array(
                    'id' => $this->targets[$number]->id,
                    'timemodified' => time(),
                    $field1 => $value
                );
                $targets[$number] = $update;
            } else {
                if (!empty($value)) {
                    $newtarget = (object)array(
                        'timecreated' => time(),
                        'timemodified' => time(),
                        'setforuserid' => $this->progressreview->get_student()->id,
                        'setbyuserid' => $this->progressreview->get_teacher()->originalid
                    );
                    $newtarget->$field1 = $value;
                    $newtarget->$field2 = $value2;
                    $targets[$number] = $newtarget;
                }
            }

            $this->update($targets);
        } catch (progressreview_invalidfield_exception $e) {
            throw $e;
        } catch (dml_write_exception $e) {
            throw $e;
        } catch (progressreview_autosave_exception $e) {
            throw $e;
        }
    }


} // end of progressreview_targets
