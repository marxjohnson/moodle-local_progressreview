<?php

/**
 * Child of progressreview_subject_template, allowing methods to be customised
 *
 * Since methods for determining statistics are likely to differ between institutions,
 * {@see progressreview_subject_template} leaves some methods undefined.
 *
 * Sensible defaults are defined for these functions here, but this file is intended
 * for customisation.
 *
 */
class progressreview_subject extends progressreview_subject_template {

    /**
     * Return the attendance as a percentage.
     *
     * @todo Modify to use the attendance module by default
     */
    private function retrieve_attendance() {
        return 0;
    }

    /**
     * Return the punctuality as a percentage.
     *
     * @todo Modify to use attendacne module by default.
     */
    private function retrieve_punctality() {
        return 0;
    } 

    private function retrieve_performancegrade() {
        return 0;
    }
}
