# Desire Thermometer

A PHP + MySQL web application where partners can share their current level of emotional and physical desire using an interactive thermometer.

---

## Requirements

- PHP 7.4 or newer (with PDO and the `pdo_mysql` extension)
- MySQL 5.7+ or MariaDB 10.3+
- A web server (Apache, Nginx, or PHP's built-in dev server)

---

## Setup

### 1. Create the database

Log in to MySQL and run the setup script:

```bash
mysql -u root -p < setup.sql
```

This creates a database called `desire_thermometer` with all required tables.

### 2. Configure the database connection

Edit `config.php` and fill in your MySQL credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'desire_thermometer');
define('DB_USER', 'your_mysql_user');
define('DB_PASS', 'your_mysql_password');
```

### 3. Serve the `web/` directory

**Option A — PHP built-in server (development only):**

```bash
cd web
php -S localhost:8080
```

Then open http://localhost:8080 in your browser.

**Option B — Apache / Nginx:**

Point the document root at the `web/` directory, or place the `web/` folder inside `htdocs` / `public_html`.

---

## Usage

1. **Create an account** — choose a first name, set a password, and customise your 1–10 desire level names (or keep the defaults).
2. **Note your Account ID** shown after registration — share it with your partner.
3. **Sign in** using your Account ID and password.
4. **Set your desire level** by clicking or dragging the thermometer on the dashboard.
5. **Partner up** — go to *Partner Settings*, enter your partner's Account ID, and send a request. They accept from their dashboard or Partner Settings page.
6. Once partnered, tap **"See [Partner]'s Level"** on your dashboard to see where they are right now.

---

## File Overview

| File | Purpose |
|------|---------|
| `setup.sql` | Database schema — run once to create tables |
| `config.php` | Database credentials |
| `db.php` | PDO connection helper |
| `index.php` | Login page |
| `register.php` | Account creation |
| `dashboard.php` | Main thermometer page |
| `partner.php` | Partner request management |
| `change_password.php` | Password change |
| `logout.php` | Sign-out |
| `api_set_level.php` | AJAX: save current desire level |
| `api_get_partner.php` | AJAX: fetch partner's current level |
| `style.css` | Shared styles |
| `thermometer.js` | Thermometer drag interaction + AJAX |
