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
 * Defines the course selector fields used for adding reviews to courses
 *
 * By overriding and massaging user_selector_base, the class is repurposed to allow selecting
 * of courses instead of users
 *
 * @package   local_progressreview
 * @copyright 2011 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
            $options['file'] = 'local/progressreview/course_selector.php';
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
        private $reviewtype;

        public function __construct($name, $sessionid, $reviewtype = PROGRESSREVIEW_SUBJECT, $extraoptions = array()) {
            parent::__construct($name, $extraoptions);
            $this->sessionid = $sessionid;
            $this->reviewtype = $reviewtype;
        }

        public function find_users($search = '') {
            global $DB;
            $options = array(get_string('courseswithreviews', 'local_progressreview') => array());
            $reviewcourses = $DB->get_records_sql('SELECT DISTINCT courseid FROM {progressreview} WHERE sessionid = ? AND reviewtype = ?', array($this->sessionid, $this->reviewtype));
            foreach($reviewcourses as $reviewcourse) {
                $select = 'SELECT c.id, c.shortname AS lastname, "" AS firstname, c.fullname AS email, cc.name AS category ';
                $from = 'FROM {course} c JOIN {course_categories} cc on c.category = cc.id ';
                $where = 'WHERE c.id = ?';
                $params = array($reviewcourse->courseid);
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
                if ($course = $DB->get_record_sql($select.$from.$where, $params)) {
                    if (!isset($options[$course->category])) {
                        $options[$course->category] = array();
                    }
                    $options[$course->category][$course->id] = $course;
                }
            }
            return $options;
        }
}

