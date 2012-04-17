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
 * Defines the plugin's strings, and those for the core subplugins (tutor and subject)
 *
 * @package   local_progressreview
 * @copyright 2011 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['all'] = 'All';
$string['attendance'] = 'Attendance';
$string['attendance_help'] = 'Attendance is calculated since the start of the academic year, using
    the formula published on the Registry Page';
$string['autosaving'] = 'Autosaving';
$string['autosaveactive'] = 'Autosave Active';
$string['autosavefailed'] = 'Autosave Failed. Autosaving will now be disabled. Please ensure that
you manually save using the button at the bottom of the page. The server returned the following
message: {$a}';
$string['behaviour'] = 'Behaviour';
$string['behaviour_help'] = 'Refers to behaviour in class i.e. Disruptive, not attendance as the
    percentage speaks for itself';
$string['changescale'] = 'Change Grade Scale';
$string['changesnotsaved'] = 'Changes Not Saved';
$string['comments'] = 'Comments';
$string['comments_help'] = 'Write a short comment in the **third person** about the student\'s
    progress, or action that you have agreed with the student.';
$string['commentstargets'] = 'Comments/Targets';
$string['commentswritten'] = 'Comments Written?';
$string['completedreviews'] = 'Completed Reviews';
$string['completesubjectreviewsby'] = 'Subject Reviews must be completed by {$a}';
$string['completetutorreviewsby'] = 'Tutor Reviews must be completed by {$a}';
$string['confirmdelete'] = 'You are about to delete all reviews written by {$a->teacher} for {$a->course} during {$a->session}.
    This cannot be undone.  If this is definitely what you want to do, click "Continue".';
$string['courses'] = 'Courses';
$string['courseswithreviews'] = 'Your courses with Reviews';
$string['createsession'] = 'Create Session';
$string['createreviews'] = 'Generate Reviews';
$string['deadline_subject'] = 'Subject Review Deadline';
$string['deadline_tutor'] = 'Tutor Review Deadline';
$string['deadline_active'] = 'Display Links Until';
$string['deleted'] = 'Progressreviews Deleted Successfully';
$string['disablememlimit'] = 'Try again with Memory Limit disabled';
$string['effort'] = 'Effort';
$string['effort_help'] = 'Refers to both effort in class and homework/coursework produced';
$string['endofgroup'] = 'End of Group';
$string['filterdept'] = 'Filter Department';
$string['filtercourse'] = 'Filter Course';
$string['filterteacher'] = 'Filter Teacher';
$string['homework'] = 'Homework Completion';
$string['homework_help'] = 'Data will be supplied from your Moodle gradebook. It should be a
    fraction which represents a cumulative total of homework/coursework from the beginning of the
    academic year e.g.18/18. The figures are editable in case your gradebook doesn\'t reflect the
    true picture. As a guide, 2/3 homeworks is unsatisfactory and should be reflected in the
    Effort section.';
$string['homeworkstart'] = 'Homework Start Date';
$string['homeworktotallessthandone'] = 'Homework Total was less than Homework Done for {$a}.';
$string['homeworkstart_help'] = 'Homework statistics will use this date as a start date when calculating completion';
$string['generate'] = 'Generate PDF';
$string['generateandview'] = 'Generate PDF and View';
$string['generateanddownload'] = 'Generate PDF and Download';
$string['groupby'] = 'Group Report By:';
$string['groupbystudent'] = 'Student (show all subject reviews then tutor review, for each student)';
$string['groupbysubject'] = 'Class (show reviews for all students in each class)';
$string['invalidcourse'] = 'There is no course with the ID {$a}.';
$string['invalidsession'] = 'There is no session with the ID {$a}.';
$string['invalidstudent'] = 'There is no student with the ID {$a}.';
$string['invalidteacher'] = 'There is no teacher with the ID {$a}.';
$string['inductionreview'] = 'Induction Review?';
$string['lockafterdeadline'] = 'Lock reviews after deadline?';
$string['manage'] = 'Manage';
$string['minimumgrade'] = 'Minimum Grade';
$string['minimumgrade_help'] = 'Minimum Grade is calculated based on national data for students
    with similar GCSE scores. Full details of the calculations and statistics can be found on the
    Registry Page.';
$string['name'] = 'Name';
$string['nodeletereviews'] = 'No reviews were selected for deletion';
$string['nosessions'] = 'There are currently no Review Sessions';
$string['noreviews'] = 'This user has no reviews';
$string['outofmemory'] = 'ERROR: While fetching the progress reviews for printing, the system reached its
                    memory limit of {$a}.  This can happen when printing a large number of reviews.
                    You can ask your Server Administrator to raise this limit, or if you are aware of
                    the risks of doing so, click the link below to try again with the memory limit
                    disabled.';
$string['outstandingreviews'] = 'Outstanding Reviews';
$string['performancegrade'] = 'Performance Grade';
$string['performancegrade_help'] = 'CPG is the subject teacher’s professional judgement of the
    grade the student is most likely to achieve should they continue to work at their current
    rate and effectiveness.  This grade can be a ‘U’ or ‘Fail’ where accurate assessment supports
    that view. This can be chosen from a drop down menu of grades. If the scale displayed is not
    correct for this class, please click the Override Grade Scale button for other options
    e.g. P, M, D, Unsatisfactory, Satisfactory, Good, and Excellent, IB and Art endorsement
    grades. ';
$string['pluginname'] = 'Progress Review';
$string['plugins'] = 'Plugins';
$string['punctuality'] = 'Punctuality';
$string['punctuality_help'] = 'Punctuality is calculated from the start of the academic year, using
    the formula published on the Registry Page';
$string['previousfigures'] = 'Figures in (brackets) are from {$a}';
$string['print'] = 'Print';
$string['printheading'] = 'Printing and PDF Generation';
$string['rednotsaved'] = 'Values in red have not been saved';
$string['regenerate'] = 'Generate Missing Reviews';
$string['reference'] = 'Reference';
$string['returntolist'] = 'Return to List';
$string['reviews'] = 'Reviews';
$string['reviewsfordept'] = 'Reviews For {$a}';
$string['reviewsgenerated'] = 'Reviews Generated';
$string['saveand'] = 'Save and...';
$string['savefirst'] = '(Don\'t forget to save first!)';
$string['savedreviewfor'] = 'Saved Review For {$a}';
$string['scale_behaviour'] = 'Behaviour Scale';
$string['scale_effort'] = 'Effort Scale';
$string['scale_homework'] = 'Scale Homework';
$string['selectcriteria'] = 'Select criteria of reviews for printing';
$string['selectplugins'] = 'Select Plugins';
$string['selectedreviews'] = 'You have chosen to print the following Progress Reviews';
$string['sessioncreated'] = 'Session Saved';
$string['sessions'] = 'Sessions';
$string['showdatafrom'] = 'Show Data From';
$string['snapshot'] = 'Snapshot data';
$string['snapshotdate'] = 'Snapshot Date';
$string['snapshotted'] = 'Data Snapshotted';
$string['subjectreviews'] = 'Subject Reviews';
$string['startofgroup'] = 'Start of Group';
$string['student'] = 'Student';
$string['students'] = 'Students';
$string['targetgrade'] = 'Target Grade';
$string['targetgrade_help'] = 'This is the grade that the student sets for themselves after
    discussion with you ';
$string['teacher'] = 'Teacher';
$string['teachers'] = 'Teachers';
$string['tutor'] = 'Tutor';
$string['tutorreviews'] = 'Tutor Reviews';

