<?php

class progressreview_tutor_renderer extends plugin_renderer_base {
    public function review($reviewdata) {
        $output = $this->output->heading(get_string('comments', 'local_progressreview'), 4);
        $output .= html_writer::tag('p', str_replace("\n", "<br />", $reviewdata->comments));
        return $output;
    }
}
