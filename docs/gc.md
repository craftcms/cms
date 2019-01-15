# Garbage Collection

Craft occasionally runs a few garbage collection routines to remove stale data, including:

- Purge any expired pending user accounts (per the <config:purgePendingUsersDuration> config setting).
- Hard delete expired soft-deleted rows (per the <config:softDeleteDuration> config setting). 
- Delete stale user session data.

By default, each web request has a 1 in 100,000 chance of triggering garbage collection. That can be configured from `config/app.php` by overriding <api:craft\services\Gc::$probability>.

```php
return [
    'components' => [
        'gc' => [
            'probability' => 0,     // no chance
            'probability' => 1,     // 1 in 1,000,000
            'probability' => 10,    // 1 in 100,000 (default)
            'probability' => 100,   // 1 in 10,000
            'probability' => 1000,  // 1 in 1,000
            'probability' => 10000, // 1 in 100
        ],
    ],
];
```

## Forcing Garbage Collection

You can force garbage collection to run at any time with a terminal command.

In your terminal, go to your Craft project and then run:

```bash
./craft gc
```

If the shell is interactive, you will be asked whether Craft should delete all trashed items. If you enter `yes` at that prompt, all of your database rows that have been soft-deleted will get hard-deleted immediately, even if they hadn’t waited the full [soft delete duration](config:softDeleteDuration) yet.

You can also force hard-deletion for all soft-deleted rows with the `delete-all-trashed` option:

```bash
./craft gc --delete-all-trashed=1
```
