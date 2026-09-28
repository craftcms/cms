---
paths:
  - 'src/Database/Migrations/**'
---

# Migrations

## Bump the schema version when adding a migration
Add core migrations in `src/Database/Migrations/`. Whenever you add a migration, increment `Cms::SCHEMA_VERSION` in `src/Cms.php` in the same change so existing installations detect the pending database update.
