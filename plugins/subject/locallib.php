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
    protected function retrieve_attendance() {
        $attendance = new stdClass;
        $attendance->attendance = 0;
        $attendance->punctuality = 0;
        return $attendance;
    }


}
