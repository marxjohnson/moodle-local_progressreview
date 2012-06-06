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
 * Defines a template for the plugin's main class
 *
 * @package   local_progressreview
 * @subpackage progressreview_reference
 * @copyright 2011 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * class progressreview_reference
 *
 */
class progressreview_reference extends progressreview_plugin_subject {
    /*** Attributes: ***/

    /**
     * The ID of the review's record in the progressreview_ref table
     * @access public
     */
    public $id;

    protected $name = 'reference';

    static public $type = PROGRESSREVIEW_SUBJECT;

    protected $valid_properties;

    /**
     * A reference to the progressreview object for the review that this reference
     * belongs to.
     * @access private
     */
    protected $progressreview;

    /**
     * The comments entered for this review
     * @access private
     */
    public $reference;

    /**
     * Initialises the object by storing a reference to the progressreview object and
     * calling retrieve_review()
     *
     * @param progressreview review A reference to the progressreview object that this plugin belongs to.
     * @return
     * @access public
     */
    public function __construct(&$review) {
        $this->valid_properties = array(
            'reviewid',
            'reference',
        );
        $this->progressreview = $review;
        $this->retrieve_review();
    } // end of member function __construct

    /**
     * Returns an object containing the current review statistics and comments.
     *
     * @return
     * @access public
     */
    public function get_review() {
        return (object)array(
            'id' => $this->id,
            'progressreview' => $this->progressreview,
            'reference' => $this->reference
        );
    } // end of member function get_review

    public function delete() {
        global $DB;
        $DB->delete_records('progressreview_reference', array('id' => $this->id));
    }

    /**
     * Updates the attributes with the passed values and saves the values to the
     * database.
     *
     * @param stdClass|array $data
     * @access public
     */
    public function update($data) {
        global $DB;

        $data = $this->filter_properties($data);

        if (empty($data)) {
            throw new progressreview_invalidfield_exception('Invalid Field Name');
        }

        if (!empty($this->id)) {
            $data->id = $this->id;
            $result = $DB->update_record('progressreview_reference', $data);
            $params = array('id' => $this->progressreview->id);
            $DB->set_field('progressreview', 'datecreated', time(), $params);
        } else {
            $result = $this->id = $DB->insert_record('progressreview_reference', $data);
            $params = array('id' => $this->progressreview->id);
            $DB->set_field('progressreview', 'datemodified', time(), $params);
        }
        return $result;
    } // end of member function update

    /*
     * Generates a basic review containing any statistics that can be determined from
     * the database.
     *
     * @return
     * @access private
     */
    protected function skeleton_review() {
        $skeleton = array();
        $skeleton['reviewid'] = $this->progressreview->id;
        $this->update($skeleton);

    } // end of member function skeleton_review

    /**
     * Retrieves the current review from the database, or generates one if required.
     *
     * @return
     * @access protected
     */
    protected function retrieve_review() {
        global $DB;
        $params = array('reviewid' => $this->progressreview->id);
        if ($review = $DB->get_record('progressreview_reference', $params)) {
            foreach ((array)$review as $property => $value) {
                if ($property != 'reviewid') {
                    $this->$property = $value;
                }
            }
        } else {
            $this->skeleton_review();
        }
    } // end of member function retrieve_review

    public function get_scaleid() {
        return $this->scaleid;
    }

    public function clean_params($post) {
        $cleaned = array(
            'reference' => null
        );
        foreach ($cleaned as $field => $data) {
            if (!empty($post[$field])) {
                $cleaned[$field] = clean_param($post[$field], PARAM_INT);
            }
        }
        return $cleaned;
    }

    public function process_form_fields($data) {
        global $DB;
        $this->validate($data);
        if ($this->update($data)) {
            return true;
        } else {
            return false;
        }
    }

    public function add_form_rows() {
        global $OUTPUT;

        $rows = array();

        $fieldarray = 'review['.$this->progressreview->id.']';
        $referenceattrs = array(
            'class' => 'reference',
            'name' => $fieldarray.'[reference]'
        );
        $referencefield = html_writer::tag('textarea', $this->reference, $referenceattrs);
        $referencecell = new html_table_cell($referencefield);
        $strreference = get_string('reference', 'progressreview_reference');
        $helpicon = $OUTPUT->help_icon('reference', 'progressreview_reference');
        $headercell = new html_table_cell($strreference.':'.$helpicon);
        $headercell->header = true;

        $referencecell->colspan = 8;
        $row = new html_table_row(array('', $headercell, $referencecell));
        $rows[] = $row;

        return $rows;
    }

    public function add_table_rows($displayby, $showincomplete = true, $html = true) {
        global $OUTPUT;
        $rows = array();
        $row = new html_table_row();

        $inductionreview = $this->progressreview->get_session()->inductionreview;

        if (!$showincomplete && empty($this->reference)) {
            return $rows;
        }

        if ($html) {
            $this->reference = str_replace("\n", "<br />", $this->reference);
        }
        $referencecell = new html_table_cell($this->reference);
        $strreference = get_string('reference', 'progressreview_reference');
        $headercell = new html_table_cell($strreference.':');
        $headercell->header = true;

        $referencecell->colspan = 7;
        $row = new html_table_row(array('', $headercell, $referencecell));
        $rows[] = $row;

        return $rows;

    }

} // end of progressreview_subject
