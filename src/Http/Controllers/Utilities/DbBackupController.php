<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Utilities;

use craft\helpers\FileHelper;
use CraftCms\Cms\Database\Backups;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\DbBackup;
use Exception;
use Illuminate\Http\Request;
use Throwable;

use function CraftCms\Cms\t;

final readonly class DbBackupController
{
    public function __construct(Utilities $utilitiesService)
    {
        if (! $utilitiesService->checkAuthorization(DbBackup::class)) {
            abort(403, 'User is not authorized to perform this action.');
        }
    }

    public function __invoke(Request $request, Backups $backups)
    {
        try {
            $backupPath = $backups->backup();
        } catch (Throwable $e) {
            throw new Exception('Could not create backup: '.$e->getMessage());
        }

        if (! is_file($backupPath)) {
            throw new Exception("Could not create backup: the backup file doesn't exist.");
        }

        // Zip it up and delete the SQL file
        $zipPath = FileHelper::zip($backupPath);

        unlink($backupPath);

        if (! $request->input('downloadBackup')) {
            return back()->with('success', t('Backup created.'));
        }

        return response()->download($zipPath);
    }
}
