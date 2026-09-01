<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Utilities;

use CraftCms\Cms\Database\Backups;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\DbBackup;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;

readonly class DbBackupController
{
    use RespondsWithFlash;

    public function __construct(Utilities $utilitiesService)
    {
        if (! $utilitiesService->checkAuthorization(DbBackup::class)) {
            abort(403, 'User is not authorized to perform this action.');
        }
    }

    public function __invoke(Request $request, Backups $backups): Response
    {
        try {
            $backupPath = $backups->backup();
        } catch (Throwable $e) {
            report($e);

            return $this->asFailure(t('Could not create backup: check the logs for details.'));
        }

        if (! is_file($backupPath)) {
            return $this->asFailure(t('Could not create backup: the backup file doesn’t exist.'));
        }

        // Zip it up and delete the SQL file
        $zipPath = File::zip($backupPath);

        File::delete($backupPath);

        if (! $request->boolean('downloadBackup')) {
            return $this->asSuccess(t('Backup created.'));
        }

        return response()->download($zipPath);
    }
}
