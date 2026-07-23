<?php
// This file is part of Moodle - http://moodle.org/
//
// This plugin is free software distributed under the terms of the GNU GPL v3 or later.

/**
 * Admin settings for block_attendance_summary.
 *
 * These are the ONLY things a school needs to update each year: the four
 * semester date fields below. No code changes are ever required.
 *
 * @package   block_attendance_summary
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading(
        'block_attendance_summary/semesterheading',
        get_string('semesterheading', 'block_attendance_summary'),
        get_string('semesterheading_desc', 'block_attendance_summary')
    ));

    $settings->add(new admin_setting_configtext(
        'block_attendance_summary/sem1start',
        get_string('sem1start', 'block_attendance_summary'),
        get_string('semdate_desc', 'block_attendance_summary'),
        '',
        PARAM_TEXT,
        12
    ));

    $settings->add(new admin_setting_configtext(
        'block_attendance_summary/sem1end',
        get_string('sem1end', 'block_attendance_summary'),
        get_string('semdate_desc', 'block_attendance_summary'),
        '',
        PARAM_TEXT,
        12
    ));

    $settings->add(new admin_setting_configtext(
        'block_attendance_summary/sem2start',
        get_string('sem2start', 'block_attendance_summary'),
        get_string('semdate_desc', 'block_attendance_summary'),
        '',
        PARAM_TEXT,
        12
    ));

    $settings->add(new admin_setting_configtext(
        'block_attendance_summary/sem2end',
        get_string('sem2end', 'block_attendance_summary'),
        get_string('semdate_desc', 'block_attendance_summary'),
        '',
        PARAM_TEXT,
        12
    ));
}
