# E2E Browser Test App

This directory contains a minimal Laravel application used as the web server for Craft CMS browser (E2E) tests. It runs inside DDEV and serves the Craft CMS control panel that Pest browser tests interact with.

## Setup

1. **Start DDEV:**

   ```bash
   cd tests/_app
   ddev start
   ```

2. **Copy the environment file:**

   ```bash
   ddev exec cp .env.example .env
   ddev exec php artisan key:generate
   ```

3. **Install dependencies:**

   ```bash
   ddev composer install
   ```

4. **Install Craft:**

   ```bash
   ddev craft install \
     --username=admin \
     --password=craftcms2018!! \
     --email=test@craftcms.com \
     --siteName="E2E Tests" \
     --language=en_US
   ```

5. **Run browser tests** (from the repo root):

   ```bash
   ./vendor/bin/pest tests/Browser --compact
   ```

## How It Works

- The DDEV project (`e2e`) runs a minimal Laravel app with Craft CMS installed as a Composer dependency.
- The `craftcms/cms` and `craftcms/yii2-adapter` packages are symlinked from the repo root via path repositories and Docker volume mounts (`docker-compose.craft.yaml`).
- Changes to the CMS source code are reflected immediately in the running app — no rebuild needed.
- The Pest browser tests connect to this app via `BROWSER_TEST_URL` (defaults to `https://e2e.ddev.site`).

## Stopping

```bash
cd tests/_app
ddev stop
```
