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
        if (! $utilitiesService->checkAuthorization(DbBackup::class)) {
            abort(403, 'User is not authorized to perform this action.');
        }
    }

    public function __invoke(Request $request, #[Give('Craft')] Application $craft)
    {
        try {
            $backupPath = $craft->getDb()->backup();
        } catch (Throwable $e) {
            throw new Exception('Could not create backup: '.$e->getMessage());
        }

        if (! is_file($backupPath)) {
            throw new Exception("Could not create backup: the backup file doesn't exist.");
        }

        // Zip it up and delete the SQL file
        $zipPath = FileHelper::zip($backupPath);

        unlink($backupPath);

        if (! $request->get('downloadBackup')) {
            return new JsonResponse;
        }

        return response()->download($zipPath);
    }
}
