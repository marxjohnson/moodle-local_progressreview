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
 * Defines strings for referral plugin
 *
 * @package   local_progressreview
 * @subpackage progressreview_referral
 * @copyright 2012 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['emailtext'] = '{$a->studentname}\'s tutor {$a->tutorname} wishes to refer the student to you.

{$a->message}

You will not recieve further email updates if this referral is edited, but you can view the
up-to-date text at the link below:
{$a->link}';
$string['emailsubject'] = 'Progress Review Referral';
$string['pluginname'] = 'Refer to LAM';
$string['refer'] = 'Refer?';
$string['referral'] = 'Referral';
$string['writtenby'] = 'Written By {$a}';
$string['message'] = 'Message';
$string['message_help'] = 'Please enter full details of the non-standard programme which the
    student wants to study next year and why. This will be sent to the LAM for review and further
    discussion. The LAM will then confirm the student’s intentions to the Registry.

 Please Note that this message will not be saved and sent to the LAM until you click one of the
    buttons as the bottom of the form.';
$string['musthavemessage'] = 'If you wish to refer this student to their LAM you must include
    a message';
$string['notuserforreferral'] = 'This referral is not for you!';
