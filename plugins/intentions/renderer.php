<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Defines renderers for intentions plugin
 *
 * @package   local_progressreview
 * @subpackage progressreview_intentions
 * @copyright 2012 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
                if (!empty($currentcourse->progression->intention->cont)) {
                    if ($currentcourse->progression->intention->cont) {
                        $row[] = $stryes;
                    } else {
                        $row[] = $strno;
                    }
                } else {
                    $row[] = '';
                }
                if (!empty($currentcourse->progression->intention->istop)) {
                    if ($currentcourse->progression->intention->istop) {
                        $row[] = $stryes;
                    } else {
                        $row[] = $strno;
                    }
                } else {
                    $row[] = '';
                }

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

class progressreview_intentions_print_renderer extends plugin_print_renderer_base {
    public function review($currentcourses) {
    }
}
