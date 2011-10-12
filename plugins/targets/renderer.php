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
