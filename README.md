# Hospital Management System

A complete hospital administration system built with **Laravel 12** and **Bootstrap 5**. It covers the daily workflow of a small hospital: staff accounts, departments, doctors, patients, appointments, prescriptions, billing and printable reports.

The interface is a server-rendered Blade application. Forms, tables, modals, filters and pagination are updated with the Fetch API and small pieces of vanilla JavaScript — **no React, Vue, Livewire, Inertia or Tailwind** are used.

---

## Features

### Authentication and roles

Three roles with server-side enforcement (middleware + controller checks, not just hidden buttons):

| Module | Admin | Receptionist | Doctor |
| --- | --- | --- | --- |
| Dashboard | Full hospital stats | Full hospital stats | Own patients, appointments and prescriptions |
| Departments / Doctors | Manage | View | View |
| Patients | Manage | Manage | Only patients they have seen |
| Appointments | Manage | Manage | Only their own appointments |
| Prescriptions | View all / delete | View only | Create and edit only their own |
| Billing | Manage / delete | Manage (no delete) | No access |
| Reports | All 7 reports | 6 reports (no prescriptions) | Appointments and prescriptions, own data only |

- Session login with active/inactive account handling.
- CSRF protection on every write request, password hashing, validation on every form.
- Role middleware alias `role` registered in `bootstrap/app.php`.

### Modules

- **Dashboard** — live database-backed cards and lists (patients, doctors, departments, today's appointments, revenue, outstanding balance, recent activity) that adapt to the logged-in role.
- **Departments** — create, edit and delete with an AJAX table, live search, status filter and pagination. A department with doctors cannot be deleted.
- **Doctors** — department-based management, availability toggle, consultation fee, optional link to a login account, AJAX search and filters.
- **Patients** — registration with auto-generated patient code (`PAT-00001`), unique phone/email validation, profile page with appointment, prescription and billing history, AJAX table.
- **Appointments** — booking with cascading department → doctor selects, 30-minute slots from 09:00 to 16:30, and transactional double-booking prevention (`lockForUpdate` + conflict check inside a database transaction).
- **Prescriptions** — diagnosis, instructions and a repeatable medicines table (medicine, dosage, frequency, duration, notes). Doctors can only prescribe for themselves and only for their own appointments. Printable prescription sheet.
- **Billing and payments** — invoices with multiple line items, discount, server-side totals, partial and full payments, payment status (`Unpaid`, `Partially Paid`, `Paid`), outstanding balance tracking, printable invoice. Totals and balances are always calculated on the server; the browser only previews them.
- **Reports** — seven reports with date ranges and print support:
  - Patient Register
  - Doctors & Departments
  - Appointments
  - Prescriptions
  - Billing Summary
  - Payments Received
  - Outstanding Balances

### AJAX behaviour

Every create, update, delete, search, filter and pagination action on the main modules updates the page in place:

- Controllers return `{ html, total }` (plus `{ summary }` for billing and reports) when the request expects JSON, rendered from the same Blade partials used for the first page load.
- Shared JavaScript helpers in `public/js/app.js` (`App.get`, `App.post`, `App.put`, `App.delete`, toasts, confirmation dialogs, form error rendering, debounce, money formatting).
- Module scripts: `public/js/departments.js`, `doctors.js`, `patients.js`, `appointments.js`, `prescriptions.js`, `bills.js`, `reports.js`.
- No `location.reload()` and no full-page navigation for normal CRUD operations.

Printing has a dedicated print stylesheet so only the document header, details and tables are printed.

---

## Tech stack

- PHP 8.2+ / Laravel 12
- MySQL 8 (database `hospital_db`)
- Bootstrap 5 + Bootstrap Icons (CDN)
- Vanilla JavaScript with the Fetch API
- PHPUnit feature tests

No Node build step is required — CSS and JavaScript live in `public/css` and `public/js`.

---

## Requirements

- PHP 8.2 or newer with `pdo_mysql`
- Composer 2
- MySQL 8 (or MariaDB 10.4+)

---

## Installation

```bash
# 1. Install PHP dependencies
composer install

# 2. Create the environment file
cp .env.example .env
php artisan key:generate
```

Edit `.env` if your MySQL credentials differ:

```env
APP_NAME="Hospital Management System"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hospital_db
DB_USERNAME=root
DB_PASSWORD=
```

```bash
# 3. Create the database (once)
mysql -u root -e "CREATE DATABASE IF NOT EXISTS hospital_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Create tables and load demo data
php artisan migrate --seed

# 5. Run the application
php artisan serve
```

Open <http://localhost:8000> and sign in with one of the demo accounts below.

### Demo credentials

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@hospital.com` | `password123` |
| Doctor | `doctor@hospital.com` | `password123` |
| Receptionist | `reception@hospital.com` | `password123` |

The seeder also creates sample departments, doctors, patients, appointments, prescriptions and bills so every screen and report has data to show.

---

## Tests

The test suite covers authentication, role access, validation, appointment conflicts, prescription ownership, bill mathematics and report scoping.

Tests use a separate MySQL database because the project targets MySQL:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS hospital_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan test
```

Current status: **48 tests, 148 assertions, all passing**.

`phpunit.xml` points at `hospital_testing`, so running the suite never touches development data.

---

## Project structure

```
app/
  Http/
    Controllers/        Auth, Dashboard, Department, Doctor, Patient,
                        Appointment, Prescription, Bill, Report
    Middleware/         RoleMiddleware (alias: role)
  Models/               User, Department, Doctor, Patient, Appointment,
                        Prescription, PrescriptionItem, Bill, BillItem
database/
  migrations/           users → departments → doctors → patients →
                        appointments → prescriptions → prescription_items →
                        bills → bill_items
  seeders/              demo staff and hospital records
resources/views/
  layouts/app.blade.php shared shell (sidebar, navbar, toasts)
  <module>/             index pages, partials/table.blade.php, modals,
                        show/print documents
public/
  css/app.css           theme, print styles
  js/app.js             shared Fetch helpers
  js/<module>.js        module behaviour
tests/Feature/          feature tests and CreatesHospitalRecords trait
routes/web.php          all application routes
```

### Database notes

- Foreign keys protect history: doctors/departments restrict deletion, appointment-linked records use `nullOnDelete`, and children (`prescription_items`, `bill_items`) cascade with their parent.
- Appointment slot conflicts are enforced in a transaction with a row lock, so two receptionists cannot book the same doctor and time simultaneously.
- Bill totals (`line_total`, `subtotal`, `total`, `balance`, `payment_status`) are computed on the server from items and discount. The client cannot submit a paid amount greater than the total.

---

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
