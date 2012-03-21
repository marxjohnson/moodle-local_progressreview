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
 * Defines renderers for Ultimate Plans plugin
 *
 * @package   local_progressreview
 * @subpackage progressreview_ultimateplans
 * @copyright 2012 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
    }
}
