# PlanTrack — Installation & Deployment Guide for Ubuntu & NGINX

This guide provides step-by-step instructions for deploying and configuring **PlanTrack** in a production **Ubuntu (22.04 LTS or 24.04 LTS)** environment using **NGINX**, **PHP 8.3+ FPM**, **Composer**, and **Node.js (Vite)**.

---

## Table of Contents

1. [Architecture & System Requirements](#architecture--system-requirements)
2. [Step 1: System Packages & Dependencies](#step-1-system-packages--dependencies)
3. [Step 2: Install PHP 8.3 and PHP-FPM](#step-2-install-php-83-and-php-fpm)
4. [Step 3: Install Composer & Node.js](#step-3-install-composer--nodejs)
5. [Step 4: Clone & Place the Application](#step-4-clone--place-the-application)
6. [Step 5: File Permissions & Ownership](#step-5-file-permissions--ownership)
7. [Step 6: Environment Configuration (.env)](#step-6-environment-configuration-env)
8. [Step 7: Install PHP Dependencies & Build Assets](#step-7-install-php-dependencies--build-assets)
9. [Step 8: Database Setup & Migrations](#step-8-database-setup--migrations)
10. [Step 9: Admin Account Provisioning](#step-9-admin-account-provisioning)
11. [Step 10: Configure NGINX](#step-10-configure-nginx)
12. [Step 11: Configure Systemd Queue Worker](#step-11-configure-systemd-queue-worker)
13. [Step 12: Configure Scheduled Tasks (Cron)](#step-12-configure-scheduled-tasks-cron)
14. [Step 13: Configure SSL/TLS with Let's Encrypt (Certbot)](#step-13-configure-ssltls-with-lets-encrypt-certbot)
15. [Step 14: Production Optimization & Caching](#step-14-production-optimization--caching)
16. [Maintenance & Routine Updates](#maintenance--routine-updates)
17. [Troubleshooting & Logs](#troubleshooting--logs)

---

## Architecture & System Requirements

- **Operating System:** Ubuntu 22.04 LTS or Ubuntu 24.04 LTS
- **Web Server:** NGINX 1.18+
- **PHP Version:** PHP 8.3 or higher (with PHP-FPM)
- **Package Managers:** Composer 2.x, Node.js (v20.x or v22.x LTS) & NPM
- **Database:** SQLite (default / zero-config) or MySQL / MariaDB / PostgreSQL
- **Key PHP Extensions:** `bcmath`, `curl`, `intl`, `mbstring`, `sqlite3` (or `mysql`), `tokenizer`, `xml`, `zip`

---

## Step 1: System Packages & Dependencies

Update your package repositories and install fundamental utilities:

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl git unzip zip software-properties-common ca-certificates lsb-release
```

---

## Step 2: Install PHP 8.3 and PHP-FPM

Add the official Ondřej Surý PHP repository to ensure PHP 8.3 and required modules are available:

```bash
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
```

Install PHP 8.3, PHP-FPM, and the required extensions:

```bash
sudo apt install -y \
    php8.3 \
    php8.3-fpm \
    php8.3-cli \
    php8.3-common \
    php8.3-curl \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-zip \
    php8.3-bcmath \
    php8.3-intl \
    php8.3-sqlite3 \
    php8.3-mysql \
    php8.3-redis
```

Verify the installation:

```bash
php -v
sudo systemctl status php8.3-fpm --no-pager
```

### Tune PHP-FPM Limits (Uploads & Memory)

PlanTrack supports file uploads (e.g. comment attachments). Adjust upload limits in `/etc/php/8.3/fpm/php.ini`:

```bash
sudo sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 64M/' /etc/php/8.3/fpm/php.ini
sudo sed -i 's/^post_max_size = .*/post_max_size = 64M/' /etc/php/8.3/fpm/php.ini
sudo sed -i 's/^memory_limit = .*/memory_limit = 256M/' /etc/php/8.3/fpm/php.ini
sudo systemctl restart php8.3-fpm
```

---

## Step 3: Install Composer & Node.js

### 1. Install Composer 2

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

### 2. Install Node.js (v20 LTS or v22 LTS)

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

---

## Step 4: Clone & Place the Application

Deploy the application files to `/var/www/plantrack`:

```bash
# Clone via git
sudo git clone https://github.com/your-org/plantrack.git /var/www/plantrack

# Or create the folder if uploading via rsync / sftp
# sudo mkdir -p /var/www/plantrack
```

Navigate into the application directory:

```bash
cd /var/www/plantrack
```

---

## Step 5: File Permissions & Ownership

Laravel requires write access to the `storage/` and `bootstrap/cache/` directories by the web server user (`www-data`):

```bash
# Set directory ownership to www-data
sudo chown -R www-data:www-data /var/www/plantrack

# Allow current deployment user to manage files while granting www-data group permissions
sudo usermod -a -G www-data $USER
sudo chmod -R 775 /var/www/plantrack/storage
sudo chmod -R 775 /var/www/plantrack/bootstrap/cache
```

---

## Step 6: Environment Configuration (.env)

Create the production environment file from the example template:

```bash
cp .env.example .env
```

Edit `.env` using your preferred editor (`nano .env`):

```ini
APP_NAME=PlanTrack
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://plantrack.example.com

LOG_CHANNEL=stack
LOG_LEVEL=error

# SQLite (default)
DB_CONNECTION=sqlite

# Or MySQL / MariaDB (uncomment and configure if used)
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=plantrack_prod
# DB_USERNAME=plantrack_user
# DB_PASSWORD=your_secure_password

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
FILESYSTEM_DISK=local
```

Generate the application encryption key:

```bash
php artisan key:generate
```

---

## Step 7: Install PHP Dependencies & Build Assets

Run Composer with production optimizations:

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

Install frontend packages and compile client assets with Vite:

```bash
npm ci
npm run build
```

---

## Step 8: Database Setup & Migrations

### If using SQLite (Default):

1. Create the SQLite database file if it does not already exist:

```bash
sudo -u www-data touch database/database.sqlite
sudo chmod 664 database/database.sqlite
```

2. Ensure the parent directory `database/` is writable by `www-data` so SQLite can manage temporary lock journals:

```bash
sudo chown -R www-data:www-data database
sudo chmod 775 database
```

### If using MySQL / MariaDB:

Create the database and dedicated user:

```sql
CREATE DATABASE plantrack_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'plantrack_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON plantrack_prod.* TO 'plantrack_user'@'localhost';
FLUSH PRIVILEGES;
```

### Run Migrations & Storage Symlink:

```bash
php artisan migrate --force
php artisan storage:link
```

---

## Step 9: Admin Account Provisioning

### Option A: Create a Dedicated Administrator (Recommended for Production)

PlanTrack includes a built-in Artisan command to create an administrator or assign the Admin role to an operative:

```bash
php artisan admin:create --name="Lead Admin" --email="admin@example.com" --password="YourSecurePasswordHere"
```

To require a password reset on initial login:

```bash
php artisan admin:create --name="Lead Admin" --email="admin@example.com" --reset
```

### Resetting Administrator Passwords

To reset an existing administrator's password:

```bash
# Provide custom password
php artisan admin:reset-password admin@example.com --password="NewSecurePassword123!"

# Or generate a temporary random key automatically
php artisan admin:reset-password admin@example.com

# Optionally enforce password reset upon next login
php artisan admin:reset-password admin@example.com --reset
```

Aliases available: `php artisan admin:password` and `php artisan admin:reset`.

### Option B: Seed Sample Records (Testing / Staging Only)

If you are deploying a test/staging environment and want initial sample data (projects, plans, components, and default roles):

```bash
php artisan db:seed --force
```

> ⚠️ **Security Warning:** The demo account quick-fill options have been removed from the login screen. If you run `db:seed`, immediately change passwords for any seeded accounts (`admin@plantrack.test`, `marshall@plantrack.test`, `executor@plantrack.test`).

---

## Step 10: Configure NGINX

Install NGINX if not already installed:

```bash
sudo apt install -y nginx
```

Create a new server block configuration for PlanTrack:

```bash
sudo nano /etc/nginx/sites-available/plantrack
```

Paste the following NGINX configuration (replace `plantrack.example.com` with your domain or server IP):

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name plantrack.example.com;

    root /var/www/plantrack/public;
    index index.php index.html;

    charset utf-8;

    # Allow up to 64MB file uploads for attachments
    client_max_body_size 64M;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml application/json application/javascript application/rss+xml application/atom+xml image/svg+xml;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    # FastCGI execution through PHP 8.3 FPM
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 180;
    }

    # Deny access to hidden dotfiles (e.g. .env, .git)
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the configuration and disable default site if applicable:

```bash
sudo ln -s /etc/nginx/sites-available/plantrack /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

# Test NGINX configuration syntax
sudo nginx -t

# Reload NGINX
sudo systemctl reload nginx
```

---

## Step 11: Configure Systemd Queue Worker

PlanTrack processes background jobs (such as email notifications and chunked file processing) using Laravel queues. Set up a dedicated systemd service to keep the worker running automatically.

Create the service file:

```bash
sudo nano /etc/systemd/system/plantrack-worker.service
```

Add the following configuration:

```ini
[Unit]
Description=PlanTrack Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=5
ExecStart=/usr/bin/php /var/www/plantrack/artisan queue:work --sleep=3 --tries=3 --max-time=3600
WorkingDirectory=/var/www/plantrack

[Install]
WantedBy=multi-user.target
```

Reload systemd, enable the service on boot, and start it:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now plantrack-worker
sudo systemctl status plantrack-worker --no-pager
```

---

## Step 12: Configure Scheduled Tasks (Cron)

PlanTrack includes automated routines such as `staging:clean` (which prunes temporary upload chunks hourly). Configure the Laravel cron task for the `www-data` user:

```bash
sudo crontab -u www-data -e
```

Add the following line to the crontab:

```cron
* * * * * cd /var/www/plantrack && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Save and exit.

---

## Step 13: Configure SSL/TLS with Let's Encrypt (Certbot)

To secure your production traffic with HTTPS, use Certbot to acquire a free Let's Encrypt certificate:

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d plantrack.example.com
```

Follow the prompts. Certbot will automatically adjust `/etc/nginx/sites-available/plantrack` with SSL directives and configure automatic certificate renewals via systemd timers.

---

## Step 14: Production Optimization & Caching

Cache configuration, routes, and compiled views to maximize performance in production:

```bash
cd /var/www/plantrack
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Verify that ownership of all newly generated caches remains with `www-data`:

```bash
sudo chown -R www-data:www-data /var/www/plantrack/bootstrap/cache /var/www/plantrack/storage
```

---

## Maintenance & Routine Updates

Whenever you deploy a new version of PlanTrack, run the standard deployment routine:

```bash
cd /var/www/plantrack

# 1. Enable maintenance mode
php artisan down

# 2. Pull newest code
git pull origin main

# 3. Update PHP dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Rebuild frontend assets
npm ci
npm run build

# 5. Execute new database migrations
php artisan migrate --force

# 6. Clear and refresh caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Restart the queue worker
sudo systemctl restart plantrack-worker

# 8. Restore permissions & bring application back online
sudo chown -R www-data:www-data storage bootstrap/cache
php artisan up
```

---

## Troubleshooting & Logs

| Log Type | Path / Command |
| :--- | :--- |
| **PlanTrack Application Logs** | `tail -f /var/www/plantrack/storage/logs/laravel.log` |
| **NGINX Access Logs** | `tail -f /var/log/nginx/access.log` |
| **NGINX Error Logs** | `tail -f /var/log/nginx/error.log` |
| **PHP-FPM Error Logs** | `tail -f /var/log/php8.3-fpm.log` |
| **Queue Worker Output** | `journalctl -u plantrack-worker -f` |

### Common Issues & Quick Fixes

1. **HTTP 500 / "Permission Denied" on storage or cache:**
   ```bash
   sudo chown -R www-data:www-data /var/www/plantrack/storage /var/www/plantrack/bootstrap/cache
   sudo chmod -R 775 /var/www/plantrack/storage /var/www/plantrack/bootstrap/cache
   ```

2. **SQLite Database Read-Only error:**
   ```bash
   sudo chown -R www-data:www-data /var/www/plantrack/database
   sudo chmod 775 /var/www/plantrack/database
   sudo chmod 664 /var/www/plantrack/database/database.sqlite
   ```

3. **Assets Not Loading / 404 for CSS/JS:**
   Ensure `npm run build` ran successfully and generated the `public/build` directory, and verify your `APP_URL` in `.env` matches your domain name (e.g., `https://plantrack.example.com`).
