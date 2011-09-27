<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/local/progressreview/lib.php');
require_once($CFG->dirroot.'/user/selector/lib.php');

class progressreview_potential_course_selector extends user_selector_base {

        /**
         * Add the file name to the $options array to make AJAX searching work
         * @return array
         */
        ### @export "pcs_get_options"
        protected function get_options() {
            $options = parent::get_options();
            $options['file'] = 'local/progressreviews/course_selector.php';
            return $options;
        }

        public function find_users($search = '') {
            global $DB;
            $options = array();
            $categories = $DB->get_records('course_categories');
            foreach ($categories as $category) {
                $where = 'category = ?';
                $params = array($category->id);
                if (!empty($search)) {
                    $shortnamelike = $DB->sql_like('shortname', '?');
                    $fullnamelike = $DB->sql_like('fullname', '?');
                    $where .= 'AND ('.$shortnamelike.' OR '.$fullnamelike.') ';
                    $params = array_merge($params, array('%'.$search.'%', '%'.$search.'%'));
                }
                if (!empty($this->exclude)) {
                    list($not_in_sql, $not_in_params) = $DB->get_in_or_equal($this->exclude, SQL_PARAMS_QM, '', false);
                    $where .= 'AND id '.$not_in_sql.' ';
                    $params = array_merge($params, $not_in_params);
                }
                $fields = 'id, shortname AS lastname, "" AS firstname, fullname AS email';
                $courses = $DB->get_records_select('course', $where, $params, 'shortname', $fields);
                $options[$category->name] = $courses;
            }
            return $options;
        }
}

class progressreview_distributed_course_selector extends progressreview_potential_course_selector {

        private $sessionid;

        public function __construct($name, $sessionid, $extraoptions = array()) {
            parent::__construct($name, $extraoptions);
            $this->sessionid = $sessionid;
        }

        public function find_users($search = '') {
            global $DB;
            $options = array(get_string('results') => array());
            $reviewcourses = $DB->get_records_sql('SELECT DISTINCT courseid FROM {progressreview} WHERE sessionid = ?', array($this->sessionid));
            foreach($reviewcourses as $reviewcourse) {
                $concat_sql = $DB->sql_concat('shortname', '"-"', 'fullname');
                $select = 'SELECT c.id, c.shortname AS lastname, "" AS firstname, c.fullname AS email, cc.name AS category ';
                $from = 'FROM {course} c JOIN {course_categories} cc on c.category = cc.id ';
                $where = 'WHERE c.id = ?';
                $params = array($reviewcourse->id);
                if (!empty($search)) {
                    $shortnamelike = $DB->sql_like('shortname', '?');
                    $fullnamelike = $DB->sql_like('fullname', '?');
                    $where .= 'AND ('.$shortnamelike.' OR '.$fullnamelike.') ';
                    $params = array_merge($params, array('%'.$search.'%', '%'.$search.'%'));
                }
                if (!empty($this->exclude)) {
                    list($not_in_sql, $not_in_params) = $DB->get_in_or_equal($this->exclude, SQL_PARAMS_QM, '', false);
                    $where .= 'AND id '.$not_in_sql.' ';
                    $params = array_merge($params, $not_in_params);
                }
                $course = $DB->get_record_sql($select.$from.$where, $params);
                if (!isset($options[$course->category])) {
                    $options[$course->category] = array();
                }
                $options[$course->category][] = $course;
            }
            return $options;
        }
}

