<?php

class progressreview_tutor_renderer extends plugin_renderer_base {
    public function review($reviewdata) {
        $output = $this->output->heading(get_string('comments', 'local_progressreview'), 2);
        $output .= html_writer::tag('p', str_replace("\n", "<br />", $reviewdata->comments));
        return $output;
    }
}

class progressreview_tutor_print_renderer extends plugin_print_renderer_base {

    public function review($reviewdata) {
        $this->output->heading(get_string('comments', 'local_progressreview'), 4);
        $options = array('font' => (object)array('size' => 12));
        return pdf_writer::div($reviewdata->comments, $options);
    }
}
