<?php
class progressreview_alternativeplans_renderer extends plugin_renderer_base {
    public function review($alternativeplan) {
        if ($alternativeplan) {
            $output = $this->output->heading(get_string('pluginname', 'progressreview_alternativeplans'), 2);
            $output .= $this->output->heading($alternativeplan->plan, 4);
            $output .= html_writer::tag('p', $alternativeplan->comments);
            return $output;
        }
    }
}

class progressreview_alternativeplans_print_renderer extends plugin_print_renderer_base {
    public function review($alternativeplan) {
        if ($alternativeplan) {
            $this->output->heading(get_string('pluginname', 'progressreview_alternativeplans'), 4);
            $options = array('font' => (object)array('size' => 12));
            pdf_writer::div($alternativeplan->plan, $options);
            pdf_writer::div($alternativeplan->comments);
            return pdf_writer::$pdf;
        }
    }
}
