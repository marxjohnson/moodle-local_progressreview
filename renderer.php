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

            $subject_deadline = $session->deadline_subject ? date('D d/m/Y', $session->deadline_subject) : '';
            $tutor_deadline = $session->deadline_tutor ? date('D d/m/Y', $session->deadline_tutor) : '';
            $row = new html_table_row(array(
                html_writer::link($sessionurl, $session->name),
                $subject_deadline,
                $tutor_deadline,
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

            if ($summaries) {
                $table = new html_table();
                $table->head = array(
                    get_string('name', 'local_progressreview'),
                    get_string('teachers', 'local_progressreview'),
                    get_string('reviews', 'local_progressreview'),
                    get_string('completedreviews', 'local_progressreview'),
                    get_string('outstandingreviews', 'local_progressreview')
                );

                foreach ($summaries as $summary) {
    //                $courseurl = new moodle_url('/local/progresssummary/courseview.php', array('id' => $summary->courseid));

                    $row = new html_table_row(array(
                        // html_writer::link($courseurl, $summary->name),
                        $summary->name,
                        $summary->teacher,
                        $summary->total,
                        $summary->completed,
                        ($summary->total)-($summary->completed)
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

    function course_selector_form($potential_selector, $distributed_selector, $sessionid, $type = PROGRESSREVIEW_SUBJECT) {

        $buttonsuffix = ($type == PROGRESSREVIEW_SUBJECT) ? '_subject' : '_tutor';
        $output = '';
        $table = new html_table('course_selector');
        $row = new html_table_row();
        $row->cells[] = $distributed_selector->display(true);
        $cell = html_writer::empty_tag('input', array('class' => 'course_selector_button', 'name' => 'generate'.$buttonsuffix, 'type' => 'submit', 'value' => $this->output->larrow().' '.get_string('createreviews', 'local_progressreview')));
        $row->cells[] = $cell;
        $row->cells[] = $potential_selector->display(true);
        $table->data[] = $row;

        $output = html_writer::start_tag('form', array('action' => $this->page->url->out(), 'method' => 'post'));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id', 'value' => $sessionid));
        $output .= html_writer::table($table);
        $output .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'regenerate'.$buttonsuffix, 'value' => get_string('regenerate', 'local_progressreview')));
        if ($type == PROGRESSREVIEW_SUBJECT) {
            $output .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'snapshot', 'value' => get_string('snapshot', 'local_progressreview')));
        }
        $output .= html_writer::end_tag('form');

        return $output;

    }

    function changescale_button($sessionid, $courseid) {
        $url = new moodle_url('/local/progressreview/changescale.php', array('sessionid' => $sessionid, 'courseid' => $courseid));
        $button = $this->output->single_button($url, get_string('changescale', 'local_progressreview'), 'get');
        return $this->output->container($button.get_string('savefirst', 'local_progressreview'), array('changescale'));
    }

    /**
     * @todo Make option for non-induction review, and allow avgcse to be configured
     * @todo Make pluggable
     */
    function subject_review_table($reviews, $form = true, $inductionreview = false) {

        $table = new html_table();
        $table->head = array(
            '',
            '',
            get_string('attendance', 'local_progressreview'),
            get_string('punctuality', 'local_progressreview'),
            get_string('homework', 'local_progressreview'),
            get_string('behaviour', 'local_progressreview'),
            get_string('effort', 'local_progressreview'),
            get_string('minimumgrade', 'local_progressreview'),
            get_string('targetgrade', 'local_progressreview'),
            get_string('performancegrade', 'local_progressreview')
        );
        if (!$form) {
            $table->head[0] = get_string('course');
            $table->head[1] = get_string('teacher', 'local_progressreview');
        }

        foreach ($reviews as $review) {
            $student = $review->progressreview->get_student();
            $session = $review->progressreview->get_session();
            if ($form) {
                $picture = $this->output->user_picture($student);
                $name = fullname($student);
            } else {
                $picture = $review->progressreview->get_course()->fullname;
                $name = fullname($review->progressreview->get_teacher());
            }
            $attendance = number_format($review->attendance, 0).'%';
            $punctuality = number_format($review->punctuality, 0).'%';
            $fieldarray = 'review['.$review->id.']';
            if ($form) {
                $homework = html_writer::empty_tag('input', array('class' => 'homework', 'name' => $fieldarray.'[homeworkdone]', 'value' => $review->homeworkdone));
                $homework .= ' / ';
                $homework .= html_writer::empty_tag('input', array('class' => 'homework', 'name' => $fieldarray.'[homeworktotal]', 'value' => $review->homeworktotal));
                $behaviour = html_writer::select($session->scale_behaviour, $fieldarray.'[behaviour]', $review->behaviour);
                $effort = html_writer::select($session->scale_effort, $fieldarray.'[effort]', $review->effort);
                //            $mintarget = $review->scale[$review->minimumgrade];
                $targetgrade = html_writer::select($review->scale, $fieldarray.'[targetgrade]', $review->targetgrade);
                $performancegrade = html_writer::select($review->scale, $fieldarray.'[performancegrade]', $review->performancegrade);
                $commentsattrs = array(
                    'name' => $fieldarray.'[comments]'
                );
                $commentsfield = html_writer::tag('textarea', $review->comments, $commentsattrs);
                $commentscell = new html_table_cell($commentsfield);
            } else {
                $homework = $review->homeworkdone.'/'.$review->homeworktotal;
                $behaviour = @$session->scale_behaviour[$review->behaviour];
                $effort = @$session->scale_effort[$review->effort];
                $targetgrade = @$review->scale[$review->targetgrade];
                $performancegrade = @$review->scale[$review->performancegrade];
                $commentscell = new html_table_cell(str_replace("\n", "<br />", $review->comments));
            }
            $mintarget = $review->minimumgrade;
            if ($form || !empty($behaviour) || !empty($effort) || !empty($targetgrade) || !empty($performancegrade)) {
                $row = new html_table_row(array(
                    $picture,
                    $name,
                    $attendance,
                    $punctuality,
                    $homework,
                    $behaviour,
                    $effort,
                    $mintarget,
                    $targetgrade,
                    $performancegrade
                ));

                $table->data[] = $row;
            }
            if (!$session->inductionreview) {
                $headercell = new html_table_cell(get_string('commentstargets', 'local_progressreview').':');
                $headercell->header = true;

                $commentscell->colspan = 8;
                $row = new html_table_row(array('', $headercell, $commentscell));
                $table->data[] = $row;
            }
        }

        $output = '';
        $output .= html_writer::start_tag('form', array('action' => $this->page->url->out_omit_querystring(), 'method' => 'post'));
        $output .= html_writer::input_hidden_params($this->page->url);
        $output .= html_writer::table($table);
        if ($form) {
            $output .= html_writer::empty_tag('input', array('name' => 'submit', 'type' => 'submit', 'value' => get_string('savechanges')));
        }
        return $output;

    }

    public function tutor_review_table($review) {
        $output = '';
        $plugins = $review->get_plugins();
        foreach ($plugins as $plugin) {
            $output .= $plugin->get_static_output();
        }
    }

    public function tutorgroup_list($progressreviews) {
        $table = new html_table();
        $table->head = array(get_string('student', 'local_progressreview'), get_string('commentswritten', 'local_progressreview'));
        foreach($progressreviews as $progressreview) {

            $review = $progressreview->get_plugin('tutor')->get_review();
            $student = $progressreview->get_student();
            $name = fullname($student);
            if(empty($review->comments)) {
                $completed = get_string('no');
            } else {
                $completed = get_string('yes');
            }

            $params = array(
                'courseid' => $progressreview->get_course()->originalid,
                'studentid' => $student->id,
                'sessionid' => $progressreview->get_session()->id
            );
            $reviewurl = new moodle_url('/local/progressreview/tutorreview.php', $params);
            $link = html_writer::link($reviewurl, $name);
            $row = array($link, $completed);
            $table->data[] = $row;
        }
        return html_writer::table($table);
    }

    public function user_session_links($user, $sessions, $currentid) {
        $links = array();
        foreach ($sessions as $session) {
            $params = array('sessionid' => $session->id, 'userid' => $user->id);
            $url = new moodle_url('/local/progressreview/user.php', $params);
            $link = html_writer::link($url, $session->name);
            if ($session->id == $currentid) {
                $link = html_writer::tag('strong', $link);
            }
            $links[] = $link;
        }
        return html_writer::alist($links);
    }
}

