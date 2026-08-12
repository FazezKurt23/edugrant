# EduGrant

**A Web-Based Scholarship Information and Application Management System for Students**

**Find Opportunities. Build Your Future.**

EduGrant is a complete, fully functional PHP/MySQL web application built as a BSIT
capstone project. It lets students discover scholarship opportunities, understand
eligibility requirements, save scholarships, track their application progress, and
monitor important deadlines. Providers can publish and manage scholarship listings
(through an administrator approval workflow), and administrators manage the whole
platform.

> **Important:** EduGrant is an **information and application tracking system**. It
> does **not** submit applications to scholarship providers. Students are redirected
> to each provider's official application website.

---

## 1. Tech Stack

- PHP 8+ (PDO)
- MySQL / MariaDB (via phpMyAdmin)
- Apache (XAMPP)
- HTML5, CSS3, JavaScript
- Bootstrap 5
- Chart.js
- No frameworks required (no Laravel, no Composer, no Node)

---

## 2. Project Structure

```
EduGrant/
├── index.php                     → Landing page
├── login.php                     → Log in
├── register.php                  → Register (student/provider)
├── logout.php                    → Log out
├── forgot-password.php           → Password reset request
├── reset-password.php            → Password reset (token)
│
├── student/                      → Student portal
│   ├── dashboard.php
│   ├── scholarships.php          → Directory (search/filter/sort/pagination)
│   ├── scholarship-details.php   → Details + eligibility checker + save + track
│   ├── saved-scholarships.php
│   ├── applications.php
│   ├── application-details.php
│   ├── notifications.php
│   └── profile.php
│
├── provider/                     → Provider portal
│   ├── dashboard.php
│   ├── scholarships.php
│   ├── add-scholarship.php
│   ├── edit-scholarship.php
│   ├── view-scholarship.php
│   ├── applications.php
│   ├── notifications.php
│   └── profile.php
│
├── admin/                        → Admin portal
│   ├── dashboard.php             → Statistics + Chart.js
│   ├── users.php
│   ├── students.php
│   ├── providers.php             → Provider verification
│   ├── scholarships.php          → Manage all scholarships
│   ├── pending-scholarships.php  → Approval queue
│   ├── view-scholarship.php
│   ├── applications.php
│   ├── notifications.php
│   ├── reports.php               → Activity logs + CSV export
│   └── profile.php
│
├── config/
│   ├── config.php                → Constants, session, base URL
│   └── database.php              → PDO connection
│
├── includes/
│   ├── functions.php             → Reusable helpers
│   ├── auth.php                  → Login/auth/role helpers
│   ├── deadline-notifications.php→ Deadline reminder generator
│   ├── header.php                → HTML head + navbar + flashes
│   ├── sidebar.php               → Role-based sidebar
│   └── footer.php                → Footer + scripts
│
├── assets/
│   ├── css/style.css
│   ├── js/app.js
│   └── images/favicon.svg
│
└── database/
    └── edugrant.sql              → Importable schema + demo data
```

---

## 3. Database Setup (XAMPP + phpMyAdmin)

### Step 1 — Start XAMPP

Open the **XAMPP Control Panel** and start:

- **Apache**
- **MySQL**

### Step 2 — Place the project

Make sure the project folder is at:

```
C:\xampp\htdocs\EduGrant
```

(If your folder name has a different case, update `BASE_URL` in `config/config.php`.)

### Step 3 — Import the database

1. Open <http://localhost/phpmyadmin>
2. Click the **Import** tab
3. Choose the file `database/edugrant.sql` from the project folder
4. Click **Go** / **Import**

This creates the `edugrant` database with all tables, foreign keys, indexes,
constraints, and demo data.

### Step 4 — Verify the database

- You should see a database named `edugrant`
- It contains the tables: `users`, `student_profiles`, `provider_profiles`,
  `scholarships`, `scholarship_requirements`, `required_documents`,
  `saved_scholarships`, `applications`, `notifications`, `activity_logs`,
  `password_resets`

### Step 5 — Check the database configuration

Open `config/database.php`. The default XAMPP settings are:

```php
$host     = '127.0.0.1';
$port     = '3306';
$dbname   = 'edugrant';
$user     = 'root';
$password = '';   // default XAMPP root has no password
```

If your MySQL root account uses a password, update it here.

### Step 6 — Open the application

Visit:

```
http://localhost/EduGrant/
```

---

## 4. Demo Accounts

| Role     | Email                   | Password       |
|----------|-------------------------|----------------|
| Admin    | `admin@edugrant.local`  | `Admin123!`    |
| Student  | `student@edugrant.local`| `Student123!`  |
| Student  | `student2@edugrant.local`| `Student123!` |
| Student  | `student3@edugrant.local`| `Student123!` |
| Provider | `provider@edugrant.local`| `Provider123!`|
| Provider | `provider2@edugrant.local`| `Provider123!`|

All passwords are stored as `password_hash()` bcrypt hashes — never in plain text.

Demo data includes 11 scholarships (approved, pending, rejected, draft, expired),
requirements, required documents, saved scholarships, application tracking records,
notifications, and activity logs. All organizations and URLs are clearly **demo** data.

---

## 5. Role-Based Workflows

### Student
1. Register → complete profile (GPA, family income, course, etc.)
2. Browse the scholarship directory (search, filter, sort, paginated)
3. View scholarship details, requirements, required documents, eligibility checker
4. Save / unsave scholarships
5. Start application tracking (Interested → Preparing → Applied → Under Review → Approved/Rejected)
6. Add notes to each tracked application
7. Receive and manage deadline + system notifications
8. Open the provider's official application website (new tab)

### Provider
1. Register → organization profile (verification status: pending/verified/rejected)
2. Create scholarships (draft)
3. Add requirements and required documents
4. Submit for approval (pending)
5. See approval/rejection status + rejection reason
6. Edit a rejected/approved scholarship and resubmit (returns to pending)
7. Monitor students' tracked applications
8. Only see and modify their own scholarships

### Admin
1. Dashboard with live statistics and Chart.js charts
2. Manage users (activate / deactivate / suspend / delete; the last active admin is protected)
3. Manage students (view profiles, change status)
4. Verify or reject providers
5. Approve / reject scholarships (rejection requires a reason)
6. Edit / delete any scholarship
7. Monitor all applications
8. Reports page + CSV exports (scholarships, users, applications) + activity logs

---

## 6. Application Status Workflow

```
Interested → Preparing Documents → Applied → Under Review → Approved / Rejected
```

- Students advance their own tracking status step by step.
- Impossible transitions (e.g., Preparing → Under Review) are rejected server-side.
- "Applied / Under Review / Approved / Rejected" reflect the student's own tracking
  notes, **not** the provider's official decision.

## 7. Scholarship Status Workflow

```
Provider creates → draft
Provider submits → pending
Admin approves  → approved  (visible to students)
Admin rejects   → rejected  (reason saved in rejection_reason)
Deadline passed → expired   (controlled maintenance on admin dashboard load)
```

## 8. Deadline Monitoring

Remaining days are always calculated from the database deadline:

| Remaining days | Label |
|----------------|-------|
| 30+            | Upcoming |
| 8–30           | Approaching |
| 1–7            | Urgent |
| 0 or less      | Expired |

Deadline reminder notifications are generated idempotently (deduplicated by
user + title) and triggered from the student dashboard.

---

## 9. Security Features

- **PDO prepared statements** everywhere (no raw SQL concatenation with user input)
- **`password_hash()` / `password_verify()`** for passwords
- **Secure sessions** with `session_regenerate_id()` after login, HTTP-only cookies, SameSite=Lax
- **Role-based authorization** — direct URL access to unauthorized areas returns 403
- **CSRF protection** on every state-changing form
- **Server-side validation** for email, URL, numeric (GPA, income, IDs), and dates
- **`htmlspecialchars()`** output escaping throughout
- **Ownership checks** on the server — the logged-in student/provider identity is
  always derived from the session, never from hidden form fields
- **User-friendly error messages** — raw database errors are logged, not displayed

---

## 10. Implemented Feature List

- Landing page (hero, why, how it works, featured scholarships, upcoming deadlines, stats, CTA, footer)
- Public registration for students and providers (admin registration not public)
- Login / logout / forgot-password / reset-password (development-mode reset link)
- Student dashboard with real statistics
- Scholarship directory with real search, filters, sorting, and pagination
- Scholarship details with requirements, documents, official URL, and eligibility checker
- Save / unsave scholarships (unique constraint prevents duplicates)
- Application tracking with valid status transitions and notes
- Deadline monitoring with automatic countdowns and expired handling
- Deadline + system notifications (mark as read, mark all read, delete, unread count in navbar)
- Provider dashboard, scholarship CRUD, draft, submit, rejection reason, resubmission
- Admin dashboard with Chart.js charts (type, education level, application status, monthly submissions)
- User / student / provider management
- Provider verification workflow
- Scholarship approval / rejection workflow
- Application monitoring
- Reports + activity logs + CSV exports
- Full activity logging (login, logout, registration, scholarship actions, applications, profile updates)

---

## 11. Testing Checklist

Automated end-to-end tests (PHP cURL script) confirmed all of the following:

- [x] Landing page loads
- [x] Student registration (and duplicate-email rejection)
- [x] Login / logout for all roles
- [x] Role restriction (student blocked from `/admin/*` with 403)
- [x] Scholarship directory lists only approved, non-expired scholarships
- [x] Search, filter by type, sort
- [x] Details page: requirements, documents, eligibility checker, disclaimer
- [x] Save / duplicate-save / unsave
- [x] Track application / applications page
- [x] Status update + invalid transition blocked
- [x] CSRF rejection for missing tokens
- [x] Mark all notifications read
- [x] Profile update
- [x] Provider creates draft → submits → pending
- [x] Pending hidden from students
- [x] Admin approves → visible to students
- [x] Admin rejects with reason → provider sees reason
- [x] Resubmit rejected → pending
- [x] Admin verifies provider
- [x] Reports + activity logs + CSV export
- [x] Password reset flow (forgot → reset → login with new password)

---

## 12. Known Limitations

- **No email server** — password-reset links are displayed on screen in development
  mode (`DEV_MODE` in `config/config.php`). Set `DEV_MODE = false` and integrate a
  mail library (e.g., PHPMailer) for production.
- **Eligibility checker is a guide only** — requirements that cannot be evaluated
  from structured data are marked "Requires Manual Review". Final eligibility is
  always decided by the provider.
- **Application tracking is student-managed** — EduGrant does not integrate with
  provider application systems and cannot submit applications.
- **Demo deadlines** are fixed dates in the seed data; use phpMyAdmin or the provider
  UI to set realistic deadlines when presenting.
- **Chart.js and Bootstrap are loaded from CDNs** — an internet connection is needed
  for full styling/charts (or download the assets locally).
- **No file upload** for requirement documents (out of scope for this capstone version).

---

## 13. Troubleshooting Guide

### Apache not starting
- Port 80 is probably in use (Skype, IIS). In XAMPP Control Panel: Config → Apache → `httpd.conf`, change `Listen 80` to another port (e.g., `8080`), then open `http://localhost:8080/EduGrant/`.

### MySQL not starting
- Close the XAMPP Control Panel and start again as **Administrator**.
- If the error says the data folder is locked, stop other MySQL services, or check `C:\xampp\mysql\data` for leftover `.pid` files.
- Check Windows services: stop the built-in MySQL service, then start XAMPP MySQL.

### Database connection failed / Unknown database `edugrant`
- MySQL is not running, or the SQL file was not imported.
- Re-import `database/edugrant.sql` in phpMyAdmin.
- Confirm the database name in `config/database.php` matches `edugrant`.

### Access denied for user 'root'
- Your XAMPP MySQL root has a password. Add it to `config/database.php`:
  ```php
  $password = 'your_root_password';
  ```

### 404 Not Found
- Wrong folder name. The project must be at `C:\xampp\htdocs\EduGrant` and accessed via `http://localhost/EduGrant/`.
- If you renamed the folder, update `BASE_URL` in `config/config.php`.

### PHP errors
- Enable error display during development by checking `C:\xampp\php\php.ini`:
  - `display_errors = On`
- Application errors are logged with `error_log()`; check `C:\xampp\apache\logs\error.log`.

### Missing include files
- Make sure all files and folders exist as in the structure above.
- Includes use absolute paths based on `BASE_PATH`, so the project must be the full folder at `C:\xampp\htdocs\EduGrant`.

### Incorrect relative paths / assets not loading
- Asset links use `BASE_URL` (`http://localhost/EduGrant/...`). Update `config/config.php` if the URL differs.

### Permission problems (Windows)
- Right-click the project folder → Properties → Security → make sure your user has **Modify** permission. Run editors/terminals as Administrator when needed.

### Invalid login
- Confirm the account status is **active** (admin can change it).
- Use the exact demo credentials (passwords are case-sensitive).
- If you changed a password, use the new one.

### Session problems
- Delete browser cookies for `localhost` and log in again.
- Do not open `http://localhost/EduGrant` and `http://127.0.0.1/EduGrant` interchangeably (different session cookies). Pick one host consistently.

### Charts / styling missing
- Check internet access (Bootstrap/Chart.js are served from CDNs).

---

## 14. Developer Notes

- To reset all demo data at any time, re-import `database/edugrant.sql`.
- `expirePastDeadlineScholarships()` runs automatically on the admin dashboard load,
  so expired approved scholarships become `expired` in a controlled way.
- Deadline notifications are generated idempotently on the student dashboard load;
  duplicates are prevented by a user+title check.

---

## 15. Final Output / Deliverables

1. Complete project structure — see Section 2
2. Complete source code — all files in this repository
3. `database/edugrant.sql` — importable database
4. Database setup instructions — Section 3
5. XAMPP setup instructions — Section 3
6. phpMyAdmin import instructions — Section 3
7. Database configuration instructions — `config/database.php`
8. Demo admin credentials — `admin@edugrant.local` / `Admin123!`
9. Demo student credentials — `student@edugrant.local` / `Student123!`
10. Demo provider credentials — `provider@edugrant.local` / `Provider123!`
11. How to run the project — Section 3, Step 6
12. Implemented feature list — Section 10
13. Testing checklist — Section 11
14. Known limitations — Section 12
15. Troubleshooting guide — Section 13
