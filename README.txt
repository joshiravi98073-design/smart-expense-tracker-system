=============================================================
  EXPENSE TRACKER PRO — README
  Version 1.0.0 | Built with PHP, MySQL, HTML, CSS, JS
=============================================================

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  QUICK START (STEP BY STEP)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

STEP 1 — Requirements
  • XAMPP / WAMP / LAMP (PHP 8.0+, MySQL 5.7+)
  • Web browser (Chrome / Firefox recommended)

STEP 2 — Setup Database
  1. Start XAMPP → Start Apache + MySQL
  2. Open: http://localhost/phpmyadmin
  3. Click "SQL" tab
  4. Paste the entire contents of database.sql
  5. Click "Go"
  ✅ This creates: database, 5 tables, 12 categories, 2 demo users, sample transactions

STEP 3 — Configure Database Connection
  Open: includes/config.php
  Change these lines:
    define('DB_HOST', 'localhost');     ← usually localhost
    define('DB_USER', 'root');          ← your MySQL username
    define('DB_PASS', '');              ← your MySQL password (blank in XAMPP)

STEP 4 — Place Files
  Copy entire "expense-tracker" folder to:
  • XAMPP: C:/xampp/htdocs/expense-tracker/
  • WAMP:  C:/wamp64/www/expense-tracker/
  • Linux: /var/www/html/expense-tracker/

STEP 5 — Set Permissions (Linux/Mac only)
  chmod -R 755 expense-tracker/
  chmod -R 777 expense-tracker/assets/uploads/

STEP 6 — Open in Browser
  http://localhost/expense-tracker/

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  LOGIN CREDENTIALS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  ADMIN
  Email:    admin@demo.com
  Password: Admin@123
  Access:   Full system control, all users, all data

  USER (Demo)
  Email:    user@demo.com
  Password: User@123
  Access:   Own transactions only

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  FILE STRUCTURE & PURPOSE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

expense-tracker/
│
├── index.php               — Login & Registration page (public entry point)
├── logout.php              — Destroys session, redirects to login
├── database.sql            — Full DB schema + seed data (run this in phpMyAdmin)
├── README.txt              — This file
│
├── includes/
│   ├── config.php          — DB connection, session init, helper functions
│   ├── layout.php          — Shared sidebar, header, footer HTML functions
│   ├── tx_modal.php        — Add/Edit Transaction modal (reused across pages)
│   └── budget_modal.php    — Set Budget modal (reused across pages)
│
├── user/
│   ├── dashboard.php       — User dashboard: stats, charts, recent transactions, insights
│   ├── transactions.php    — Full transactions list with filters, search, pagination
│   ├── budget.php          — Set/view monthly budgets with history
│   ├── reports.php         — Monthly/yearly breakdown, category analysis
│   └── profile.php         — Edit name, avatar, currency, theme, password
│
├── admin/
│   ├── dashboard.php       — Admin overview: all users stats, recent activity
│   ├── users.php           — View/delete users, toggle admin/user roles
│   ├── transactions.php    — View/edit/delete ALL user transactions
│   ├── categories.php      — Create/edit/delete global categories
│   ├── reports.php         — Per-user financial summaries
│   └── profile.php         — Redirects to user/profile.php
│
├── api/
│   ├── dashboard.php       — AJAX: stats, summary, monthly/yearly totals
│   ├── transactions.php    — AJAX: list, get, create, update, delete transactions
│   ├── categories.php      — AJAX: list, create, update, delete categories
│   ├── budget.php          — AJAX: set and get monthly budgets
│   ├── notifications.php   — AJAX: list, mark read, delete notifications
│   ├── admin.php           — AJAX: admin-only user management + role changes
│   └── export.php          — CSV download + PDF print report generation
│
└── assets/
    ├── css/
    │   └── style.css       — Full CSS: light/dark themes, glassmorphism, responsive
    ├── js/
    │   └── app.js          — All JS: AJAX, charts, modals, toasts, filters, export
    ├── img/
    │   └── avatar.svg      — Default avatar fallback image
    └── uploads/            — User avatar + receipt image uploads (writable)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  DATABASE TABLES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  users            — id, name, email, password(hashed), role, avatar,
                     currency, theme, monthly_budget, created_at

  categories       — id, name, icon(emoji), color, type(income/expense/both),
                     user_id(NULL=global), created_at

  transactions     — id, user_id, type(income/expense), amount, category_id,
                     description, date, currency, receipt(filename), created_at

  budgets          — id, user_id, month, year, amount  (UNIQUE per user+month+year)

  notifications    — id, user_id, message, type, is_read, created_at

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  FEATURES INCLUDED (ALL 15 SECTIONS)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  ✅ 1.  Authentication — Register, Login, Sessions, Protected pages, bcrypt passwords
  ✅ 2.  Admin Features — Dashboard, all users, all transactions, edit/delete, categories
  ✅ 3.  User Features  — Personal dashboard, add/edit/delete own transactions
  ✅ 4.  Expense & Income Management — Amount, Category, Date, Type, Currency
  ✅ 5.  Dashboard & Analytics — Balance, Income, Expense, Charts (Pie + Bar)
  ✅ 6.  Filters & Search — Date range, category, type, live search (debounced)
  ✅ 7.  Budget System — Set monthly budget, track remaining, progress bar
  ✅ 8.  Alerts & Notifications — Budget exceeded (75%/100%), no-entry reminder
  ✅ 9.  Export — CSV download, PDF print report
  ✅ 10. WhatsApp Share — One-click share with auto-generated summary message
  ✅ 11. UI/UX — Dark/Light mode, glassmorphism, responsive (mobile+desktop)
  ✅ 12. Performance — AJAX/Fetch (no page reload), smooth animations
  ✅ 13. Database — 5 properly structured tables with foreign keys
  ✅ 14. Bonus — Receipt image upload, multi-currency (INR/USD/EUR/GBP), avatar upload
  ✅ 15. Monthly & Yearly Totals — This month + this year income/expense/net

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  FUTURE SCOPE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  🔮 Email notifications (PHPMailer integration)
  🔮 Two-factor authentication (2FA via OTP)
  🔮 Recurring/scheduled transactions
  🔮 Mobile app (React Native / Flutter)
  🔮 Bank statement import (CSV parsing)
  🔮 Goal/savings tracker
  🔮 AI-powered spending predictions (ML model)
  🔮 Google OAuth login
  🔮 REST API for third-party integrations
  🔮 Multi-language (i18n) support
  🔮 Progressive Web App (PWA) with offline support
  🔮 Advanced OCR receipt scanning
  🔮 Crypto wallet integration
  🔮 Tax report generation (ITR-ready)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  SECURITY FEATURES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  • Passwords hashed with bcrypt (cost=12) via password_hash()
  • PDO prepared statements — prevents SQL injection
  • Session-based authentication — no direct URL access
  • Role-based access control (Admin vs User)
  • Input sanitization on all user inputs
  • File upload validation (type + size limits)
  • CSRF protection via session ownership checks

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  SUPPORT
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  Built with ❤️ using:
  • Backend:  PHP 8.0+, PDO MySQL
  • Frontend: HTML5, CSS3, Vanilla JavaScript
  • Charts:   Chart.js 4.4
  • Fonts:    Google Fonts (Syne + DM Sans)
  • Icons:    Emoji-based (no external icon library needed)

=============================================================
user gmail joshiravi98073@gmail.com
 passworld 25142005