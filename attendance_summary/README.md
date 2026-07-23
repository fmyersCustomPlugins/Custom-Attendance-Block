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

## Installation (upload the ZIP — no file/folder access needed)

1. Log in as a site administrator.
2. Go to **Site administration > Plugins > Install plugins**.
3. Under "Install plugin from ZIP file," drag in `block_attendance_summary.zip`
   (or click to browse and select it).
4. Click **Install plugin from the ZIP file**. Moodle will validate it
   and show a plugin check screen — click **Continue**.
5. On the "Plugins check" page, click **Upgrade Moodle database now** to
   finish the install.
6. Go to **Site administration > Plugins > Blocks > Attendance Summary**
   and set the four semester dates (see below).
7. Turn on editing on the Dashboard, then **Add a block > Attendance
   Summary** (this is the block's display name — the plugin folder is
   `attendance_summary`). See "Who sees what" below for how access is
   determined per account.

No FTP, SSH, or direct file access is needed — this whole process happens
through the Moodle admin UI.

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

### Linking a parent to a student (repeat per parent/student pair)

1. Go to the **student's** profile page.
2. **Preferences** > **Roles** > **This user's role assignment** (URL
   pattern: `/admin/roles/assign.php?contextid=<student's user context id>`).
3. Select the **Parent** role, then add the parent's account in the
   "potential users" list on the right and assign it.

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
