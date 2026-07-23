<?php
// This file is part of Moodle - http://moodle.org/
//
// This plugin is free software distributed under the terms of the GNU GPL v3 or later.

/**
 * Attendance Summary block - shows Semester 1, Semester 2 and total attendance
 * pulled from mod_attendance, on the user's Dashboard.
 *
 * @package   block_attendance_summary
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_attendance_summary extends block_base {

    /**
     * Block initialisation.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_attendance_summary');
    }

    /**
     * Where this block is allowed to be added.
     * We only allow it on the Dashboard (My Moodle) page since it shows
     * data for the currently logged in user.
     *
     * @return array
     */
    public function applicable_formats() {
        return [
            'my'          => true,
            'site'        => false,
            'course-view' => false,
            'mod'         => false,
        ];
    }

    /**
     * This block has a global (site-level) settings.php for semester dates.
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }

    /**
     * Only one instance of this block per Dashboard makes sense.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Don't let editing teachers/admins put a raw title override that hides context - keep default.
     *
     * @return bool
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     * Build the block content.
     *
     * @return stdClass
     */
    public function get_content() {
        global $USER, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // Only show real, logged in users their own attendance.
        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        // Make sure the Attendance activity module is actually installed.
        if (!\core_component::get_component_directory('mod_attendance')) {
            $this->content->text = $OUTPUT->notification(
                get_string('modattendancemissing', 'block_attendance_summary'),
                'warning'
            );
            return $this->content;
        }

        $helper = '\block_attendance_summary\local\attendance_helper';

        if ($helper::is_student((int) $USER->id)) {
            // Students always see only their own attendance, never a mentee's.
            $summary = $helper::get_user_summary((int) $USER->id);
            $data = [
                'hasresult'       => true,
                'hasmentees'      => false,
                'sections'        => [$summary],
                'datesconfigured' => $summary['datesconfigured'],
            ];
        } else {
            // Not a student: check for parents/mentors with linked students
            // (a role assigned in one or more students' user context - see README).
            $mentees = $helper::get_mentee_users((int) $USER->id);

            if (!empty($mentees)) {
                $sections = [];
                foreach ($mentees as $mentee) {
                    $summary = $helper::get_user_summary((int) $mentee->id);
                    $summary['label'] = fullname($mentee);
                    $sections[] = $summary;
                }
                $data = [
                    'hasresult'       => true,
                    'hasmentees'      => true,
                    'sections'        => $sections,
                    'datesconfigured' => $sections[0]['datesconfigured'],
                ];
            } else {
                // Neither a student nor linked to any students - nothing to show.
                $data = [
                    'hasresult' => false,
                ];
            }
        }

        $this->content->text = $OUTPUT->render_from_template('block_attendance_summary/summary', $data);

        return $this->content;
    }
}
