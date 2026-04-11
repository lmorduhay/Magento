# AGENTS.md

## Cursor Cloud specific instructions

### Repository state

This repository is for Magento (Adobe Commerce) AI research and development (see `README.md`). As of this setup, **the repo contains no application code, dependency manifests, or configuration files** — only `README.md`.

### Pre-installed environment

| Tool | Version | Notes |
|---|---|---|
| Node.js | v22 (nvm) | `$NVM_DIR` = `/home/ubuntu/.nvm` |
| npm | 10.x | Comes with Node.js |
| Python | 3.12 | System Python |
| pip | 24.x | System pip |
| Git | 2.43 | Pre-configured with user.name/user.email |

### Not pre-installed (needed when Magento code is added)

PHP 8.1+, Composer 2, MySQL 8.0 / MariaDB 10.6, Elasticsearch 7.x / OpenSearch, Nginx/Apache, Docker. See system prompt for Docker-in-Docker installation steps.

### Lint / Test / Build / Run

No lint, test, build, or run commands are available yet — the repo has no application code. When Magento code is committed:

- **Lint**: `vendor/bin/phpcs` or `vendor/bin/phpstan`
- **Test**: `vendor/bin/phpunit`
- **Build**: `bin/magento setup:di:compile && bin/magento setup:static-content:deploy`
- **Run**: PHP-FPM + web server, then access via browser
- **Update script**: configure `SetupVmEnvironment` to run `composer install`
