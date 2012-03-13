<?php
class progressreview_intentions_renderer extends plugin_renderer_base {
    public function review($currentcourses) {
        $output = $this->output->heading(get_string('pluginname', 'progressreview_intentions'));
        $table = new html_table();
        $strcurrentcourse = get_string('currentcourse', 'progressreview_intentions');
        $strprogressioncourse = get_string('progressioncourse', 'progressreview_intentions');
        $strcontinue = get_string('continue', 'progressreview_intentions');
        $stristop = get_string('istop', 'progressreview_intentions');
        $stryes = get_string('yes');
        $strno = get_string('no');
        $strnone = get_string('none', 'progressreview_intentions');
        $table->head = array($strcurrentcourse, $strprogressioncourse, $strcontinue, $stristop);

        foreach ($currentcourses as $currentcourse) {
            $row = array($currentcourse->fullname);
            if ($currentcourse->progression) {
                $row[] = $currentcourse->progression->newname;
                $row[] = $currentcourse->progression->intention->cont ? $stryes : $strno;
                $row[] = $currentcourse->progression->intention->istop ? $stryes : $strno;

            } else {
                $row[] = $strnone;
                $row[] = '';
                $row[] = '';
            }
            $table->data[] = $row;
        }
        $output .= html_writer::table($table);
        return $output;
    }
}

class progressreview_intentions_print_handler extends plugin_print_renderer_base {
    public function review($currentcourses) {
        $this->output->heading(get_string('pluginname', 'progressreview_intentions'));
        $table = new html_table();
        $strcurrentcourse = get_string('currentcourse', 'progressreview_intentions');
        $strprogressioncourse = get_string('progressioncourse', 'progressreview_intentions');
        $strcontinue = get_string('continue', 'progressreview_intentions');
        $stristop = get_string('istop', 'progressreview_intentions');
        $stryes = get_string('yes');
        $strno = get_string('no');
        $strnone = get_string('none', 'progressreview_intentions');
        $table->head = array($strcurrentcourse, $strprogressioncourse, $strcontinue, $stristop);

        foreach ($currentcourses as $currentcourse) {
            $row = array($currentcourse->fullname);
            if ($currentcourse->progression) {
                $row[] = $currentcourse->progression->newname;
                $row[] = $currentcourse->progression->intention->cont ? $stryes : $strno;
                $row[] = $currentcourse->progression->intention->istop ? $stryes : $strno;

            } else {
                $row[] = $strnone;
                $row[] = '';
                $row[] = '';
            }
            $table->data[] = $row;
        }
        return pdf_writer::table($table);
    }
}
