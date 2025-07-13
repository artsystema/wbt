# WBT

Web-based Task Tracker (WBT) is a small PHP application for posting and tracking simple tasks. Workers claim tasks with a passcode and submit their results for review.

## Requirements

- PHP 8 or later
- MySQL (or MariaDB)

## Installation

1. Import `db/schema.sql` into a new database.
   ```bash
   mysql -u root -p < db/schema.sql
   ```
2. Edit `db/db.php` with your database credentials.
3. Create an `uploads/` directory in the project root and make sure the web server can write to it.
4. Serve the `public/` directory. For local testing you can run:
   ```bash
   php -S localhost:8000 -t public
   ```
5. Visit `http://localhost:8000` in your browser. The admin panel is available at `http://localhost:8000/admin.php`.

## Project Structure

```
public/  - front-end pages and assets
api/     - PHP endpoints used by the interface
db/      - database connection and schema
uploads/ - created at runtime for attachments and submissions
```

## Basic Workflow

1. The admin posts tasks from the admin panel.
2. Workers authorize with a passcode and claim available tasks.
3. When done, they upload a file and optional comment.
4. The admin approves or rejects submissions. Approved tasks deduct their reward from the fund bank.

This repository provides a simple starting point and is not yet production ready. Use it as a prototype or adapt it to your needs.
