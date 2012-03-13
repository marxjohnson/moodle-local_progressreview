<?php
class progressreview_referral_renderer extends plugin_renderer_base {
    public function review($referral) {
        if ($referral) {
            $output = $this->output->heading(get_string('pluginname', 'progressreview_referral'), 2);
            $output .= $this->output->heading(fullname($referral->user), 4);
            $output .= html_writer::tag('p', $referral->message);
            return $output;
        }
    }
}

class progressreview_referral_print_handler extends plugin_print_renderer_base {
    public function review($referral) {
        if ($referral) {
            $this->output->heading(get_string('pluginname', 'progressreview_referral'), 2);
            $this->output->heading(fullname($referral->user), 4);
            pdf_writer::div($referral->message);
            return pdf_writer::$pdf;
        }
    }
}
