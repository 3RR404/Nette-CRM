# Nette CRM

A full-featured CRM application built with the [Nette Framework](https://nette.org/) (PHP 8.1+), Latte templates, and MySQL.

## Features

- **Contacts** — lead/prospect/customer lifecycle with status tracking and source attribution
- **Companies** — company profiles linked to contacts and deals
- **Deals** — sales pipeline with stages: New → Qualified → Proposal → Negotiation → Won/Lost
- **Tasks** — call, meeting, email, and deadline tasks assignable to any entity
- **Notes** — freeform notes attached to contacts, companies, or deals
- **Tags** — color-coded labels on contacts, companies, and deals
- **Reports** — aggregated sales and activity overview
- **Activity log** — full audit trail of changes with old/new values
- **User management** — roles: `admin`, `manager`, `agent`

## Stack

- PHP 8.1+, Nette 3.2, Latte 3.0, Tracy
- MySQL 8.0
- Nginx + PHP-FPM (Docker)

## Quick Start (Docker)

```bash
git clone https://github.com/3RR404/Nette-CRM.git
cd Nette-CRM

# Start all services (nginx :8184, MySQL :3377)
docker compose up -d

# Copy Docker DB config
cp app/config/local.neon.docker app/config/local.neon

# Install PHP dependencies
docker compose exec crm_phpfpm composer install
```

Open **http://localhost:8184** in your browser.

The database schema and seed data are applied automatically on first start from `db/migrations/001_initial_schema.sql`.

### Default credentials

| Role    | Email                | Password   |
|---------|----------------------|------------|
| Admin   | admin@crm.local      | `admin123` |
| Manager | manager@crm.local    | `admin123` |
| Agent   | agent@crm.local      | `admin123` |

## Manual Setup (without Docker)

**Requirements:** PHP 8.1+, Composer, MySQL 8.0

```bash
composer install

# Create database and run migration
mysql -u root -p -e "CREATE DATABASE nette_crm CHARACTER SET utf8mb4"
mysql -u root -p nette_crm < db/migrations/001_initial_schema.sql

# Configure DB connection
cp app/config/local.neon.example app/config/local.neon
# Edit app/config/local.neon with your credentials

# Point your web server document root to ./www
# Enable URL rewriting (see www/.htaccess for Apache)
```

## Project Structure

```
app/
  Bootstrap.php              # Application bootstrap
  Core/RouterFactory.php     # URL routing
  Model/                     # Business logic (Company, Contact, Deal, Task, ...)
  UI/
    Presenters/              # Controllers (Nette presenters)
    templates/               # Latte templates
  config/
    common.neon              # Shared DI configuration
    local.neon               # Local DB config (git-ignored)
db/
  migrations/                # SQL schema & seed data
www/
  index.php                  # Entry point
  .htaccess                  # Apache rewrite rules
.docker/
  nginx/                     # Nginx Dockerfile + vhost config
  phpfpm/                    # PHP-FPM Dockerfile + php.ini
docker-compose.yml
```

## Configuration

All environment-specific settings go in `app/config/local.neon` (git-ignored).  
Use `local.neon.example` as a template for local development or `local.neon.docker` when running inside containers.

## Development

Tracy debugger is enabled in development mode — error details appear in the browser.  
PHP logs are written to `log/` and `./var/log/php` (Docker volume).

Static analysis:

```bash
docker compose exec php-fpm vendor/bin/phpstan analyse app
```

## License

MIT
