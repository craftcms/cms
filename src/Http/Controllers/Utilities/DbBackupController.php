<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Utilities;

use craft\helpers\FileHelper;
use craft\web\Application;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\DbBackup;
use Exception;
use Illuminate\Container\Attributes\Give;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final readonly class DbBackupController
{
    public function __construct(Utilities $utilitiesService)
    {
        abort_unless($utilitiesService->checkAuthorization(DbBackup::class), 403, 'User is not authorized to perform this action.');
    }

    public function __invoke(Request $request, #[Give('Craft')] Application $craft)
    {
        try {
            $backupPath = $craft->getDb()->backup();
        } catch (Throwable $e) {
            throw new Exception('Could not create backup: '.$e->getMessage());
        }

        throw_unless(is_file($backupPath), new Exception("Could not create backup: the backup file doesn't exist."));

        // Zip it up and delete the SQL file
        $zipPath = FileHelper::zip($backupPath);

        unlink($backupPath);

        if (! $request->input('downloadBackup')) {
            return new JsonResponse;
        }

        return response()->download($zipPath);
    }
}
