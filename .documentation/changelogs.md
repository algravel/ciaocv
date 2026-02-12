# Changelog

## [Unreleased] - 2026-02-12

### Added
- **Data Retention Policy**: Implemented automated data purges to comply with GDPR/Law 25.
    - `gestion/cron_retention.php`: Script to be run daily via cron.
    - Deletes video and CV files from Cloudflare R2 **60 days** after a job posting ends.
    - Permanently deletes candidate records from the database **1 year** after a job posting ends.
- **R2 File Deletion**: Added `deleteFile()` method to `app/helpers/R2Signer.php` to support programmatic deletion of files on Cloudflare R2 using AWS Signature V4.
- **Product Roadmap**: Created a comprehensive [`product_roadmap.md`](product_roadmap.md) detailing current features and proposed future enhancements (AI insights, advanced evaluation, etc.).

### Changed
- **Database Migrations**:
    - Extracted auto-migration logic from `gestion/config.php` to a dedicated script `gestion/migrate.php`.
    - Migrations now must be run manually (or via deployment script), significantly improving response times for every request.
- **PlatformUser Model Optimization**:
    - Refactored `gestion/models/PlatformUser.php` to remove redundant `SHOW COLUMNS` queries.
    - The model now assumes a stable schema (managed by `migrate.php`), reducing database load and improving performance of user operations.

### Verified
- **Application Portal**: Confirmed the existence and functionality of:
    - Mobile-responsive design.
    - Device (camera/microphone) validation steps.
    - Browser-based video recording with retake capability.
    - Secure, direct-to-cloud (R2) file uploads via presigned URLs.
