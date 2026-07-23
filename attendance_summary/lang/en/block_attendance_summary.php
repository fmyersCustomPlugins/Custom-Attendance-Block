<?php
// This file is part of Moodle - http://moodle.org/
//
// This plugin is free software distributed under the terms of the GNU GPL v3 or later.

/**
 * English language strings for block_attendance_summary.
 *
 * @package   block_attendance_summary
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Attendance Summary';
$string['attendance_summary:addinstance'] = 'Add a new Attendance Summary block';
$string['attendance_summary:myaddinstance'] = 'Add a new Attendance Summary block to the Dashboard';

$string['semester1'] = 'Semester 1';
$string['semester2'] = 'Semester 2';
$string['totalattendance'] = 'Total attendance';
$string['colpresent'] = 'P';
$string['collate'] = 'L';
$string['colunexcusedabsent'] = 'UA';
$string['colexcusedabsent'] = 'EA';
$string['coltotal'] = 'Total';
$string['nostudentsorcourses'] = 'You have no students or are not enrolled in a course.';

$string['semesterheading'] = 'Semester dates';
$string['semesterheading_desc'] = 'Set the start and end dates for each semester below. Attendance sessions are grouped into Semester 1 / Semester 2 based on these dates. Update these four fields at the start of every school year — no code changes are ever required.';
$string['sem1start'] = 'Semester 1 start date';
$string['sem1end'] = 'Semester 1 end date';
$string['sem2start'] = 'Semester 2 start date';
$string['sem2end'] = 'Semester 2 end date';
$string['semdate_desc'] = 'Enter the date in YYYY-MM-DD format, e.g. 2026-08-10.';
$string['semesterdatesnotset'] = 'Semester dates have not been fully configured yet, so only Total attendance can be shown. An administrator can set them under Site administration > Plugins > Blocks > Attendance Summary.';
$string['modattendancemissing'] = 'The Attendance activity module (mod_attendance) is not installed on this site. This block requires it in order to show attendance data.';

$string['privacy:metadata'] = 'The Attendance Summary block does not store any personal data itself. It only reads and displays data that already exists in the Attendance activity module.';
