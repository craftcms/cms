<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Support\File;
use Illuminate\Support\Facades\View;
use Illuminate\View\Engines\CompilerEngine;
use RuntimeException;

/**
 * Clears Laravel’s compiled Blade views, as `view:clear` does.
 *
 * Composer installs package files with their commit’s modification time, so after an update a Blade view can
 * look older than its stale compiled copy, and Laravel would keep serving that copy.
 *
 * @since 6.0.0
 */
class CompiledViews
{
    /**
     * Returns the compiled views directory, or `null` if none is configured.
     */
    public static function path(): ?string
    {
        $path = config('view.compiled');

        return is_string($path) && $path !== '' ? $path : null;
    }

    /**
     * Deletes the compiled views and resets the current process’s record of which views are compiled.
     *
     * Does nothing when no compiled views directory is configured or it doesn’t exist yet.
     *
     * @throws RuntimeException if any compiled views couldn’t be deleted
     */
    public static function clear(): void
    {
        $path = self::path();

        if ($path === null) {
            return;
        }

        $engine = View::getEngineResolver()->resolve('blade');

        if ($engine instanceof CompilerEngine) {
            $engine->forgetCompiledOrNotExpired();
        }

        // Like view:clear, leave dotfiles such as .gitignore in place. Symlinks are unlinked rather than followed.
        if (File::isDirectory($path) && ! File::cleanDirectory($path, ['.*'])) {
            throw new RuntimeException("Couldn’t delete all compiled views in $path.");
        }
    }
}
