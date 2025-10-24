<?php

namespace CraftCms\Cms\Http\Controllers\Dashboard\Widgets;

use Craft;
use craft\helpers\FileHelper;
use craft\web\Application;
use CraftCms\Cms\License\License;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\Support\Composer;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use Exception;
use GuzzleHttp\RequestOptions;
use Illuminate\Container\Attributes\Give;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;
use Throwable;
use ZipArchive;

use function CraftCms\Cms\maxPowerCaptain;

final readonly class CraftSupportController
{
    public function __construct(
        private Composer $composer,
        private License $license,
        private Api $api,
    ) {}

    public function __invoke(Request $request, #[Give('Craft')] Application $craft): JsonResponse
    {
        $request->validate([
            'namespace' => ['nullable', 'string'],
        ]);

        maxPowerCaptain();

        $namespace = $request->has('namespace') ? $request->get('namespace').'.' : '';
        $data = $namespace ? $request->get($namespace) : $request->all();

        $validator = Validator::make($data, [
            'fromEmail' => ['required', 'email', 'min:5', 'max:255'],
            'message' => ['required', 'string'],
            'attachAdditionalFile' => ['nullable', 'file', 'max:3072'],
            'attachLogs' => ['nullable', 'boolean'],
            'attachDbBackup' => ['nullable', 'boolean'],
            'attachTemplates' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return new JsonResponse([
                'success' => false,
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $parts = [
            [
                'name' => 'email',
                'contents' => $data['fromEmail'],
            ],
            [
                'name' => 'name',
                'contents' => $request->user()->fullName,
            ],
            [
                'name' => 'message',
                'contents' => $data['message'],
            ],
        ];

        // If there's a custom attachment, see if we should include it in the zip
        /** @var ?UploadedFile $attachment */
        $attachment = $data['attachAdditionalFile'] ?? null;

        // Create the SupportAttachment zip
        try {
            $zipData = $this->createZip(
                $data['attachLogs'],
                $data['attachDbBackup'],
                $data['attachTemplates'],
                $attachment,
            );
            $data['message'] .= $zipData['message'];

            $parts[] = [
                'name' => 'attachments[0]',
                'contents' => fopen($zipData['zipPath'], 'rb'),
                'filename' => 'SupportAttachment-'.FileHelper::sanitizeFilename(Sites::getPrimarySite()->getName()).'.zip',
            ];
        } catch (Throwable $e) {
            Log::warning('Error creating support zip: '.$e->getMessage(), [__METHOD__]);
            $data['message'] .= "\n\n---\n\nError creating zip: ".$e->getMessage();
        }

        // Uploaded attachment separately?
        if ($attachment && ! $this->shouldZipAttachment($attachment)) {
            $parts[] = [
                'name' => 'attachments[1]',
                'contents' => $attachment->get(),
                'filename' => $attachment->getClientOriginalName(),
            ];
        }

        try {
            $this->api->request('POST', 'support', [
                RequestOptions::MULTIPART => $parts,
            ]);

            return new JsonResponse([
                'success' => true,
                'errors' => [],
            ]);
        } catch (Throwable $requestException) {
            Log::error("Unable to send support request: {$requestException->getMessage()}", [__METHOD__]);
            report($requestException);

            return new JsonResponse([
                'success' => false,
                'errors' => [
                    'form' => [
                        'Unable to send support request. Please email it to support@craftcms.com instead.',
                    ],
                ],
            ], $requestException->getCode() ?: 500);
        } finally {
            // Delete the zip file
            if (isset($zipData)) {
                File::delete($zipData['zipPath']);
            }
        }
    }

    /**
     * Returns whether we should zip the custom support attachment.
     */
    private function shouldZipAttachment(UploadedFile $file): bool
    {
        // If it's > 2 MB, just do it
        if ($file->getSize() > 1024 * 1024 * 2) {
            return true;
        }

        $mimeType = $file->getMimeType();

        if ($mimeType === null) {
            return true;
        }

        if (str_starts_with($mimeType, 'text/')) {
            return false;
        }

        if (str_starts_with($mimeType, 'image/')) {
            return false;
        }

        if (str_contains($mimeType, 'xml/')) {
            return false;
        }

        if (in_array($mimeType, [
            'application/json',
            'application/pdf',
            'application/x-yaml',
        ], true)) {
            return false;
        }

        return true;
    }

    /**
     * @return array{
     *     message: string,
     *     zipPath: string
     * }
     */
    public function createZip(
        bool $attachLogs,
        bool $attachDbBackup,
        bool $attachTemplates,
        ?UploadedFile $attachment
    ): array {
        $zipPath = Craft::$app->getPath()->getTempPath().'/'.Str::uuid()->toString().'.zip';
        $message = '';

        // Create the zip
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new RuntimeException('Cannot create zip at '.$zipPath);
        }

        // License key
        if (($licenseKey = $this->license->key()) !== null) {
            $zip->addFromString('license.key', $licenseKey);
        }

        // Composer files
        try {
            $zip->addFile($this->composer->getJsonPath(), 'composer.json');
            if (($composerLockPath = $this->composer->getLockPath()) !== null) {
                $zip->addFile($composerLockPath, 'composer.lock');
            }
        } catch (Exception) {
            // that's fine
        }

        // project.yaml
        $projectConfig = app(ProjectConfig::class)->get();
        $projectConfig = Craft::$app->getSecurity()->redactIfSensitive('', $projectConfig);
        $zip->addFromString('project.yaml', Yaml::dump($projectConfig, 20, 2));

        // project.yaml backups
        $configBackupPath = Craft::$app->getPath()->getConfigBackupPath(false);
        $zip->addGlob($configBackupPath.'/*', 0, [
            'remove_all_path' => true,
            'add_path' => 'config-backups/',
        ]);

        // Logs
        if ($attachLogs) {
            $logPath = Craft::$app->getPath()->getLogPath();
            FileHelper::addFilesToZip($zip, $logPath, 'logs', [
                'only' => ['*.log'],
                'except' => ['web-404s.log'],
                'recursive' => false,
            ]);
        }

        // DB backups
        if ($attachDbBackup) {
            // Make a fresh database backup of the current schema/data. We want all data from all tables
            // for debugging.
            try {
                $backupPath = Craft::$app->getDb()->backup();
                $zip->addFile($backupPath, basename($backupPath));
            } catch (Throwable $e) {
                Log::warning('Error adding database backup to support request: '.$e->getMessage(), [__METHOD__]);
                $message .= "\n\n---\n\nError adding database backup: ".$e->getMessage();
            }
        }

        // Templates
        if ($attachTemplates) {
            $templatesPath = Craft::$app->getPath()->getSiteTemplatesPath();
            FileHelper::addFilesToZip($zip, $templatesPath, 'templates');
        }

        // Attachment?
        if ($attachment && $this->shouldZipAttachment($attachment)) {
            $zip->addFile($attachment->getRealPath(), $attachment->getClientOriginalName());
        }

        // Close and attach the zip
        $zip->close();

        return [
            'message' => $message,
            'zipPath' => $zipPath,
        ];
    }
}
