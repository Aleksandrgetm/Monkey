# Running and operating Scan & Save

The Vue application lives in `frontend`; Laravel lives in `backend`. The completed local application uses <http://localhost:5176> and the API on port 8000. Run all commands below from the repository root unless the block changes directory. Requirements: the project's PHP/Composer and Node/npm versions, PHP PDO SQLite and `sqlite3`, and writable Laravel `storage`/`bootstrap/cache` directories. Dependencies are locked in the project lockfiles. The added `laravel/boost:^2.10` package is development tooling; no new frontend or production runtime dependency was introduced during this application phase.

## First setup

```sh
cd backend
composer install
```

If `backend/.env` does not exist, copy `.env.example` to `.env`, then run `php artisan key:generate`. Do not overwrite an existing `.env` or rotate an existing application key during an update. Set:

```dotenv
APP_NAME="Scan & Save"
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:5176
DB_CONNECTION=sqlite
SESSION_DRIVER=database
MAIL_MAILER=log
```

Create `backend/database/database.sqlite` only if absent, then apply migrations:

```sh
cd backend
php artisan migrate
```

Registration creates a normal user. No default administrator password is seeded. Create an administrator explicitly from a terminal:

```sh
cd backend
php artisan scan:create-admin admin@example.test --name=Administrator
```

The command prompts twice for a hidden password of at least 12 characters. The username accepts 3–30 Unicode letters/digits, including Latvian letters. It refuses unattended execution, duplicate email, or invalid input. Use an email address of at most 30 characters, following the PDF model. Do not pass passwords in shell arguments.

## Start the complete application

Terminal 1, from the repository root:

```sh
cd backend/public
php -d upload_max_filesize=10M -d post_max_size=12M -S 127.0.0.1:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
```

These PHP overrides permit the application's documented uploads. Plain `php artisan serve` uses the machine's PHP limits; the current machine defaults to 2 MiB per upload. Passing `-d` only to `artisan serve` does not configure its child server. The application accepts PDF/JPEG/PNG content strictly smaller than 10 MiB and rejects larger or unsupported files itself.

Terminal 2:

```sh
cd frontend
npm ci
npm run dev -- --host 127.0.0.1
```

Open <http://localhost:5176>. Use that origin consistently; Vite proxies `/api` to Laravel on port 8000. Authentication uses an HTTP-only Laravel session cookie and a CSRF bootstrap/token; no bearer password or session token is stored in browser local storage.

Terminal 3, for automatic warranty reminders during development:

```sh
cd backend
php artisan schedule:work
```

A manual reminder pass is available with `php artisan scan:send-reminders`. It uses Europe/Riga calendar dates, respects blocked accounts and notification preferences, and deduplicates events by document, expiration date, and warranty status. The expiration day itself is still valid and appears as expiring. The scheduler runs this command daily at **08:00 Europe/Riga**. Default lead time is 30 days; users can inherit the system setting or choose an explicit 0–365-day period. Changing appearance preserves reminder inheritance. Warranty amount input follows the confirmed maximum **99999.99 €**.

## Mail and deployment

`MAIL_MAILER=log` keeps reset links and reminder messages in Laravel's private local log. It does not deliver real email. For deployment, set an actual SMTP transport (`MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_SCHEME`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`) and `FRONTEND_URL` to the deployed SPA URL. Keep these secrets outside Git. Clear/rebuild Laravel configuration after changing environment values. Reset links expire and can be used only once.

Serve the built Vue assets and Laravel `/api` under the same HTTPS origin; route unknown frontend paths to the SPA and keep `/api` handled by Laravel. Use `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, and `SESSION_SECURE_COOKIE=true`. Configure PHP-FPM/web-server request limits to permit a 10 MiB file plus multipart overhead (for example, `upload_max_filesize=10M`, `post_max_size=12M`, equivalent proxy body limit). The built-in PHP and Vite servers are for local development.

Install a server cron entry that runs `php artisan schedule:run` every minute from `backend`. No hosted cron, SMTP account, deployment domain, offsite backup destination, or external uptime service is configured by this repository. The reminder command currently sends through Laravel's configured mail transport during its run; check its exit status and logs. The scheduler process and its database must remain available for automatic reminders; closing the development terminal stops `schedule:work`. Production reminders require the cron/service setup above.

## Backups and recovery

The operational commands currently support the configured local SQLite database. Documents are stored as private database BLOBs, so the database snapshot includes all file contents, users, categories, products, notifications, and settings. There is no public document directory to copy. Other database drivers require their vendor's backup/restore tooling before deployment on that driver.

Create a consistent SQLite snapshot:

```sh
cd backend
php artisan scan:backup
```

The command uses SQLite's online backup API, validates database integrity, and writes a private backup directory under `storage/app/private/backups`. Its `database.sqlite` and `manifest.json` have mode 0600; the directory has mode 0700. `--directory=/private/backup/location` chooses a different destination. The manifest records format version, timestamp, size, and SHA-256. Copy the complete directory to protected offsite storage and apply an operator-selected retention policy. The local manifest detects accidental corruption; keep backups and manifest together in trusted storage.

Verify a selected backup without changing data:

```sh
cd backend
php artisan scan:restore /absolute/path/to/backup-directory --verify
```

Recovery replaces current application data. Stop web traffic, scheduler, and any queue workers; take maintenance mode first. Use only a backup from trusted storage. Verify it before restoring:

```sh
cd backend
php artisan down
php artisan scan:restore /absolute/path/to/backup-directory --verify
php artisan scan:restore /absolute/path/to/backup-directory --force
php artisan migrate --force
php artisan up
```

Restore refuses to run without both `--force` and maintenance mode. It checks the checksum, expected tables, and SQLite integrity before modifying anything, and makes a separate safety snapshot of the current database. A failed validation leaves current data unchanged. Restore validation tests operate only on temporary databases, never on the development database. After recovery, verify login, document view/download, lists and settings, then restart the scheduler/workers. Keep the safety snapshot until these checks pass. Back up the deployed `.env`/application key separately through protected server-secret storage; these files are intentionally not included in database backups.

## Updates and monitoring

Before an update, review the release and required migrations, run the tests/build in staging, create a backup, and verify it. Enter maintenance mode and stop scheduler/workers. Install the locked PHP and frontend dependencies, build the frontend, apply migrations, clear/rebuild configuration caches, then bring the app up and restart scheduled work. Typical release steps after a reviewed code update:

```sh
cd backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
```

```sh
cd frontend
npm ci
npm run build
```

Switch the web server to the built release, then run `php artisan up` from `backend` and perform health/login/document smoke checks. If migration fails, keep maintenance enabled. If checks fail after bringing the release up, run `php artisan down` again and restore the reviewed prior release and verified database snapshot using the recovery procedure. Do not use `migrate:fresh` on a database with real user data.

`GET /up` checks that Laravel can boot; it does not independently query the database. The administrator's health screen additionally reports database status, configured mail transport, the last completed scheduler/reminder pass, and the last successful backup. A missing timestamp means the corresponding operation has not run. Review:

- `/up` availability and HTTP errors through your hosting monitor;
- the scheduler timestamp relative to the daily 08:00 Europe/Riga reminder schedule;
- successful daily backups and available disk space;
- `storage/logs/laravel.log` for application/mail failures;
- expiring/expired counts and storage usage in admin statistics.

External alerts and offsite backup automation require the deployment operator's services and credentials. The UI alone does not install them.

## Verification

```sh
cd backend
php artisan test --compact
```

```sh
cd frontend
npm run build
```

Verification completed on 2026-10-06:

- Backend: **47 tests, 456 assertions passed**; `vendor/bin/pint --dirty --format agent` passed.
- Frontend: `npm run build` passed, including TypeScript checking.
- Real browser flows: registration/login/logout; upload/edit/search/download and valid PDF preview; category/product creation and linking; password change followed by a successful CSRF-protected preference save; notification read state; admin CSV/moderation and ordinary-user rejection from admin UI/API; account deletion cancel/confirm with database-verified cleanup; mobile Escape/focus and light/dark appearance.
- Responsive review: all 19 authenticated route patterns at **375, 430, 768, 1024, 1440 and 1920 px**, plus auth/public widths; no horizontal overflow or runtime errors in the reviewed flows. Existing homepage appearance is preserved, and reduced-motion hero rendering was checked.
- Temporary E2E accounts and their associated test records were cleaned up after verification. Restore tests did not modify the development database.

Backend feature tests exercise real migrations, authentication/reset/session revocation, CSRF, Unicode validation, user/admin authorization, unauthenticated API/file/admin requests with or without JSON Accept headers, deleted-user sessions, blocked sessions, private BLOB upload/download/replacement/deletion, real MIME and byte limits, owner/category isolation, combined search/filter/sort, warranty boundaries, reminder preferences/deduplication/read state, last-admin protections, interactive admin creation and safe backup/restore. The corrective schema upgrade is separately tested against an existing temporary database, confirming preserved records, BLOB content and notification timestamp behavior. Mail is faked in tests; no external email was delivered.

Both application migrations have been applied to the verified local instance and must also be applied when setting up another instance: `2026_10_06_104750_create_scan_save_tables` and `2026_10_06_111657_align_scan_save_column_types_with_documentation`. Use normal `php artisan migrate` when updating existing data. The corrective migration aligns physical column types while the documented form length limits remain enforced in validation.

See [IMPLEMENTATION.md](IMPLEMENTATION.md) for the `[done]`/`[partial]` checklist, models/migrations, frontend/backend component inventory, all 48 API routes, authorization rules and detailed verification. No application item remains `[blocked]`; real SMTP delivery, persistent production scheduling, offsite retention/alerts and production HTTPS hosting remain `[partial]` until the operator supplies the named configuration.
