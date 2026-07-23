# Attendance Summary block (block_attendance_summary)

A Moodle 4.5 block that reads data straight from the **Attendance** activity
module (`mod_attendance`) and shows, on a user's Dashboard:

- Semester 1 Present / Late / Unexcused Absent / Excused Absent counts, plus a Total
- Semester 2 Present / Late / Unexcused Absent / Excused Absent counts, plus a Total
- Whole-year Present / Late / Unexcused Absent / Excused Absent counts, plus a Total

Semester date ranges are a plain admin setting — change them every year in
a few seconds, no code editing required.

## Requirements

- Moodle 4.5.x (built/tested against 4.5.1)
- The `mod_attendance` activity module installed and in use in courses

## Installation

1. Copy this folder into your Moodle installation as:
   `[moodleroot]/blocks/attendance_summary`
   (the folder must be named `attendance_summary`, not `block_attendance_summary`)
2. Log in as an admin and visit **Site administration > Notifications** to
   trigger the install, or run:
   ```
   php admin/cli/upgrade.php
   ```
3. Go to **Site administration > Plugins > Blocks > Attendance Summary**
   and set the four semester dates (see below).
4. Turn on editing on the Dashboard, add the **Attendance Summary** block.
   See "Who sees what" below for how access is determined per account.

## Configuring semester dates (do this every year)

Go to **Site administration > Plugins > Blocks > Attendance Summary**.
You'll see four plain text fields:

- Semester 1 start date
- Semester 1 end date
- Semester 2 start date
- Semester 2 end date

Enter dates as `YYYY-MM-DD`, e.g. `2026-08-10`. Attendance sessions falling
on or between the start/end dates are counted in that semester. Sessions
outside both ranges still count toward the **Total** figure, but not toward
either semester. If the four dates aren't all filled in, the block shows
a small warning and displays Total attendance only.

There is no code to touch when the school calendar changes — just update
these four fields.

## Who sees what

The block decides what to show, in this order:

1. **Students** (any account holding a role with the "student" archetype,
   anywhere on the site) always see only their own attendance — never a
   linked mentee's, even if that same account also happens to have a role
   assignment in another user's context.
2. **Parents / mentors** (any non-student account with a role assigned in
   one or more students' user context — see setup below) see one section
   per linked student, instead of their own data.
3. **Everyone else** (e.g. a teacher or manager account with no linked
   students, or any account not enrolled in a course with attendance
   tracking) sees a simple "You have no students or are not enrolled in a
   course." message instead of empty or irrelevant data.

This matches how the companion Report Card block (`block_reportcard`)
already determines parent/student access, so both blocks behave
consistently for the same accounts.

## Parents / mentors seeing their student's attendance

Parents (or any "mentor" account) can be linked to one or more students so
that when *they* view the block, they see a separate section per linked
student — this uses Moodle's standard mechanism for linking one account to
another (the same one used for "Preferences > This user's role assignment"
on a user's profile page, and the same mechanism `block_reportcard` uses).
No custom user field, extra table, or extra capability grant is needed;
this is a one-time setup per parent/student pair.

### One-time setup (per site)

1. **Create a "Parent" role**, if you don't already have one:
   Site administration > Users > Permissions > Define roles > Add a new role.
   - Base it on "No role" (or "Authenticated user"), whichever your site
     normally uses for parent accounts.
   - Under **Context types where this role may be assigned**, tick **User**.
   - Save. No extra capabilities need to be granted for this block —
     simply being assigned the role in a student's user context is enough.

   *(If you already have a Parent/Guardian role for other purposes — e.g.
   the one used for `block_reportcard` — you can reuse it here too, as
   long as it's assignable at User context.)*

### Linking a parent to a student (repeat per parent/student pair)

1. Go to the **student's** profile page.
2. **Preferences** > **Roles** > **This user's role assignment** (URL
   pattern: `/admin/roles/assign.php?contextid=<student's user context id>`).
3. Select the **Parent** role, then add the parent's account in the
   "potential users" list on the right and assign it.

That's it — the parent doesn't need to be enrolled in the student's
courses. As soon as this role assignment exists, the parent's Attendance
Summary block on their own Dashboard will automatically show that
student's Semester 1 / Semester 2 / Total attendance.

A parent linked to multiple children sees one section per child, labelled
with the child's name, sorted alphabetically by last name.

## How the counts are calculated

For each Attendance session where the logged-in student has an attendance
log entry (i.e. attendance was actually taken for them), the block:

1. Looks up the **acronym** of the status they were marked with (this comes
   from whatever status set is configured in each Attendance activity) and
   matches it, case-insensitively, against `P`, `L`, `UA`, `EA`.
2. Adds one to that student's Present / Late / Unexcused Absent / Excused
   Absent count, and one to their overall Total.
3. Sums these counts across every attendance activity in every course the
   student is actively enrolled in.

This means your Attendance activities need to use status acronyms of `P`
(Present), `L` (Late), `UA` (Unexcused Absent) and `EA` (Excused Absent) for
the block to categorise sessions correctly. A status with any other acronym
still counts toward the Total column, but won't be added to any of the four
specific columns.

Sessions are bucketed into Semester 1 / Semester 2 / Total using the
`sessdate` of each session against your configured semester dates.

## Notes / things worth knowing

- The block only appears on the **Dashboard** (`applicable_formats` limits
  it to `my`), since it's inherently per-user data. It's not meant to be
  added to a course page.
- It uses `enrol_get_users_courses($userid, true)` — only **active**
  enrolments count, so old/suspended enrolments are ignored.
- If `mod_attendance` isn't installed at all, the block shows a friendly
  notice instead of erroring.
- The block implements Moodle's privacy API as a `null_provider` — it
  stores no personal data itself (it only reads mod_attendance's own
  tables live, on each page load).
- Capabilities:
  - `block/attendance_summary:addinstance` (course/system context, teachers
    & managers by default)
  - `block/attendance_summary:myaddinstance` (lets ordinary users add it to
    their own Dashboard, on by default like other "my page" blocks)
- Students see only their own attendance. Parents/mentors linked to one or
  more students see **only** those students' sections. Anyone who is
  neither a student nor linked to any students sees a "You have no
  students or are not enrolled in a course." message instead.

## Possible future enhancements (not included)

- Caching the computed summary (e.g. via Moodle's Cache API) if you have a
  very large number of students/sessions and want to reduce DB load on
  every Dashboard view.
- A "view by course" breakdown, not just an overall total.
- Support for more than two semester periods (e.g. trimesters).

## File structure

```
attendance_summary/
├── block_attendance_summary.php     Main block class
├── settings.php                     Admin settings (semester dates)
├── version.php
├── styles.css
├── db/
│   └── access.php                   Capabilities (addinstance/myaddinstance)
├── lang/en/
│   └── block_attendance_summary.php Language strings
├── classes/
│   ├── local/
│   │   └── attendance_helper.php    Data-fetching / calculation logic
│   └── privacy/
│       └── provider.php             Privacy API (null_provider)
└── templates/
    └── summary.mustache             Output template
```
