<?php
class progressreview_targets_renderer extends plugin_renderer_base {
    public function review($targets) {
        $output = $this->output->heading(get_string('pluginname', 'progressreview_targets'), 4);
        $count = 1;
        foreach ($targets as $target) {
            $output .= $this->output->heading(get_string('modulename', 'ilptarget').' '.$count, 5);
            $deadline = date('l d/m/Y', $target->deadline);
            $output .= html_writer::tag('p', $target->targetset);
            $output .= html_writer::tag('p', get_string('deadline', 'ilptarget').': '.$deadline);
            $count++;
        }
        return $output;
    }
}

class progressreview_targets_print_renderer extends plugin_print_renderer_base {

    public function review($targets) {
        $this->output->heading(get_string('pluginname', 'progressreview_targets'), 4);
        $count = 1;
        foreach ($targets as $target) {
            $this->output->heading(get_string('modulename', 'ilptarget').' '.$count, 5);
            $deadline = date('l d/m/Y', $target->deadline);
            pdf_writer::text($target->targetset, 12);
            pdf_writer::text(get_string('deadline', 'ilptarget').': '.$deadline);
            $count++;
        }
        return pdf_writer::$pdf;
    }
}
