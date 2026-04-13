# AGENTS.md

## Cursor Cloud specific instructions

### Codebase Overview

This is a **Magento 2 Open Source (2.4.7-p4)** e-commerce platform installation with the Luma theme. The codebase follows standard Magento 2 project structure installed via Composer from the Mage-OS mirror (no Adobe Marketplace keys required).

### Available Skills

| Skill | File | Use When |
|-------|------|----------|
| **Magento Architect** | `.cursor/skills/magento-architect.md` | Designing architecture, planning modules, service contracts, DB schema, performance decisions |
| **Magento Coder** | `.cursor/skills/magento-coder.md` | Writing PHP code, modules, business logic, tests, API endpoints, code reviews |
| **Magento UX Designer** | `.cursor/skills/magento-ux-designer.md` | Frontend themes, layouts, templates, CSS/LESS, JavaScript, UI components |

### Service Stack

| Service | Version | Start Command | Port |
|---------|---------|---------------|------|
| **PHP-FPM** | 8.2 | `sudo php-fpm8.2 --nodaemonize` | Unix socket `/run/php/php8.2-fpm.sock` |
| **MySQL** | 8.0 | `sudo mysqld --user=mysql &` | 3306 (socket at `/var/run/mysqld/mysqld.sock`) |
| **Elasticsearch** | 7.17 | `sudo -u elasticsearch ES_JAVA_HOME=/usr/lib/jvm/java-11-openjdk-amd64 /usr/share/elasticsearch/bin/elasticsearch` | 9200 |
| **Nginx** | 1.24 | `sudo nginx -g "daemon off;"` | 80 |

### Starting All Services

Run services in this order (each in a separate terminal or background):

```bash
# 1. MySQL
sudo mkdir -p /var/run/mysqld && sudo chown mysql:mysql /var/run/mysqld
sudo mysqld --user=mysql &
sleep 3
sudo chmod 755 /var/run/mysqld
sudo mysql -e "SET GLOBAL log_bin_trust_function_creators = 1;"

# 2. Elasticsearch
sudo -u elasticsearch ES_JAVA_HOME=/usr/lib/jvm/java-11-openjdk-amd64 /usr/share/elasticsearch/bin/elasticsearch &

# 3. PHP-FPM
sudo php-fpm8.2 --nodaemonize &

# 4. Nginx
sudo nginx -g "daemon off;" &
```

Wait ~20 seconds for Elasticsearch to start, then verify: `curl -s http://localhost:9200`

### Key URLs

- **Storefront**: `http://localhost/`
- **Admin Panel**: `http://localhost/admin_56u1a3p`
- **Admin Credentials**: `admin` / `Admin123!`

### Important Gotchas

1. **MySQL socket permissions**: After starting MySQL, always run `sudo chmod 755 /var/run/mysqld` — the directory is created with restrictive permissions.
2. **PHP-FPM runs as `ubuntu`**: The FPM pool is configured to run as the `ubuntu` user (not `www-data`) to avoid permission conflicts in development.
3. **Nginx runs as `ubuntu`**: Same reason — `/etc/nginx/nginx.conf` is set to `user ubuntu;`.
4. **Developer mode**: The installation is configured in developer mode — static assets and DI are generated on-the-fly.
5. **Two-Factor Auth disabled**: `Magento_TwoFactorAuth` and `Magento_AdminAdobeImsTwoFactorAuth` are disabled for development convenience.
6. **Magento installed from Mage-OS mirror**: Uses `https://mirror.mage-os.org/` as the Composer repository — no Adobe Marketplace auth keys needed.
7. **`composer install` requires the mirror repo**: The `composer.json` includes the Mage-OS repository URL. Running `composer install` works without auth keys.

### Lint / Test / Build / Run

```bash
# Lint (PHP CodeSniffer with Magento standard)
vendor/bin/phpcs --standard=Magento2 app/code/

# Auto-fix lint issues
vendor/bin/phpcbf --standard=Magento2 app/code/

# Unit Tests
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/Vendor/Module/Test/Unit/

# Static Tests
vendor/bin/phpunit -c dev/tests/static/phpunit.xml.dist

# Compile DI
php bin/magento setup:di:compile

# Deploy Static Content
php bin/magento setup:static-content:deploy -f en_US

# Reindex
php bin/magento indexer:reindex

# Clear Cache
php bin/magento cache:flush
```

### Custom Module Development

Custom modules go in `app/code/<Vendor>/<Module>/`. After creating or modifying a module:

```bash
php bin/magento module:enable Vendor_Module
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```
