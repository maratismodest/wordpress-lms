=== Simple LMS ===
Contributors: maratismodest
Tags: lms, courses, e-learning, education, learning management system
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight Learning Management System: courses, lessons, free enrollment and per-lesson progress tracking. No bloat, no page builder lock-in.

== Description ==

Simple LMS adds two post types — **Courses** and **Lessons** — plus enrollment and
progress tracking stored in dedicated database tables (not post meta), so it stays fast
on sites with thousands of students.

Features:

* Course and Lesson custom post types with a hierarchical **Course Category** taxonomy.
* Assign lessons to a course and order them with the standard "Order" field.
* Free self-enrollment for logged-in users (AJAX + REST), with optional auto-enroll.
* Per-lesson "Mark as complete" toggle and a live course progress bar.
* Automatic course completion when every lesson is done (fires `slms_course_completed`).
* Lesson gating: non-enrolled visitors see a teaser + enroll call-to-action. Lessons can
  be flagged as a **free preview**.
* Shortcodes for every front-end surface (see below).
* REST API namespace `slms/v1`.
* Theme-overridable templates: copy any file from `simple-lms/templates/` into
  `yourtheme/simple-lms/`.
* Clean uninstall (opt-in): drops tables, posts, terms, options and capabilities.

== Shortcodes ==

* `[slms_courses category="" per_page="12" columns="3" orderby="date" order="DESC"]` —
  responsive grid of courses with pagination.
* `[slms_my_courses]` — the logged-in user's enrolled courses with progress bars.
* `[slms_course_progress id="123"]` — a progress bar for one course (defaults to the
  current course/lesson context).
* `[slms_enroll_button id="123"]` — enroll / continue button.
* `[slms_lesson_list id="123"]` — course curriculum with completion ticks.

== REST API ==

All endpoints require a logged-in user and the standard `X-WP-Nonce` (`wp_rest`) header.

* `POST slms/v1/enroll` — body `{ "course_id": 123 }`
* `POST slms/v1/lessons/123/complete` — body `{ "complete": true }`
* `GET  slms/v1/courses/123/progress`

== Hooks ==

Actions: `slms_user_enrolled`, `slms_user_unenrolled`, `slms_lesson_completed`,
`slms_lesson_progress_updated`, `slms_course_completed`, `slms_loaded`.
Filters: `slms_settings`.

== Installation ==

1. Upload the `simple-lms` folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" screen.
3. You are redirected to **Courses → Settings**. Adjust options if needed.
4. Create a Course, then create Lessons and assign each to that course.
5. Add `[slms_courses]` to any page for a course catalogue, and `[slms_my_courses]`
   to a "My Courses" page.

== Frequently Asked Questions ==

= Does it support paid courses? =
Not in this version. Courses with a non-zero "Price" simply cannot be self-enrolled;
you can enroll users programmatically with `slms_enroll_user( $user_id, $course_id )`
or via another plugin.

= Where is progress stored? =
In two custom tables: `{prefix}slms_enrollments` and `{prefix}slms_progress`.

= How do I customise the course/lesson pages? =
Copy the relevant file from `simple-lms/templates/` into `yourtheme/simple-lms/` and
edit your copy.

== Changelog ==

= 1.0.0 =
* Initial release: courses, lessons, enrollment, progress tracking, shortcodes, REST API.
