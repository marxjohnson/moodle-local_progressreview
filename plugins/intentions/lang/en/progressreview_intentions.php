<?php
// this file is part of moodle - http://moodle.org/
//
// moodle is free software: you can redistribute it and/or modify
// it under the terms of the gnu general public license as published by
// the free software foundation, either version 3 of the license, or
// (at your option) any later version.
//
// moodle is distributed in the hope that it will be useful,
// but without any warranty; without even the implied warranty of
// merchantability or fitness for a particular purpose.  see the
// gnu general public license for more details.
//
// you should have received a copy of the gnu general public license
// along with moodle.  if not, see <http://www.gnu.org/licenses/>.


/**
 * defines strings for intentions plugin
 *
 * @package   local_progressreview
 * @subpackage progressreview_intentions
 * @copyright 2012 taunton's college, uk
 * @author    mark johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html gnu gpl v3 or later
 */

$string['currentcode'] = 'Current Code';
$string['currentcourse'] = 'Current Course';
$string['currentcourse_help'] = 'These are all the courses the student is currently enrolled on.';
$string['configheader'] = 'Upload Intentions Data';
$string['continue'] = 'Continue?';
$string['continue_help'] = 'Check the box if the student wished to continue the course or is
    undecided.  Leave unchecked only if the student is sure they will not continue the course.';
$string['csvfile'] = 'Select CSV File';
$string['csvfile_help'] = 'CSV File must contain 3 columns: "currentcode", "newcode", "newname"';
$string['id'] = 'ID';
$string['istop'] = 'Timetable Priorities';
$string['istop_help'] = 'Please ask the student to name the **three** courses they most want to
    ensure the timetable caters for next year. (Please refer to the general student notes at the
    top).  If you choose more than three courses, the last ones you choose will **not** be saved.

 *Note: This information will only be used if there is a timetabling conflict, to help us assess
 how important it is to resolve the conflict. We will always aim to devise a year 2 timetable
 that accommodates all students’ choices.*';
$string['musthavefile'] = 'You must select a file';
$string['newcode'] = 'New Code';
$string['newname'] = 'New Name';
$string['none'] = 'None';
$string['notrequired'] = 'This section is not required for this student';
$string['pluginname'] = 'Intentions';
$string['progressioncourse'] = 'Progression Course';
$string['progressioncourse_help'] = 'These are the continuation options for next year.';
$string['tutormask'] = 'Tutor Group Mask';
$string['tutormask_help'] = 'Only display this plugin on reviews for reviews where the course shortname
    matches this mask (must be a valid regular expression). Leave blank to match all.';
$string['toomanytop'] = 'You can only select 3 Timetable Priorities. Click the (?) Icon form more information';
$string['wrongcolcount'] = 'Wrong number of columns on line {$a->line}. Expected 3, got {$a->num}';

