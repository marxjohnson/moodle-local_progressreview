<?php
class progressreview_ultimateplans_renderer extends plugin_renderer_base {
    public function review($ultimateplan) {
        if ($ultimateplan) {
            $output = $this->output->heading(get_string('pluginname', 'progressreview_ultimateplans'), 2);
            $output .= $this->output->heading($ultimateplan->plan, 4);
            $output .= html_writer::tag('p', $ultimateplan->comments);
            return $output;
        }
    }
}

class progressreview_ultimateplans_print_handler extends plugin_print_renderer_base {
    public function review($ultimateplans) {
        if ($ultimateplan) {
            $this->output->heading(get_string('pluginname', 'progressreview_ultimateplans'), 4);
            pdf_writer::div($ultimateplan->plan);
            pdf_writer::div($ultimateplan->comments);
            return pdf_writer::$pdf;
        }
    }
}
