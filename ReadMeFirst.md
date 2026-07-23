# Read Me First

## ⚠️ Do NOT use the green "Code" button to download this plugin

The green **Code > Download ZIP** button (and the auto-generated "Source code"
files on the Releases page) will **not** install correctly in Moodle. 

## ✅ Install from the Releases page instead

1. Go to the **[Releases](../../releases)** page for this repo.
2. Under the latest release, download **`block_attendance_summary.zip`**
   from the **Assets** list.
3. In Moodle, log in as a site administrator and go to:
   **Site administration > Plugins > Install plugins**
4. Drag in `block_attendance_summary.zip` (or click to browse and select it).
5. Click **Install plugin from the ZIP file**, then **Continue** on the
   plugin check screen.
6. Click **Upgrade Moodle database now** to finish.
7. Go to **Site administration > Plugins > Blocks > Attendance Summary** and
   set the four semester date fields.
8. Turn on editing on the Dashboard, then **Add a block > Attendance Summary**.

No FTP, SSH, or direct file access is needed — the whole install happens
through the Moodle admin UI.

## Requirements

- Moodle 4.5.x
- The `mod_attendance` activity module installed and in use in courses

## More details

See [`attendance_summary/README.md`](attendance_summary/README.md) in this
repo for full documentation, including how semester dates work and how to
link a parent/mentor account to a student.
