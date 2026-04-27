# JobTracker

A full-stack web application for managing and tracking job applications throughout the recruitment cycle. Built for students navigating internship and full-time recruiting seasons; organize applications by cycle, attach resumes, track statuses, and connect with peers to share progress.

Built with PHP, MySQL, and JavaScript

## Features

- **Dashboard** — Real-time stats (total, offers, interviews, rejections), filterable by cycle/status, sortable columns, drag-to-reorder rows
- **Application Tracking** — Full CRUD with six status stages (Draft, Submitted, Interview, Offer, Rejected, Withdrawn), linked to companies, cities, cycles, and documents
- **Cycle Management** — Organize applications into named cycles (e.g., "Summer 2026 Internships") with per-cycle views
- **Document Management** — Drag-and-drop uploads, in-app PDF/image preview, linked applications shown per document
- **User Connections** — Connect with peers by email, view their profiles and applications by cycle
- **Profile** — School, degree, major, graduation dates, bio, and profile picture
- **Export** — Download application data as JSON, optionally filtered by cycle
- **Multi-User** — Session-based auth with per-user data isolation; supports concurrent usage

## Tech Stack

| Layer              | Technology                                                             |
| ------------------ | ---------------------------------------------------------------------- |
| **Frontend** | HTML5, CSS3, vanilla JavaScript (single-page application)              |
| **Backend**  | PHP 8.x (procedural, RESTful API endpoints)                            |
| **Database** | MySQL (hosted on UVA CS department server:`mysql01.cs.virginia.edu`) |
| **Server**   | Apache via XAMPP                                                       |
| **Design**   | Custom glassmorphism UI with CSS variables, responsive layout          |

## Setup

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) (PHP 8.x + Apache)
- Access to a MySQL database (UVA CS server or local)
- UVA VPN (Cisco AnyConnect) if connecting to the UVA database off-campus

### Installation

1. **Clone the repository** into XAMPP's `htdocs` directory:

   ```bash
   git clone https://github.com/jason-dong03/JobTracker.git /Applications/XAMPP/xamppfiles/htdocs/JobTracker
   ```
2. **Import the database schema:**

   - Open phpMyAdmin and select your database
   - Go to the **Import** tab and import `db/schema.sql`
   - You will need to modify the student ID to match yours
   - The schema includes seed data for common companies (Google, Meta, Amazon, etc.), cities, and universities
3. **Create a `.env` file** in the project root:

   ```
   DB_HOST=mysql01.cs.virginia.edu
   DB_USER=<your_db_username>
   DB_PASS="<your_db_password>"
   DB_NAME=<your_db_name>
   ```
4. **Start Apache and MySQL** in the XAMPP control panel
5. **Open** `http://localhost/JobTracker/` in a browser and register an account

## Project Structure

```
JobTracker/
├── api/                    REST API endpoints
│   ├── applications.php        CRUD + sort/filter for job applications
│   ├── cycles.php              CRUD for application cycles
│   ├── companies.php           CRUD for company master data
│   ├── cities.php              CRUD for city/location data
│   ├── documents.php           Upload, preview, download, delete documents
│   ├── profile.php             Get/update user profile
│   ├── connections.php         Manage user connections
│   ├── connection_data.php     View a connection's profile and applications
│   ├── schools.php             School master data
│   └── export.php              Export applications as JSON
├── config/
│   └── db.php              Database connection, session init, helpers
├── css/
│   └── style.css           Glassmorphism design system
├── db/
│   └── schema.sql          Full database schema + seed data
├── js/
│   └── app.js              SPA logic — routing, rendering, API calls
├── storage/
│   ├── documents/          Uploaded resumes and files
│   └── profiles/           Profile pictures
├── index.php               Main SPA shell (requires authentication)
├── login.php               Login / register page
├── auth.php                Authentication handler (login, register, logout)
└── .env                    Database credentials (git-ignored)
```

## Database Design

The database uses **11 normalized tables** connected through foreign key constraints:

**Core tables:** `users`, `profiles`, `application_cycles`, `applications`, `companies`, `cities`, `documents`, `schools`

**Junction tables:** `submits` (user ↔ application), `uploads` (user ↔ document), `application_documents` (application ↔ document)

**Additional tables:** `terms` (education history), `user_connections` (peer networking)

All tables enforce referential integrity through `CASCADE` and `RESTRICT` rules, and data validity through `CHECK` constraints (e.g., application status must be one of six allowed values).

## Security

### Database Level

- Foreign key constraints with `ON DELETE CASCADE` / `RESTRICT` to prevent orphaned records
- `CHECK` constraints to enforce data validity (e.g., valid status values, non-empty filenames)
- Database user privileges restricted to `SELECT`, `INSERT`, `UPDATE`, `DELETE` only — no `DROP`, `ALTER`, `CREATE`, or `GRANT` access

### Application Level

- **Authentication:** Session-based login with automatic redirect for unauthenticated users; every API endpoint checks `$_SESSION['user_id']` and returns 401 if missing
- **Password Security:** Passwords hashed with `bcrypt` via `password_hash()` and verified with `password_verify()` — raw passwords are never stored
- **SQL Injection Prevention:** 100% of database queries use prepared statements with bound parameters
- **Authorization:** All queries are scoped to the authenticated user via `user_id` in `WHERE` clauses and `JOIN` conditions — users can never access another user's data
- **XSS Prevention:** All user-generated content is escaped with a custom `esc()` function before rendering in the DOM
- **File Upload Security:** Filenames are sanitized with `preg_replace()`, stored with unique disk names (`{user_id}_{timestamp}_{filename}`), and ownership is verified before serving
- **Credential Management:** Database credentials stored in `.env` file which is listed in `.gitignore`

## Team

| Name         | Computing ID |
| ------------ | ------------ |
| Kaden Nguyen | ync5ad       |
| Leo Lee      | pfw5ty       |
| Jason Dong   | ppf3jn       |
| Nathan Suh   | xuk7sp       |
