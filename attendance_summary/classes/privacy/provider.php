<?php
// This file is part of Moodle - http://moodle.org/
//
// This plugin is free software distributed under the terms of the GNU GPL v3 or later.

namespace block_attendance_summary\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\null_provider;

/**
 * Privacy provider for block_attendance_summary.
 *
 * This block does not store any personal data of its own - it only reads
 * and displays data that already exists in mod_attendance (which has its
 * own privacy provider). Semester dates are site configuration, not
 * personal data.
 *
 * @package   block_attendance_summary
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements null_provider {

    /**
     * Get the language string identifier explaining why this is a null provider.
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
