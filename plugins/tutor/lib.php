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

    /**
     * Reference to the progressreview that this plugin belongs to.
     * @access private
     */
    protected $progressreview;

    /**
     * The tutor's comments
     * @access private
     */
    private $comments;


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
            $this->comments = $review->comments;
            return $review;
        } else {
            return $this->id = $DB->insert_record('progressreview_tutor', (object)array('reviewid' => $this->progressreview->id));
        }
    } // end of member function retrieve_review

    public function add_form_fields(&$form) {
        $mform =& $form->_form;

        $mform->addElement('textarea', 'comments', get_string('comments', 'local_progressreview'));

    }

    public function process_form_fields($data) {
        $review = (object)array(
            'comments' => $data->comments,
            'reviewid' => $data->progressreview->id
        );
        return $this->update($review);
    }

} // end of progressreview_tutor
