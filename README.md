# Personal-Expense-Budget-Tracker-PHP-MySQL-CRUD-app-

Track income and expenses, set monthly category budgets, and see reports. Built to practise 
HTML/CSS, Bootstrap 5, JavaScript, PHP, MySQL, authentication and basic web security.

## Run it locally (XAMPP)

1. Install XAMPP and start **Apache** and **MySQL** from the control panel.
2. Copy this `expense-tracker` folder into `C:\xampp\htdocs\` (Mac/Linux: `htdocs` of your XAMPP/LAMP).
3. Open **http://localhost/phpmyadmin** > **Import** > choose `sql/schema.sql` > **Go**.
   (This creates the `expense_tracker` database and its 4 tables.)
4. If your MySQL has a password, edit `config/db.php`. XAMPP's default is user `root`, empty password.
5. Open **http://localhost/expense-tracker/register.php**, create an account, and log in.

## Features

- Register / login / logout (hashed passwords, sessions, CSRF-protected forms)
- Add, edit, delete expenses (Create, Read, Update, Delete)
- Filter expenses by month, category and search text
- Record income; set monthly budgets per category with progress bars and over-budget alerts
- Dashboard (this month's income, expenses, balance, category breakdown, recent expenses)
- Reports with Chart.js (category doughnut + 6-month income vs expenses)
- Mobile-friendly: sidebar is `position: fixed` on desktop and a slide-in menu on phones

## Project structure

```
expense-tracker/
├── index.php              Dashboard (Read, SUM, GROUP BY, LIMIT)
├── register.php           Create account (password_hash)
├── login.php              Log in (password_verify, session_regenerate_id)
├── logout.php             Log out (destroy session)
├── add_expense.php        CREATE
├── manage_expenses.php    READ with filters
├── edit_expense.php       UPDATE
├── delete_expense.php     DELETE
├── incomes.php            Income CRUD
├── budgets.php            Budget upsert + delete + progress
├── view_reports.php       Chart.js reports
├── config/db.php          mysqli connection
├── includes/
│   ├── auth.php           session, CSRF, escaping, require_login, helpers
│   ├── header.php         navbar + sidebar layout
│   ├── footer.php         scripts
│   └── expense_form_fields.php   shared add/edit form
├── assets/css/style.css
├── assets/js/app.js       confirm(), setTimeout, DOM events
├── assets/js/reports.js   Chart.js setup
└── sql/schema.sql         users, expenses, incomes, budgets
```

## Where each topic from your checklist lives

| # | Topic | Where to look |
|---|-------|---------------|
| 1 | HTML & CSS (forms, tables, semantic tags, box model, flexbox, fixed positioning, responsive) | `includes/header.php` (`<nav>`, `<aside>`, `<main>`), `assets/css/style.css` (sidebar `position: fixed`, media queries), forms in `add_expense.php`, tables in `manage_expenses.php` |
| 2 | Bootstrap 5 (grid, cards, navbar, dropdowns, alerts, tables) | CDN links in `includes/header.php`; `row`/`col-md-*` in `index.php`; overrides in `style.css` |
| 3 | JavaScript (DOM, events, `confirm()`, `setTimeout`, Chart.js) | `assets/js/app.js`, `assets/js/reports.js` |
| 4 | PHP fundamentals (`$_GET`, `$_POST`, `$_SESSION`, `include`, `header()` + `exit`, `htmlspecialchars`) | `includes/auth.php` (`redirect()` calls `exit`, `e()` wraps `htmlspecialchars`), every page |
| 5 | MySQL / SQL (keys, types, INSERT/SELECT/UPDATE/DELETE, WHERE, ORDER BY, GROUP BY, SUM, LIMIT) | `sql/schema.sql`, `index.php`, `manage_expenses.php` |
| 6 | PHP + MySQL (mysqli, prepared statements, `bind_param`) | `config/db.php`, `add_expense.php` |
| 7 | Authentication & sessions | `register.php`, `login.php`, `logout.php`, `require_login()` |
| 8 | CRUD | `add_expense.php`, `manage_expenses.php`, `edit_expense.php`, `delete_expense.php` |
| 9 | Security (SQL injection, XSS, CSRF, never trust input) | prepared statements everywhere; `e()` on all output; `csrf_check()` on all POST handlers; server-side validation; `AND user_id = ?` on every query |
| 10 | Tools (XAMPP, phpMyAdmin, Git/GitHub, DevTools) | setup steps above; see Git section below |

## `bind_param` type letters

| Letter | Type | Example |
|--------|------|---------|
| `i` | integer | `user_id`, `id` |
| `d` | double / decimal | `amount` |
| `s` | string (also dates) | `title`, `expense_date` |
| `b` | blob (binary) | not used here |

`'issdss'` in `add_expense.php` means: int, string, string, double, string, string, matching the six `?` placeholders in order.

## Why prepared statements stop SQL injection

The SQL text is sent to MySQL first with `?` placeholders, and the values are sent separately afterwards.
MySQL treats values purely as data, never as SQL, so input such as `' OR 1=1 --` is stored or compared as
plain text instead of changing the query.

## Security checklist (what this app does)

- **SQL injection:** prepared statements for every query that touches user input.
- **XSS:** all output goes through `e()` (`htmlspecialchars`); session cookie is `HttpOnly`.
- **CSRF:** a random token in every form, verified with `hash_equals`; delete and logout are POST-only.
- **Passwords:** `password_hash` / `password_verify`, minimum 8 characters.
- **Session fixation:** `session_regenerate_id(true)` after login.
- **Authorization:** every read/update/delete is scoped with `AND user_id = ?` so users can only touch their own rows.
- **Open redirects:** `delete_expense.php` only redirects back to a whitelisted local URL pattern.

Still worth adding before real deployment: HTTPS (then set the session cookie `secure` flag), login
rate-limiting, and moving DB credentials out of the web root. This app has no file uploads; if you add
one (e.g. receipt images), check the MIME type and extension against an allow-list, rename the file,
and store it outside the web root.

## Git & GitHub

```bash
cd expense-tracker
git init
git add .
git commit -m "Initial commit: expense tracker"
# create an empty repo on github.com, then:
git remote add origin https://github.com/<your-username>/expense-tracker.git
git branch -M main
git push -u origin main
```

Never commit real passwords: if `config/db.php` holds a production password, add it to `.gitignore`
and commit a `db.example.php` instead.

## Debugging tips (browser DevTools)

- **Network tab:** watch the POST on form submit; check the status code (302 redirect, 419 bad CSRF token, 500 server error).
- **Console tab:** JavaScript errors (for example a Chart.js data problem) show up here.
- **Elements tab + device toolbar:** test the responsive layout at phone widths.
- **Application tab > Cookies:** see the `PHPSESSID` session cookie and its `HttpOnly` flag.
- PHP errors: check `C:\xampp\apache\logs\error.log`.

## Ideas to extend it

Pagination on the manage page, CSV export, recurring expenses, edit income, password reset, and a
PDO version of the data layer to compare with mysqli.
