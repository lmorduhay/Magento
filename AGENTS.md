# AGENTS.md

## Cursor Cloud specific instructions

### Repository state

This repository is intended for Magento (Adobe Commerce) AI research and development, as described in `README.md`. As of initial setup, **the repo contains no application code, dependency manifests, or configuration files** — only `README.md`.

### Environment notes

- **Node.js** v22 is available via nvm (`$NVM_DIR` = `/home/ubuntu/.nvm`).
- **Python 3.12** is available.
- **PHP, Composer, MySQL, Nginx, Elasticsearch, and Docker are NOT pre-installed** in the base VM image. When Magento code is added, these will need to be installed (PHP 8.1+, Composer 2, MySQL 8.0 or MariaDB 10.6, Elasticsearch/OpenSearch, and a web server).
- Docker is not available by default; see the cloud agent system instructions for the recommended Docker-in-Docker installation steps if needed.

### When Magento code is added

A typical Magento 2 development stack requires:

| Service | Notes |
|---|---|
| PHP 8.1+ with extensions (intl, gd, zip, soap, bcmath, etc.) | Core runtime |
| Composer 2 | PHP dependency manager |
| MySQL 8.0 / MariaDB 10.6 | Primary database |
| Elasticsearch 7.x or OpenSearch | Required for catalog search since Magento 2.4 |
| Nginx or Apache | Web server |
| Redis (optional) | Session/cache backend |
| RabbitMQ (optional) | Message queues |

Once code is committed, update the update script (`SetupVmEnvironment`) to run `composer install` (or equivalent) and update this file with concrete startup instructions.

### Lint / Test / Build / Run

No lint, test, build, or run commands are available yet. When Magento code is added:
- **Lint**: `vendor/bin/phpcs`, `vendor/bin/phpstan`, or configured Magento coding standards
- **Test**: `vendor/bin/phpunit` with Magento test suites
- **Build**: `bin/magento setup:di:compile`, `bin/magento setup:static-content:deploy`
- **Run**: Start web server + PHP-FPM, then access via browser
