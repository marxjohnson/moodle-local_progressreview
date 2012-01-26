<?php

/**
 * class progressreview_tutor
 * Interface for the tutor review
 */
class progressreview_tutor extends progressreview_plugin {

    /** Aggregations: */

    /** Compositions: */

     /*** Attributes: ***/

    /**
     * The ID of the review record from progressreview_tutor
     * @access public
     */
    public $id;

    protected $name = 'tutor';

    static public $type = PROGRESSREVIEW_TUTOR;

    /**
     * Reference to the progressreview that this plugin belongs to.
     * @access private
     */
    protected $progressreview;

    /**
     * The tutor's comments
     * @access private
     */
    protected $comments;

    protected $valid_properties = array('reviewid', 'comments');
    /**
     * Stores a reference to the progressreview object
     *
     * @param progressreview review

     * @return
     * @access public
     */
    public function __construct($review) {
        $this->progressreview = $review;
        $this->retrieve_review();
    } // end of member function __construct

    /**
     * Returns an object containing the data entered for the review
     *
     * @return
     * @access public
     */
    public function get_review() {
        return (object)array(
            'id' => $this->id,
            'progressreview' => $this->progressreview,
            'comments' => $this->comments
        );
    } // end of member function get_review

    public function delete() {
        global $DB;
        $DB->delete_records('progressreview_tutor', array('id' => $this->id));
    }

    /**
     * Sets $id and $comments based on the values in progressreview_tutor, or creates a
     * new record and just sets $id.
     *
     * @return
     * @access public
     */
    protected function retrieve_review() {
        global $DB;

        if ($review = $DB->get_record('progressreview_tutor', array('reviewid' => $this->progressreview->id))) {
            $this->id = $review->id;
            $this->comments = $review->comments;
            return $review;
        } else {
            return $this->id = $DB->insert_record('progressreview_tutor', (object)array('reviewid' => $this->progressreview->id));
        }
    } // end of member function retrieve_review

    public function add_form_fields(&$mform) {
        $mform->addElement('textarea',
                           'comments',
                           get_string('comments', 'local_progressreview'),
                           array('rows' => 5, 'cols' => 50, 'class' => 'tutor'));

    }

    public function process_form_fields($data) {
        $review = (object)array(
            'comments' => $data->comments,
            'reviewid' => $data->reviewid
        );
        return $this->update($review);
    }

    public function add_form_data($data) {
        $data->comments = $this->comments;
        return $data;
    }

    public function require_js() {
        global $PAGE;
        $jsmodule = array(
            'name' => 'progressreview_tutor',
            'fullpath' => '/local/progressreview/plugins/tutor/module.js',
            'requires' => array('base', 'node'),
            'strings' => array(
                array('didntattend', 'progressreview_tutor'),
                array('didntattendfiller', 'progressreview_tutor')
            )
        );
        $PAGE->requires->js_init_call('M.progressreview_tutor.init', null, false, $jsmodule);
    }

} // end of progressreview_tutor
