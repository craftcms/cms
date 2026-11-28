<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

use Closure;

/**
 * Settings for database backups and restores.
 *
 * @since 6.0.0
 */
class BackupsConfig extends BaseConfig
{
    /** Whether Craft should back up the database before applying a system update. */
    public bool $onUpdate = true;

    /**
     * The shell command Craft should run to back up the database, `null` to run `mysqldump`, `pg_dump`, or `sqlite3`,
     * or `false` to disable backups.
     *
     * A command can include these tokens, which Craft replaces when it runs:
     *
     * - `{file}` – the target backup file path
     * - `{port}` – the database port
     * - `{server}` – the database hostname
     * - `{user}` – the database user
     * - `{password}` – the database user’s password
     * - `{database}` – the database name
     * - `{schema}` – the database schema, if any
     *
     * A closure receives the default command and returns the command to run.
     */
    public string|null|false|Closure $command = null;

    /**
     * The output format PostgreSQL backups should use: `custom`, `directory`, `tar`, or `plain`. `null` leaves it to
     * `pg_dump`, which uses `plain`. It has no effect with other databases.
     *
     * @see https://www.postgresql.org/docs/current/app-pgdump.html
     */
    public ?string $commandFormat = null;

    /**
     * The shell command Craft should run to restore a database backup, `null` to run `mysql`, `psql`, or `sqlite3`,
     * or `false` to disable restores.
     *
     * A command can include these tokens, which Craft replaces when it runs:
     *
     * - `{path}` – the backup file path
     * - `{port}` – the database port
     * - `{server}` – the database hostname
     * - `{user}` – the database user
     * - `{database}` – the database name
     * - `{schema}` – the database schema, if any
     *
     * A closure receives the default command and returns the command to run.
     */
    public string|null|false|Closure $restoreCommand = null;

    /** The number of backups Craft keeps before it deletes the oldest ones, or `false` to keep them all. */
    public int|false $max = 20;

    /**
     * Returns whether the database should be backed up before a system update.
     */
    public function shouldBackUpOnUpdate(): bool
    {
        return $this->onUpdate && $this->command !== false;
    }

    public function onUpdate(bool $value = true): self
    {
        $this->onUpdate = $value;

        return $this;
    }

    public function command(string|null|false|Closure $value): self
    {
        $this->command = $value;

        return $this;
    }

    public function commandFormat(?string $value): self
    {
        $this->commandFormat = $value;

        return $this;
    }

    public function restoreCommand(string|null|false|Closure $value): self
    {
        $this->restoreCommand = $value;

        return $this;
    }

    public function max(int|false $value): self
    {
        $this->max = $value;

        return $this;
    }
}
