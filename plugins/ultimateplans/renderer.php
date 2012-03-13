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

class progressreview_ultimateplans_print_renderer extends plugin_print_renderer_base {
    public function review($ultimateplan) {
        if ($ultimateplan) {
            $this->output->heading(get_string('pluginname', 'progressreview_ultimateplans'), 4);
            $options = array('font' => (object)array('size' => 12));
            pdf_writer::div($ultimateplan->plan, $options);
            pdf_writer::div($ultimateplan->comments);
            return pdf_writer::$pdf;
        }
    }
}
