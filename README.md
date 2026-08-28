# wordpress-lms

**Simple LMS** — a lightweight Learning Management System plugin for WordPress.

The plugin lives in [`simple-lms/`](simple-lms/). Copy that folder into
`wp-content/plugins/` (or symlink it) and activate it from the Plugins screen.

## What it does

| Area | Details |
|------|---------|
| Content types | `Course` and `Lesson` custom post types + hierarchical `Course Category` taxonomy |
| Curriculum | Assign each lesson to a course; order lessons with the standard **Order** field |
| Enrollment | Free self-enrollment for logged-in users (AJAX + REST), optional auto-enroll on free courses |
| Progress | Per-lesson "Mark as complete" toggle, live progress bar, automatic course completion |
| Gating | Non-enrolled visitors get a teaser + enroll CTA; lessons can be flagged **free preview**; optional "require login" |
| Storage | Dedicated tables `wp_slms_enrollments` and `wp_slms_progress` (not post meta) |
| Front end | Shortcodes only — no page-builder lock-in, no build step |
| Extensibility | Theme-overridable templates (`simple-lms/templates/` → `yourtheme/simple-lms/`), action/filter hooks, REST namespace `slms/v1` |

## Shortcodes

```
[slms_courses category="" per_page="12" columns="3" orderby="date" order="DESC"]
[slms_my_courses]
[slms_course_progress id="123"]
[slms_enroll_button id="123"]
[slms_lesson_list id="123"]
```

## REST API (`slms/v1`)

All endpoints need a logged-in user + `X-WP-Nonce` (`wp_rest`):

- `POST slms/v1/enroll` — `{ "course_id": 123 }`
- `POST slms/v1/lessons/123/complete` — `{ "complete": true }`
- `GET  slms/v1/courses/123/progress`

## Hooks

**Actions:** `slms_user_enrolled`, `slms_user_unenrolled`, `slms_lesson_completed`,
`slms_lesson_progress_updated`, `slms_course_completed`, `slms_loaded`
**Filters:** `slms_settings`

## Requirements

WordPress 6.0+, PHP 7.4+.

## Developer notes

- OOP with an SPL autoloader: `SLMS_Foo_Bar` → `includes/class-slms-foo-bar.php`
  (admin-only classes in `admin/`).
- Procedural helper API in `includes/slms-functions.php` is the supported surface
  for themes and other plugins.
- Activation creates the custom tables via `dbDelta` and grants course-management
  capabilities to the `administrator` and `editor` roles.
- Uninstall is non-destructive unless **Settings → "Delete all data on uninstall"**
  is enabled.

To regenerate the translation template:

```bash
wp i18n make-pot simple-lms simple-lms/languages/simple-lms.pot
```

## License

GPL-2.0-or-later.
