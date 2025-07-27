# WBT - Web-based Task Tracker

WBT is a lightweight PHP application backed by MySQL for posting small jobs and tracking their completion. Workers claim tasks with a passcode, upload a result and the administrator reviews the submission and pays out from a shared fund.

## Features

- Task listing with category filtering (tasks may have multiple comma-separated categories)
- Claiming tasks using a simple passcode
- Countdown timer and automatic reset of expired jobs
- File upload when submitting work
- Administrator panel for posting, editing and deleting tasks
- Review queue for approving or rejecting submissions
- Fund bank with deposit and payout history
- Worker statistics and basic history pages

## Requirements

- PHP 8 or later
- MySQL (or MariaDB)
- A web server capable of running PHP scripts

## Installation

1. Import `db/schema.sql` into a new database:
   ```bash
   mysql -u root -p < db/schema.sql
   ```
2. Edit `db/db.php` with your database credentials.
3. Create an `uploads/` directory in the project root and make sure the web server can write to it.
4. Serve the project root directory. For local testing you can run:
   ```bash
   php -S localhost:8000
   ```
5. Visit `http://localhost:8000` to view the worker interface. The administrator interface is at `http://localhost:8000/admin.php`.

### Optional

- Schedule `api/cron_check_expired.php` via cron to periodically reset long running tasks.

## Project Structure

```
assets/      - front-end assets (fonts, icons, scripts)
api/         - PHP endpoints powering the interface
db/          - database connection script and schema
uploads/     - created at runtime for attachments and submissions
```

### Important Files

- `index.php` – main task listing UI
- `admin.php` – administrator panel
- `history.php` – view a worker's task history
- `fund_history.php` – list of deposit and payout transactions
- `api/tasks.php` – list tasks and claim new ones
- `api/submit.php` – submit work for review
- `api/approve.php` and `api/reject.php` – admin actions on submissions
- `api/fund.php` and `api/deposit.php` – manage the fund bank

## Basic Workflow

1. The administrator posts tasks from the admin panel and adds funds to the bank.
2. Workers authorize with a passcode and claim available tasks.
3. When finished they upload a file and optional comment which places the job in the review queue.
4. The administrator approves or rejects submissions. Approved jobs deduct their reward from the fund and optionally apply bonus rules.
5. Workers can view their own history and statistics.

This project is intended as a simple prototype. Review the code and security considerations carefully before deploying in a production environment.
