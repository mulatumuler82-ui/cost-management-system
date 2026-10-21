this my last project # 💰 Expense Tracker

A simple PHP + MySQL web app to track daily income and expenses.
Built to manage my university food allowance (e.g. 3000 ETB) plus extra money I add,
and to see exactly where it goes.

## Features
- Register / login with hashed passwords
- Add income and expenses with category, date and note
- Daily, weekly, monthly, yearly and all-time summaries
- Balance (income − expense) at a glance
- Expense breakdown by category
- Last 7 days spending chart
- Delete records
- Responsive design (works on phone)

## Setup
1. Install XAMPP / WAMP (PHP 7.4+ and MySQL).
2. Copy this folder into `htdocs` (XAMPP) or `www` (WAMP).
3. Open phpMyAdmin → Import → choose `database.sql`.
4. Edit `db.php` if your MySQL user/password differ (default: `root`, no password).
5. Visit `http://localhost/expense-tracker/` and register an account.

## Files
| File | Purpose |
|------|---------|
| `index.php` | Login page |
| `register.php` | Create account |
| `dashboard.php` | Summaries, charts, records, add form |
| `add_expense.php` | Saves an income/expense |
| `delete_expense.php` | Deletes a record |
| `logout.php` | Ends session |
| `db.php` | DB connection + helpers |
| `database.sql` | Database schema |
| `assets/style.css` | Styling |

