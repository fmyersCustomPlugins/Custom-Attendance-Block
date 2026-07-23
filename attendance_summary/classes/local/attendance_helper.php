<?php
// This file is part of Moodle - http://moodle.org/
//
// This plugin is free software distributed under the terms of the GNU GPL v3 or later.

namespace block_attendance_summary\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Pulls data from mod_attendance and computes Semester 1 / Semester 2 / Total
 * attendance counts (Present / Late / Unexcused Absent / Excused Absent) for
 * a given user.
 *
 * @package   block_attendance_summary
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attendance_helper {

    /**
     * Status acronyms (as configured on each mod_attendance status set,
     * e.g. "P", "L", "UA", "EA") mapped to the bucket key we count them
     * under. Matching is case-insensitive and trims whitespace. Any status
     * whose acronym doesn't match one of these still counts toward the
     * bucket's total, just not toward a specific column.
     */
    const ACRONYM_MAP = [
        'P'  => 'p',
        'L'  => 'l',
        'UA' => 'ua',
        'EA' => 'ea',
    ];

    /**
     * Build a template-ready summary array for the given user.
     *
     * @param int $userid
     * @return array
     */
    public static function get_user_summary(int $userid): array {
        global $DB;

        [$sem1start, $sem1end, $sem2start, $sem2end, $datesconfigured] = self::get_semester_dates();

        $buckets = [
            'sem1'  => ['p' => 0, 'l' => 0, 'ua' => 0, 'ea' => 0, 'total' => 0],
            'sem2'  => ['p' => 0, 'l' => 0, 'ua' => 0, 'ea' => 0, 'total' => 0],
            'total' => ['p' => 0, 'l' => 0, 'ua' => 0, 'ea' => 0, 'total' => 0],
        ];

        // Courses the user is actively enrolled in.
        $courses = enrol_get_users_courses($userid, true, ['id']);
        if (empty($courses)) {
            return self::format_output($buckets, $datesconfigured);
        }
        $courseids = array_keys($courses);

        [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'course');

        // All attendance activity instances that live in those courses.
        $sql = "SELECT a.id
                  FROM {attendance} a
                 WHERE a.course $insql";
        $attendanceids = $DB->get_fieldset_sql($sql, $inparams);

        if (empty($attendanceids)) {
            return self::format_output($buckets, $datesconfigured);
        }

        [$ainsql, $ainparams] = $DB->get_in_or_equal($attendanceids, SQL_PARAMS_NAMED, 'att');

        // Preload non-deleted status options (Present/Late/Unexcused/Excused
        // etc.) so we can translate each log entry's statusid into one of
        // our P / L / UA / EA buckets via its acronym.
        $statussql = "SELECT id, attendanceid, setnumber, acronym
                        FROM {attendance_statuses}
                       WHERE attendanceid $ainsql
                             AND (deleted = 0 OR deleted IS NULL)";
        $statuses = $DB->get_records_sql($statussql, $ainparams);

        $statusbucketkey = []; // statusid => 'p'|'l'|'ua'|'ea'|null (unmatched acronym).
        foreach ($statuses as $status) {
            $acronym = strtoupper(trim((string) $status->acronym));
            $statusbucketkey[$status->id] = self::ACRONYM_MAP[$acronym] ?? null;
        }

        // Every session this student has an actual attendance log entry for
        // (i.e. attendance was taken and recorded for them) across those instances.
        $params = $ainparams + ['userid' => $userid];
        $sql = "SELECT al.id AS logid, al.statusid, s.id AS sessionid, s.sessdate,
                       s.attendanceid, s.statusset
                  FROM {attendance_log} al
                  JOIN {attendance_sessions} s ON s.id = al.sessionid
                 WHERE al.studentid = :userid
                       AND s.attendanceid $ainsql";
        $records = $DB->get_records_sql($sql, $params);

        foreach ($records as $rec) {
            if (!array_key_exists($rec->statusid, $statusbucketkey)) {
                // Status has since been deleted/changed - skip it, we can't count it fairly.
                continue;
            }

            $bucketkey = $statusbucketkey[$rec->statusid];

            $buckets['total']['total']++;
            if ($bucketkey !== null) {
                $buckets['total'][$bucketkey]++;
            }

            $sessdate = (int) $rec->sessdate;

            if ($datesconfigured && $sessdate >= $sem1start && $sessdate <= $sem1end) {
                $buckets['sem1']['total']++;
                if ($bucketkey !== null) {
                    $buckets['sem1'][$bucketkey]++;
                }
            } else if ($datesconfigured && $sessdate >= $sem2start && $sessdate <= $sem2end) {
                $buckets['sem2']['total']++;
                if ($bucketkey !== null) {
                    $buckets['sem2'][$bucketkey]++;
                }
            }
        }

        return self::format_output($buckets, $datesconfigured);
    }

    /**
     * Check whether a user holds a "student" role anywhere on the site.
     *
     * Students always see only their own attendance, never a linked
     * mentee's - even in the unusual case where the same account also has
     * a role assignment in another user's context. This mirrors
     * block_reportcard's is_student() check, so the two blocks behave
     * consistently for the same accounts.
     *
     * @param int $userid
     * @return bool
     */
    public static function is_student(int $userid): bool {
        global $DB;

        $sql = "SELECT 1
                  FROM {role_assignments} ra
                  JOIN {role} r ON r.id = ra.roleid AND r.archetype = :archetype
                 WHERE ra.userid = :userid";

        return $DB->record_exists_sql($sql, ['userid' => $userid, 'archetype' => 'student']);
    }

    /**
     * Find the students that this user is a parent/mentor of.
     *
     * This relies on Moodle's standard "role assigned in a user's context"
     * pattern (the same mechanism used by Preferences > "This user's role
     * assignment" on a student's profile page, and the same query
     * block_reportcard uses to find its mentees). Any role assigned to
     * $userid inside a specific student's user context makes that student
     * visible to $userid here, as long as the target account itself holds
     * a "student" archetype role somewhere - e.g. a custom Parent role
     * assigned on the student's profile page. No separate capability needs
     * to be granted for this to work.
     *
     * @param int $userid The prospective parent/mentor.
     * @return array of stdClass user records (id, name fields), sorted by name.
     */
    public static function get_mentee_users(int $userid): array {
        global $DB;

        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.firstnamephonetic,
                       u.lastnamephonetic, u.middlename, u.alternatename
                  FROM {role_assignments} ra
                  JOIN {context} ctx
                       ON ctx.id = ra.contextid
                      AND ctx.contextlevel = :contextlevel
                  JOIN {user} u ON u.id = ctx.instanceid
                  JOIN {role_assignments} ra2 ON ra2.userid = u.id
                  JOIN {role} r ON r.id = ra2.roleid AND r.archetype = :archetype
                 WHERE ra.userid = :userid
                       AND u.deleted = 0
                       AND u.suspended = 0";

        $params = [
            'contextlevel' => CONTEXT_USER,
            'archetype'    => 'student',
            'userid'       => $userid,
        ];

        $users = $DB->get_records_sql($sql, $params);

        if (empty($users)) {
            return [];
        }

        \core_collator::asort_objects_by_property($users, 'lastname');

        return array_values($users);
    }

    /**
     * Read the configured semester start/end dates from plugin settings and
     * convert them to unix timestamps. Dates are stored as plain YYYY-MM-DD
     * text so admins can change them every year without touching any code.
     *
     * @return array [sem1start, sem1end, sem2start, sem2end, alldatesconfigured]
     */
    protected static function get_semester_dates(): array {
        $config = get_config('block_attendance_summary');

        $sem1start = !empty($config->sem1start) ? strtotime($config->sem1start . ' 00:00:00') : 0;
        $sem1end   = !empty($config->sem1end)   ? strtotime($config->sem1end   . ' 23:59:59') : 0;
        $sem2start = !empty($config->sem2start) ? strtotime($config->sem2start . ' 00:00:00') : 0;
        $sem2end   = !empty($config->sem2end)   ? strtotime($config->sem2end   . ' 23:59:59') : 0;

        // strtotime() returns false on unparsable input - treat that as "not set".
        $sem1start = $sem1start ?: 0;
        $sem1end   = $sem1end ?: 0;
        $sem2start = $sem2start ?: 0;
        $sem2end   = $sem2end ?: 0;

        $configured = ($sem1start && $sem1end && $sem2start && $sem2end);

        return [$sem1start, $sem1end, $sem2start, $sem2end, $configured];
    }

    /**
     * Turn raw p/l/ua/ea/total count buckets into flat, mustache-friendly output.
     *
     * @param array $buckets
     * @param bool $datesconfigured
     * @return array
     */
    protected static function format_output(array $buckets, bool $datesconfigured): array {
        $out = ['datesconfigured' => $datesconfigured];

        foreach ($buckets as $key => $bucket) {
            $out[$key . '_p']       = $bucket['p'];
            $out[$key . '_l']       = $bucket['l'];
            $out[$key . '_ua']      = $bucket['ua'];
            $out[$key . '_ea']      = $bucket['ea'];
            $out[$key . '_total']   = $bucket['total'];
            $out[$key . '_hasdata'] = $bucket['total'] > 0;
        }

        return $out;
    }
}
