<?php
class progressreview_additionalcourses_renderer extends plugin_renderer_base {
    public function review($additionalcourses) {
        if ($additionalcourses) {
            $courses = array();
            $output = $this->output->heading(get_string('pluginname', 'progressreview_additionalcourses'), 2);
            foreach ($additionalcourses as $additionalcourse) {
                $courses[] = $additionalcourse;
            }
            $output .= html_writer::alist($courses);
            return $output;
        }
    }
}

class progressreview_additionalcourses_print_handler extends plugin_print_renderer_base {
    public function review($additionalcourses) {
        $courses = array();
        $this->output->heading(get_string('pluginname', 'progressreview_additionalcourses'), 2);
        foreach ($additionalcourses as $additionalcourse) {
            pdf_writer::div($additionalcourse);
        }
        return pdf_writer::$pdf;
    }
}
