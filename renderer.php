<?php

class local_progressreview_renderer extends plugin_renderer_base {

    function editicon($url) {
        $icon = $this->output->pix_icon('t/edit', get_string('edit'));
        $link = html_writer::link($url, $icon);
        return $link;
    }

    function sessions_table($sessions) {
        $table = new html_table();
        $table->head = array(
            get_string('name', 'local_progressreview'),
            get_string('deadline_subject', 'local_progressreview'),
            get_string('deadline_tutor', 'local_progressreview'),
            ''
        );

        foreach ($sessions as $session) {
            $sessionurl = new moodle_url('/local/progressreview/session.php', array('id' => $session->id));
            $editurl = new moodle_url('/local/progressreview/session.php', array('editid' => $session->id));

            $row = new html_table_row(array(
                html_writer::link($sessionurl, $session->name),
                date('D d/m/Y', $session->deadline_subject),
                date('D d/m/Y', $session->deadline_tutor),
                $this->editicon($editurl)
            ));
            if ($session->deadline_subject > time() || $session->deadline_tutor > time()) {
                $row->attributes['class'] = 'current';
            }
            $table->data[] = $row;
        }

        $output = $this->output->heading(get_string('sessions', 'local_progressreview'), 2);
        $output .= html_writer::table($table);
        $output .= $this->output->single_button('/local/progressreview/session.php', get_string('createsession', 'local_progressreview'));

        return $output;
    }

    function department_table($department, $subjectsummaries, $tutorsummaries) {
        $output = $this->output->heading(get_string('reviewsfordept', 'local_progressreview', $department->name), 2);

        foreach (array('subjectreviews' => $subjectsummaries, 'tutorreviews' => $tutorsummaries) as $type => $summaries) {

            $table = new html_table();
            $table->head = array(
                get_string('name', 'local_progressreview'),
                get_string('teachers', 'local_progressreview'),
                get_string('reviews', 'local_progressreview'),
                get_string('outstandingreviews', 'local_progressreview')
            );

            foreach ($summaries as $summary) {
                $courseurl = new moodle_url('/local/progresssummary/courseview.php', array('id' => $summary->courseid));

                $row = new html_table_row(array(
                    html_writer::link($courseurl, $course->name),
                    $summary->teacher,
                    $summary->total,
                    $summary->completed
                ));

                $row->attributes['class'] = 'incomplete';
                if ($summary->completed == 0) {
                    $row->attributes['class'] = 'empty';
                }
                if ($summary->completed == $summary->total) {
                    $row->attributes['class'] = 'completed';
                }

                $table->data[] = $row;
            }

            $output .= $this->output->heading(get_string($type, 'local_progressreview'), 3);
            $output .= html_writer::table($table);
        }

        return $output;
    }

    function course_table($course) {
        return 'Whoops! not done yet!';
    }

    function courses_table($courses) {
        $output = $this->output->heading(get_string('courseswithreviews', 'local_progressreview', 2));

        $table = new html_table();
        $table->head = array(
            get_string('reference', 'local_progressreview'),
            get_string('name', 'local_progressreview')
        );

        foreach ($courses as $course) {
            $url = new moodle_url('/local/progressreview/review.php', array('courseid' => $course->id));
            $row = new html_table_row(array(
                html_writer::link($url, $course->shortname),
                html_writer::link($url, $course->fullname)
            ));
            $table->data[] = $row;
        }

        $output .= html_writer::table($table);
        return $output;
    }

    function session_links($url, $sessions) {
        $sessionlinks = array();
        foreach($sessions as $session) {
            $url = new moodle_url($url, array('sessionid' => $session->id));
            $sessionlinks[] = html_writer::link($url, $session->name);
        }
        return html_writer::alist($sessionlinks);
    }
}

