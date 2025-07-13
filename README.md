# WBT - Web Based Task Tracker

WBT is a lightweight PHP and MySQL based task tracking system. It lets you post
jobs, let users claim them with a passcode, upload results and have an admin
approve or reject submissions. The project is intended for small teams or
personal use and does not require any external dependencies beyond PHP and a
MySQL server.

## Features

- Public task list where users can take jobs and upload submissions
- Admin panel to create, edit and delete tasks with optional attachments
- Review workflow for approving or rejecting completed work
- Countdown timer showing time remaining for in‑progress tasks
- Basic user statistics such as active/completed jobs and last submission time
- Fund tracking to monitor available and reserved payout amounts
- Worker ranking system with an adjustable payout coefficient
- Payment history stored in a dedicated `payouts` table

## Directory Layout

```
public/     – front‑end pages and assets
api/        – PHP endpoints used by the front‑end
db/         – database connection and SQL schema
uploads/    – created at runtime to store attachments and submissions
```

`public/index.php` is the main task list while `public/admin.php` provides the
admin interface. JavaScript and CSS live under `public/assets/`. The `api/`
directory contains the PHP endpoints used by the UI. Database setup and
connection logic are found in `db/`.

## Setup

1. Create the MySQL database and tables using `db/schema.sql`:
   ```bash
   mysql -u root -p < db/schema.sql
   ```
2. Update the credentials in `db/db.php` to match your MySQL setup.
3. Create an `uploads/` directory in the project root and ensure it is writable
   by the web server.
4. Serve the `public/` directory through your web server or the built‑in PHP
   server for local testing:
   ```bash
   php -S localhost:8000 -t public
   ```
5. Navigate to `http://localhost:8000/` to view the task list. The admin panel is
   available at `http://localhost:8000/admin.php`.
6. The `tasks` table now includes a `category` column. If upgrading an existing
   installation run:
   ```sql
   ALTER TABLE tasks ADD COLUMN category VARCHAR(255);
   ```
7. Versions after 1.1 use a `payouts` table to record completed payments. If
   upgrading run:
   ```sql
   CREATE TABLE payouts (
     id INT AUTO_INCREMENT PRIMARY KEY,
     passcode VARCHAR(255),
     amount DECIMAL(10,2),
     paid_at DATETIME DEFAULT CURRENT_TIMESTAMP
   );
   ```

## Expired Task Reset

Tasks that remain in progress longer than their estimated time will automatically
reset when the front‑end polls `/api/reset_expired.php`. An additional script,
`api/cron_check_expired.php`, can be run periodically (for example via cron) to
reset expired tasks even if no users are active.

## Bonus and Fund Management

Approved tasks deduct their reward (plus any bonus specified in the
`bonus_rules` table) from the `fund_bank` total. Available and reserved fund
amounts are shown in the page header via the `api/fund.php` endpoint.

## Payouts Table

Completed payouts are tracked in the `payouts` table. Insert a row whenever you
disburse money to a worker:

```sql
INSERT INTO payouts (passcode, amount) VALUES ('worker1', 10.00);
```

`/api/user_stats.php` sums these rows to display how much each user has been
paid so far.

---

This project started as a simple experiment and may require adjustments for
production use, but it provides a functional starting point for a small
web‑based task tracking workflow.
