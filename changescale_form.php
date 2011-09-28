<?php
require_once($CFG->libdir.'/formslib.php');

class progressreview_changescale_form extends moodleform {

    public function definition() {
        global $DB;
        $mform =& $this->_form;
        $mform->addElement('hidden', 'sessionid', $this->_customdata['sessionid']);
        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);

        $concat_sql = $DB->sql_concat('name', '" ("', 'scale', '")"');
        $scales = $DB->get_records_menu('scale', array('courseid' => 0), '', 'id, '.$concat_sql.' AS name');
        $mform->addElement('select', 'scaleid', get_string('scale'), $scales);
        $mform->setDefault('scaleid', $this->_customdata['scaleid']);
        $this->add_action_buttons();
    }


}
