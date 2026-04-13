# AGENTS.md

## Cursor Cloud specific instructions

This repository is a Magento research/development project. As of initial setup, the repo contains only a README placeholder—no application code, dependencies, or services.

### Current state
- **No application code** exists yet (no PHP, JS, or other source files).
- **No dependency manifests** (`composer.json`, `package.json`, etc.).
- **No Docker/container configs** (`Dockerfile`, `docker-compose.yml`).
- **No setup scripts** or CI configuration.

### When code is added
A typical Magento 2 project will require:
- PHP 8.1+ with required extensions (intl, gd, zip, soap, etc.)
- Composer for PHP dependency management
- MySQL/MariaDB 8.0+
- Elasticsearch or OpenSearch 7.x/8.x
- Redis (for cache/sessions)
- Nginx or Apache as web server

Once actual Magento code is added, update this file and the VM update script accordingly.
