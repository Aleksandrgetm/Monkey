# Scan & Save: PDF requirements and implementation contract

Source: Scan and save 2.pdf, all 27 pages reviewed (2026-10-06). The homepage is preserved. The map below describes the implemented contract; verified evidence and remaining deployment configuration are recorded at the end.

## Decisions and discrepancies

- Pages 4–5 input rules govern form validation: Unicode letters/digits username 3–30, unique email ≤30, password ≥5 characters, optional title ≤255, note ≤300, PDF/JPEG/PNG strictly less than 10 MiB. Sketches' first/last name and 8-character placeholder do not override these explicit rules.
- Physical schema pages 10–11/18 uses binary file content. Store in a database BLOB (`BYTEA` under PostgreSQL), hidden from JSON; expose only authorized streamed responses. No public storage link.
- Amount: physical schema DECIMAL(7,2), maximum **99999.99 €**. Page 5 says 7 integer digits; user confirmed the physical-schema limit (2026-10-06).
- Physical column types are aligned by an additive corrective migration: `users.name` VARCHAR(100), document/product notes TEXT, notification message TEXT, notification date TIMESTAMP. Form limits remain 3–30 characters for username and 300 for notes. Existing data and document BLOBs survive the upgrade in the schema-preservation tests.
- Required per-owner category (ER one category per file). Category deletion rejected while referenced, with useful error.
- Add `products` because page 7 explicitly links documents to products and page 24 shows multiple files per product. Optional document `product_id`; no unrelated inventory functions. Product metadata: name, category_id, merchant, amount, purchase_date, warranty_end_date, note. Document warranty remains authoritative; product dates prefill document inputs only.
- Add `kind` (receipt/warranty/other) and `merchant` to documents for tabs/shop filters in appendices; `file_type` remains actual validated MIME.
- Add user notification preferences and appearance from page 27; system settings for registration and reminder lead days. Default lead 30 days, configurable (PDF does not specify calculation threshold).
- Expiration date today is still valid (expiring); expired only before today, in Europe/Riga. Per-date/status notifications deduplicated.
- Application uses Laravel session cookies through same-origin `/api` Vite proxy; CSRF token bootstrap via GET `/api/auth/csrf`. No credentials stored in browser localStorage. CSRF bootstrap is refreshed after authentication/password changes and cleared when the session ends.
- Operational modules: local SQLite backup/restore commands, health monitoring, documented update procedure; external production mail/scheduler/offsite backup need deployment configuration.

## Requirement map

| PDF | Requirement | Frontend | API (prefix /api) / backend | Entity / authorization |
|---|---|---|---|---|
| 3,14 | Public information | existing `/` | public | guest |
| 3–4,19–20 | Register/login/logout/reset | `/register`, `/login`, `/forgot-password`, `/reset-password` | auth/csrf, register, login, logout, me, forgot-password, reset-password; Laravel session/password broker | users; active accounts; rate limiting |
| 3,7,27 | Profile/password/delete | `/app/settings` | GET/PATCH/DELETE profile, PUT profile/password, GET profile/export | own user; current password for sensitive changes; confirmation |
| 4–6,10–12,21–23 | Upload/read/update/delete/view/download | `/app/documents`, `/app/receipts`, `/app/warranties`, detail/editor | documents CRUD, GET documents/{id}/file, /download | documents; owner for all operations; admin only via admin routes |
| 7,10,24 | Categories | `/app/categories` | categories CRUD | categories owner; no cross-owner association |
| 7,24 | Products and associated files | `/app/products`, detail/editor | products CRUD | products owner, own category/document associations |
| 5–7,21–26 | Search/filter/sort | lists and `/app/search` | documents query search/category_id/product_id/merchant/kind/warranty_status/date_from/date_to/sort/direction; products query | scoped at SQL level; validated enum sorts |
| 7,15,23 | Warranty calculation | badges/tabs | WarrantyService, date-based filters | document dates; active/expiring/expired |
| 6–7,11–12,25 | In-app/email reminders/read state | `/app/notifications` | notifications GET/PATCH id, POST read-all; warranty generation command/scheduler + Laravel mail | notifications owner + document relation, new/read |
| 7,25,27 | Notification preferences, appearance | `/app/settings` | PATCH profile/preferences | user preferences |
| 4–7,14–15 | User admin/search/filter/sort/block/role/delete | `/admin/users` | admin/users GET/PATCH/DELETE | admin gate on every endpoint, protect last active admin |
| 4,7,15 | Review/correct/moderate system data | `/admin/documents` | admin/documents GET/GET id/PATCH/DELETE | explicit admin routes/policies |
| 7,15 | Statistics/reports/settings | `/admin`, `/admin/settings` | admin/stats, admin/report CSV, admin/settings GET/PATCH, admin/health | admin only |
| 7,15 | Backup/recovery/updates/monitoring | operational docs/commands + admin health | scan:backup, scan:restore; `/up`; update runbook | server operator; no public restore endpoint |
| 4–6 | Validation, success/error/empty states | shared UI errors/status/loading | Form Requests, 401/403/404/422/429 | all writes/queries checked backend |

## API contract

JSON objects (no `data` wrapper) for detail/create/update. Paginated lists: `{data: T[], current_page, last_page, total, per_page}`. Categories: `{data: Category[]}`. All exceptions `{message, errors?}`. Dates ISO YYYY-MM-DD; timestamps ISO. Currency serialized decimal string. Integer role 0=user/1=admin; status 1=active/0=blocked. Request `confirmed: true` for destructive deletes (plus current password for own account).

User: id,name,email,role,status,created_at,email_notifications,in_app_notifications,reminder_days (effective integer),reminder_days_override (nullable stored override),appearance ('light'|'dark'|'system').
Category: id,name,documents_count,products_count.
Product: id,name,category_id,category?,merchant,amount,purchase_date,warranty_end_date,note,documents_count,documents? (detail).
Document: id,user_id,category_id,product_id,category?,product?,owner? (admin),name,file_name,file_type,file_size,kind,merchant,amount,purchase_date,warranty_end_date,note,warranty_status ('active'|'expiring'|'expired'|null),created_at.
Notification: id,document_id,message,kind ('warranty'|'system'),status (0=new/1=read),notification_date (ISO timestamp),document? (id/name/file_name),created_at.

Auth register {name,email,password,password_confirmation}; login {email,password,remember}; responses {user}. GET auth/me {user}. CSRF {token}. POST auth/forgot-password {email}; reset {email,token,password,password_confirmation}. Logout empty 204.
Profile GET {user}; PATCH {name,email,current_password?} returns {user}; PUT password {current_password,password,password_confirmation}. PATCH preferences {email_notifications?,in_app_notifications?,reminder_days?,appearance?} returns {user}. `reminder_days: null` inherits the system default; explicit values are 0–365. Saving appearance does not overwrite notification preferences. Profile export JSON download.
Document POST multipart file mandatory, category_id mandatory; all other metadata optional except kind default other. PATCH metadata; replacement through POST id with `_method=PATCH` + optional file. GET file returns inline content; download attachment. Lists sort created_at/name/amount/purchase_date/warranty_end_date, direction asc/desc. GET dashboard {documents,receipts,warranties,products,expiring,unread,recent:Document[]}.
Notifications filters status(0|1),kind(warranty|system); PATCH id {status:1}; POST read-all. Products list search/category_id/merchant/sort/direction, sort name/created_at/amount/purchase_date. Category POST/PATCH {name}.
Admin users filters search/role/status/sort(name|email|created_at)/direction; PATCH {name?,email?,role?,status?}. Admin documents use document query + user_id; PATCH metadata category/product must belong to document's owner. Stats {users,blocked_users,documents,products,categories,notifications,storage_bytes,expiring,expired}; health {database,scheduler_last_run,mail_mailer,last_backup_at}. Settings {registration_enabled,reminder_days}; report CSV.

## Verified delivery checklist (2026-10-06)

`[done]` means implemented with backend/database/authorization coverage and connected UI where applicable. `[partial]` identifies the remaining external deployment configuration. No application requirement is currently classified `[blocked]`; the partial items below name the missing operator configuration explicitly.

| Status | PDF requirement | Verified implementation and evidence |
|---|---|---|
| [done] | Public information; preserve homepage (pp. 3, 14) | Existing homepage retained, with login/registration integration. Public widths and reduced-motion rendering were checked; hero remains visible. |
| [done] | Guest registration/login/logout/password recovery (pp. 3–4, 19–20) | Session authentication, hashed passwords, Unicode username validation, unique email, normal-user registration, rate limiting, one-time reset token and session revocation. Browser registration/login/logout passed; reset token and rendered Latvian reset email tested on backend. Real external mail delivery remains partial below. |
| [done] | Profile/settings/account deletion (pp. 3, 7, 27) | Profile/email/password forms, current-password validation, confirmation before deletion, cascade cleanup, metadata export, light/dark/system appearance, notification preferences with inherited or explicit reminder days. Password-change followed by another CSRF-protected preference save passed in browser. Account deletion was checked through cancel then confirm; database inspection confirmed removal of the user, owned records and sessions. |
| [done] | Upload/CRUD/private file access (pp. 4–6, 10–12, 21–23) | Private database BLOB storage, PDF/JPEG/PNG MIME validation, upload/edit/replace/delete/view/download, metadata and confirmation flows. Browser upload/edit/download and real PDF preview passed; backend ownership checks include file endpoints and forged inputs. |
| [done] | Exact input constraints (pp. 4–5) | Username 3–30 letters/digits; email unique and ≤30; password ≥5; optional title ≤255; note ≤300; file strictly <10 MiB; confirmed maximum **99999.99 €**; valid dates and warranty chronology. Actual file fixtures test 10 MiB rejection and 10 MiB minus one byte acceptance. |
| [done] | Categories/products/relationships (pp. 7, 10, 24) | Owned CRUD and associations, refusal to delete referenced categories, product-linked documents, null product relation on product deletion. Browser category/product creation and document linking passed; cross-owner associations rejected by tests. |
| [done] | Dokumenti/Čeki/Garantijas/Produkti lists and search/filter/sort (pp. 5–7, 21–26) | Lists query actual owner-scoped data with pagination, combined search/category/shop/type/warranty/date filters and allowed sorts. Browser search and backend combined-query assertions passed. |
| [done] | Date-based warranty states (pp. 7, 15, 23) | Central `WarrantyService`; Europe/Riga date boundaries; expiry day still valid; inclusive lead threshold; active/expiring/expired labels. Admin filtering uses each document owner's reminder period. |
| [done] | In-app reminders/read state/preferences (pp. 6–7, 11–12, 25, 27) | Scheduled command, per-document/date/status deduplication, timestamped notifications, owner-scoped read/read-all, blocked-account exclusion, email/in-app preferences. Backend command tests and browser read-state flow passed. |
| [done] | Administration: users and permissions (pp. 4–7, 14–15) | Search/filter/sort, block/unblock, role changes, confirmed deletion, current-user state handling and last-active-admin protection. All admin API access checked server-side; ordinary users rejected in tests. |
| [done] | Administration: data, statistics/reports/settings (pp. 7, 15) | Explicit admin document moderation/file access, owner-safe associations, counts/storage/expiry statistics, CSV report, registration toggle and default reminder days. Browser moderation and CSV export passed. |
| [done] | Database model/migrations (pp. 10–12, 18) | Core user/category/document/notification relationships, product extension grounded in PDF, binary content, foreign keys, nullable relations and deletion rules. Additive corrective migration aligns physical types; existing data, BLOBs and timestamps preserved in isolated upgrade tests. |
| [done] | Backup/recovery/updates/monitoring implementation (pp. 7, 15) | SQLite snapshot/verify/guarded restore with safety backup, interactive admin creation, boot health endpoint, admin database/scheduler/backup health fields and update/recovery runbook. Binary round-trip/corruption/maintenance requirements tested only in temporary databases. Production operations configuration remains partial below. |
| [done] | Responsive application, navigation and states | Frontend TypeScript/build passed. Browser review covered all 19 authenticated route patterns at 375, 430, 768, 1024, 1440 and 1920 px, plus auth/public widths, without horizontal overflow. Mobile navigation Escape/focus, light/dark presentation, reduced motion, validation/loading/empty/error displays and runtime console were checked. |
| [partial] | Real email delivery | Laravel reset/reminder mail architecture and Latvian templates are implemented and tested. Supply production SMTP credentials/from identity and deployed `FRONTEND_URL`; local `MAIL_MAILER=log` does not deliver external email. No external mail was sent during verification. |
| [partial] | Continuous production reminder scheduling | Daily 08:00 Europe/Riga schedule is defined and command logic is tested. A deployment operator must install/keep running cron or a scheduler service; a local development process is not persistent hosting. |
| [partial] | Offsite backup retention and external monitoring | Local backup/restore and health reporting are implemented/tested. Supply protected offsite storage, retention schedule, backup automation, uptime/alert destination and operators. Non-SQLite deployment needs the selected database vendor's backup tooling. |
| [partial] | Production hosting/HTTPS | Supply deployment host/domain/TLS, same-origin SPA/API routing, production environment/secrets and web-server upload limits. Local browser verification does not constitute production deployment. |

## Verification evidence

- **Backend:** `php artisan test --compact` — **47 tests, 456 assertions, all passed**. `vendor/bin/pint --dirty --format agent` passed. Backend verification includes the corrective-schema upgrade/preservation tests, unauthenticated API requests without JSON Accept headers, HTML Accept headers, private file/admin requests, and deleted-user sessions. These return 401 instead of trying to resolve a nonexistent server-rendered login route.
- **Frontend:** `npm run build` passed (`vue-tsc -b` and Vite production build).
- **Browser:** registration, login/logout, document upload/edit/search/download and valid PDF preview, product/category creation and linking, password change followed by CSRF-protected preferences update, notification read state, admin CSV export/moderation, mobile Escape/focus behavior and dark/light appearance passed. No runtime errors were observed in the reviewed flows. Responsive coverage is stated explicitly in the checklist above.
- **Migrations:** both new application migrations are applied in the verified local instance; setup/updates use normal `php artisan migrate`, preserving existing data.
- **Routing/scheduling:** `php artisan route:list --path=api --except-vendor` enumerates 48 routes; `php artisan schedule:list` registers reminders daily at 08:00 Europe/Riga.
- **Data handling:** temporary end-to-end verification accounts and associated records were cleaned up. Test fixtures are separate from application data. Restore tests and schema-upgrade preservation tests used temporary SQLite databases; no development-database restore was performed. No insecure default administrator credential is included.

## Created/modified implementation inventory

Paths below are repository-relative. This inventory covers the application work; the prior homepage and existing stack remain in place.

### Migrations and models

| File | Purpose |
|---|---|
| `backend/database/migrations/2026_10_06_104750_create_scan_save_tables.php` | Adds user roles/status/preferences; creates categories, products, documents with private binary content, notifications and system settings, indexes and foreign keys. |
| `backend/database/migrations/2026_10_06_111657_align_scan_save_column_types_with_documentation.php` | Additive schema alignment: username storage length 100, notes/message TEXT, notification timestamp; preserves existing records. |
| `backend/app/Models/User.php` (modified) | Authentication, role/status, preferences, owner relationships and effective reminder period. |
| `backend/app/Models/Category.php` (created) | Owned categories and linked documents/products. |
| `backend/app/Models/Product.php` (created) | Owned product metadata and document associations. |
| `backend/app/Models/Document.php` (created) | Owned file metadata, private BLOB, category/product and notification relations. |
| `backend/app/Models/Notification.php` (created) | Owner/document-linked reminder, timestamp/read/channel/deduplication state. |
| `backend/app/Models/SystemSetting.php` (created) | Persisted registration/reminder settings and operational timestamps. |

Factories: `UserFactory.php` (modified), `CategoryFactory.php`, `ProductFactory.php`, `DocumentFactory.php`, `NotificationFactory.php` in `backend/database/factories`. `DatabaseSeeder.php` initializes non-secret system defaults; `CategorySeeder.php` can add starter categories to existing users. Registration creates Latvian starter categories. Factory records are development/test helpers, not the application's data source; no default-user/admin password is seeded.

### Backend components and authorization

- Controllers in `backend/app/Http/Controllers/Api`: `AuthController`, `ProfileController`, `DashboardController`, `DocumentController`, `CategoryController`, `ProductController`, `NotificationController`, `AdminController`; base `Controller` supplies consistent pagination.
- Form Requests in `backend/app/Http/Requests`: `AuthRequest`, `ProfileRequest`, `DocumentRequest`, `DocumentQueryRequest`, `CategoryRequest`, `ProductRequest`, `AdminRequest`.
- JSON Resources in `backend/app/Http/Resources`: `UserResource`, `CategoryResource`, `ProductResource`, `DocumentResource`, `NotificationResource`.
- Services in `backend/app/Services`: `AccountService` (last-admin protection/session revocation/deletion), `DocumentStorage` (private BLOB streaming), `DocumentQuery` (owned query composition), `WarrantyService` (date/status rules), `BackupService` (SQLite snapshot/verify/restore).
- Middleware: `EnsureActiveUser` rejects and logs out blocked accounts; `EnsureAdmin` guards every admin route. Policies: `DocumentPolicy`, `CategoryPolicy`, `ProductPolicy`, `NotificationPolicy`. Ordinary document/category/product/notification endpoints only expose the current owner's data. Administrator cross-owner access is explicit through admin endpoints; associations stay with the document owner. Browser checks also confirm ordinary users are redirected from `/admin` to `/app`; direct admin API requests are rejected with 403. Sensitive profile changes require the current password; destructive API operations require confirmation. The final active administrator cannot be blocked, demoted or deleted.
- Operational commands in `backend/app/Console/Commands`: `BackupDatabase` (`scan:backup`), `RestoreDatabase` (`scan:restore`), `CreateAdmin` (`scan:create-admin`), `SendWarrantyReminders` (`scan:send-reminders`). Schedule definition: `backend/routes/console.php`.
- Mail/provider/configuration: `backend/app/Mail/WarrantyReminder.php`, Latvian email views under `backend/resources/views/emails`, `AppServiceProvider.php` for reset email/rate limits/resource behavior, `config/app.php`/`.env.example` for application/frontend URL configuration, `bootstrap/app.php` for API JSON exceptions and guest handling, and `routes/web.php` for the session API.

### Frontend pages, stores and services

| Routes | Page/component file under `frontend/src` |
|---|---|
| `/` | Existing `views/HomeView.vue` and home components; authentication navigation connected without redesign. |
| `/login`, `/register`, `/forgot-password`, `/reset-password` | `views/auth/AuthView.vue` |
| `/app` | `views/app/DashboardView.vue` |
| `/app/documents`, `/app/receipts`, `/app/warranties`, `/app/search` | `views/app/DocumentsView.vue` |
| `/app/documents/new`, `/app/documents/:id/edit` | `views/app/DocumentEditView.vue` |
| `/app/documents/:id` | `views/app/DocumentDetailView.vue` |
| `/app/products` | `views/app/ProductsView.vue` |
| `/app/products/new`, `/app/products/:id/edit` | `views/app/ProductEditView.vue` |
| `/app/products/:id` | `views/app/ProductDetailView.vue` |
| `/app/categories` | `views/app/CategoriesView.vue` |
| `/app/notifications` | `views/app/NotificationsView.vue` |
| `/app/settings` | `views/app/SettingsView.vue` |
| `/admin` | `views/admin/AdminOverview.vue` |
| `/admin/users` | `views/admin/AdminUsers.vue` |
| `/admin/documents` | `views/admin/AdminDocuments.vue` |
| `/admin/settings` | `views/admin/AdminSettings.vue` |
| `/connection-error`, unmatched paths | `views/ConnectionError.vue`, `views/NotFoundView.vue` |

- `layouts/AppLayout.vue`: shared user/admin layout and responsive keyboard-operable navigation.
- `router/index.ts`: lazy routes, authentication/guest/admin guards, redirects and page titles.
- `stores/auth.ts`: Pinia session user state and login/register/logout lifecycle.
- `services/api.ts`: same-origin API requests, CSRF bootstrap/reset, validation/server errors, authenticated downloads.
- `types/index.ts`: shared API models/pagination; `composables/useDocumentHelpers.ts`: document formatting/query helpers.
- Shared components: `components/app/ConfirmDialog.vue`; `components/documents/DocumentTable.vue`, `WarrantyBadge.vue`, `FieldError.vue`, `DeleteConfirmation.vue`.
- Integration/styles: `App.vue`, `main.ts`, `application.css`, `vite.config.ts` (port 5176 and `/api` proxy); existing homepage navigation connects to auth routes. The existing GSAP homepage motion remains separate from application interactions.

### Tests and dependencies

Critical feature suites in `backend/tests/Feature`: `AuthProfileTest`, `SessionSecurityTest`, `DocumentManagementTest`, `AdministrationTest`, `WarrantyNotificationTest`, `OperationsTest`, `UnicodeProfileTest`, `SchemaCompatibilityTest`. They cover actual migrations/models/API behavior, disk-backed upload fixtures, faked mail, and isolated recovery/schema upgrades. Existing scaffold tests remain included in the reported total.

**Dependency added for this full-application phase:** `laravel/boost:^2.10` as a development dependency, required by the original backend project instructions and installed through the approved Composer operation. Its installation updates `backend/composer.json`/`composer.lock`, adds `backend/boost.json`, and generates backend `AGENTS.md`/`CLAUDE.md` guidance updates. No new frontend dependency or application runtime dependency was added. Existing Vue 3/TypeScript/Vite/Vuetify/Vue Router/Pinia and Laravel packages are reused; GSAP was already present from the earlier homepage phase. No new authentication/storage package or external service SDK is required. SQLite operations use the installed PHP `sqlite3` extension.

## Complete API endpoint inventory

Generated from the current Laravel route listing. All paths are under `/api`; GET routes also accept HEAD. `Public` means no authenticated session is required, but write routes still use session CSRF protection and auth rate limits. `User` means an authenticated active account plus ownership checks where relevant; `Admin` additionally requires administrator role. No public backup/restore endpoint exists.

| Method | Endpoint | Access | Controller action |
|---|---|---|---|
| GET | `/api/admin/documents` | Admin | `DocumentController@index` |
| GET | `/api/admin/documents/{document}` | Admin | `DocumentController@show` |
| PATCH | `/api/admin/documents/{document}` | Admin | `DocumentController@update` |
| DELETE | `/api/admin/documents/{document}` | Admin | `DocumentController@destroy` |
| GET | `/api/admin/documents/{document}/download` | Admin | `DocumentController@file` |
| GET | `/api/admin/documents/{document}/file` | Admin | `DocumentController@file` |
| GET | `/api/admin/health` | Admin | `AdminController@health` |
| GET | `/api/admin/report` | Admin | `AdminController@report` |
| GET | `/api/admin/settings` | Admin | `AdminController@settings` |
| PATCH | `/api/admin/settings` | Admin | `AdminController@updateSettings` |
| GET | `/api/admin/stats` | Admin | `AdminController@stats` |
| GET | `/api/admin/users` | Admin | `AdminController@users` |
| PATCH | `/api/admin/users/{user}` | Admin | `AdminController@updateUser` |
| DELETE | `/api/admin/users/{user}` | Admin | `AdminController@deleteUser` |
| GET | `/api/auth/csrf` | Public | `AuthController@csrf` |
| POST | `/api/auth/forgot-password` | Public | `AuthController@forgotPassword` |
| POST | `/api/auth/login` | Public | `AuthController@login` |
| POST | `/api/auth/logout` | User | `AuthController@logout` |
| GET | `/api/auth/me` | User | `AuthController@me` |
| POST | `/api/auth/register` | Public | `AuthController@register` |
| POST | `/api/auth/reset-password` | Public | `AuthController@resetPassword` |
| GET | `/api/categories` | User | `CategoryController@index` |
| POST | `/api/categories` | User | `CategoryController@store` |
| GET | `/api/categories/{category}` | User | `CategoryController@show` |
| PUT / PATCH | `/api/categories/{category}` | User | `CategoryController@update` |
| DELETE | `/api/categories/{category}` | User | `CategoryController@destroy` |
| GET | `/api/dashboard` | User | `DashboardController` |
| GET | `/api/documents` | User | `DocumentController@index` |
| POST | `/api/documents` | User | `DocumentController@store` |
| GET | `/api/documents/{document}` | User | `DocumentController@show` |
| PUT / PATCH | `/api/documents/{document}` | User | `DocumentController@update` |
| DELETE | `/api/documents/{document}` | User | `DocumentController@destroy` |
| GET | `/api/documents/{document}/download` | User | `DocumentController@file` |
| GET | `/api/documents/{document}/file` | User | `DocumentController@file` |
| GET | `/api/notifications` | User | `NotificationController@index` |
| POST | `/api/notifications/read-all` | User | `NotificationController@readAll` |
| PATCH | `/api/notifications/{notification}` | User | `NotificationController@update` |
| GET | `/api/products` | User | `ProductController@index` |
| POST | `/api/products` | User | `ProductController@store` |
| GET | `/api/products/{product}` | User | `ProductController@show` |
| PUT / PATCH | `/api/products/{product}` | User | `ProductController@update` |
| DELETE | `/api/products/{product}` | User | `ProductController@destroy` |
| GET | `/api/profile` | User | `ProfileController@show` |
| PATCH | `/api/profile` | User | `ProfileController@update` |
| DELETE | `/api/profile` | User | `ProfileController@destroy` |
| GET | `/api/profile/export` | User | `ProfileController@export` |
| PUT | `/api/profile/password` | User | `ProfileController@password` |
| PATCH | `/api/profile/preferences` | User | `ProfileController@preferences` |

Run instructions, administrator creation, upload limits, scheduler, mail, backups/recovery and deployment/update procedures are in [RUNNING.md](RUNNING.md).
