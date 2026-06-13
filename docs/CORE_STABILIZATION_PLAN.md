# Omurga Core Stabilization Plan

## Current Priority

The 1.1.x series focuses on making Omurga safer and more stable before large feature additions.

## Part 1 - Security & Stability

- Keep Core Guard active in production.
- Prevent developer mode from bypassing protected paths.
- Harden ZIP extraction against path traversal and symlink entries.
- Improve session cookie and timeout handling.
- Log blocked core write/delete attempts.

## Part 2 - Migration & Performance Stability

- Add central Migration Runner.
- Track applied, pending and failed migrations in the database.
- Keep old migration functions backward-compatible.
- Avoid repeated heavy table checks on every request when the schema is up to date.
- Add `/admin/migrations.php` for migration status.

## Next Parts

1. Error/exception handling dashboard improvements.
3. API rate limit/versioning.
4. Gradual bootstrap refactoring into core modules.
5. Test coverage for install, login, post editing, media upload, theme activation, package activation and SEO endpoints.
