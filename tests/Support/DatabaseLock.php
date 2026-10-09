<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support;

use RuntimeException;

final class DatabaseLock
{
    private const string WaitingMessage = "Another Pest process is already using the shared test database. Waiting for the lock...\n";

    /** @var null|resource */
    private static $lockHandle;

    public static function acquire(): void
    {
        if (self::$lockHandle !== null) {
            return;
        }

        $lockFile = self::lockFile();
        $lockHandle = fopen($lockFile, 'c+');

        if ($lockHandle === false) {
            throw new RuntimeException("Unable to open the Pest database lock file: $lockFile");
        }

        if (! flock($lockHandle, LOCK_EX | LOCK_NB)) {
            fwrite(STDERR, self::WaitingMessage);
            flock($lockHandle, LOCK_EX);
        }

        self::$lockHandle = $lockHandle;

        register_shutdown_function(static fn () => self::release());
    }

    private static function release(): void
    {
        if (self::$lockHandle === null) {
            return;
        }

        flock(self::$lockHandle, LOCK_UN);
        fclose(self::$lockHandle);

        self::$lockHandle = null;
    }

    private static function lockFile(): string
    {
        $workspaceRoot = dirname(__DIR__, 2);
        $workspaceHash = md5($workspaceRoot);
        $temporaryDirectory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);

        return "$temporaryDirectory/cms-database-$workspaceHash.lock";
    }
}
