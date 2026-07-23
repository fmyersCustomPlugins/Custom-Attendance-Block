<?php
// This file is part of Moodle - http://moodle.org/
//
// This plugin is free software distributed under the terms of the GNU GPL v3 or later.

/**
 * Version details for block_attendance_summary.
 *
 * @package   block_attendance_summary
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'block_attendance_summary';
$plugin->version   = 2026071002;      // YYYYMMDDXX.
$plugin->requires  = 2023100900;      // Minimum Moodle 4.3 core - safely compatible with 4.5.1.
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.3.0';

// Note: mod_attendance is required for this block to show data, but it is
// intentionally NOT declared here as a hard install dependency because its
// version numbering scheme (community plugin) varies widely between sites
// and a mismatched pin would block installation unnecessarily. Instead, the
// block checks at runtime whether mod_attendance is installed and shows a
// friendly notice if it is missing (see block_attendance_summary::get_content()).
