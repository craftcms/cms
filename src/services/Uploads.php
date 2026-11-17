<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Closure;
use Craft;
use craft\base\FsInterface;
use craft\base\TemporaryUploadUrlInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\events\AuthorizeUploadEvent;
use craft\helpers\ConfigHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\uploads\StagedUpload;
use craft\uploads\UploadHandlerInterface;
use DateTime;
use Throwable;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\helpers\FileHelper as BaseFileHelper;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\GoneHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Uploads service.
 *
 * Uploads happen in three steps, like Craft 6’s upload sessions:
 *
 * 1. [[start()]] authorizes the upload and returns a URL that accepts the file as the body of a `PUT` request. If the
 *    staging filesystem implements [[TemporaryUploadUrlInterface]], the URL sends the file straight to storage.
 *    Otherwise it goes to Craft’s `uploads/transfer` action.
 * 2. The client sends the file.
 * 3. [[complete()]] passes the file to the upload’s handler, or [[claim()]] passes it to a callback.
 *
 * Each step can reach a different server: uploads are recorded in the database, and files are staged on a
 * filesystem.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getUploads()|`Craft::$app->getUploads()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class Uploads extends Component
{
    /**
     * @event AuthorizeUploadEvent The event that is triggered when an upload starts, and again when it’s completed.
     *
     * Guests can only upload when a handler sets [[AuthorizeUploadEvent::$isValid]] to `true`:
     *
     * ```php
     * use craft\events\AuthorizeUploadEvent;
     * use craft\services\Uploads;
     * use yii\base\Event;
     *
     * Event::on(Uploads::class, Uploads::EVENT_AUTHORIZE_UPLOAD, function(AuthorizeUploadEvent $event) {
     *     if (!$event->user && ($event->params['folderId'] ?? null) === 123 && $event->size <= 10_000_000) {
     *         $event->isValid = true;
     *     }
     * });
     * ```
     */
    public const EVENT_AUTHORIZE_UPLOAD = 'authorizeUpload';

    /**
     * @var string The folder, at the root of the staging filesystem, that holds files until they’re completed. Asset
     * indexing skips folders that begin with an underscore. The hyphen keeps it from matching a named image
     * transform’s folder (`_{handle}`), since handles can’t contain hyphens.
     */
    public const STAGING_FOLDER = '_upload-sessions';

    /** How long upload URLs accept files, in seconds. */
    private const URL_DURATION = 3600;

    /** How long an upload can be completed after it starts, in seconds. */
    private const SESSION_DURATION = 86400;

    private const UPLOADER_DIRECT = 'direct';
    private const UPLOADER_CRAFT = 'craft';
    private const TEMP_DISK = 'temp';

    /**
     * Starts an upload.
     *
     * @param string $handler The upload handler’s class, which must implement [[UploadHandlerInterface]]
     * @param string|null $filename The file’s name, if known. URLs for known filenames only accept the matching MIME type.
     * @param int|null $size The file’s size in bytes, if known. URLs for known sizes only accept files of that size.
     * @param array $params The handler’s destination parameters
     * @param User|null $user The uploader. Defaults to the current user, or a guest.
     * @param string|null $transferUrl The URL for Craft’s `uploads/transfer` action, for uploads the staging
     * filesystem can’t accept directly. A token routes the request, so any URL that reaches Craft works.
     * @return array{id: string, url: string, method: string, headers: array<string, string>, expiresAt: string}
     * @throws HttpException if the upload isn’t allowed
     */
    public function start(string $handler, ?string $filename, ?int $size, array $params = [], ?User $user = null, ?string $transferUrl = null): array
    {
        $user ??= Craft::$app->getUser()->getIdentity();
        $handlerObject = $this->handler($handler);
        $params = $this->authorize($handlerObject, $params, $filename, $size, $user);
        $disk = $handlerObject->stagingVolumeUid($params) ?? self::TEMP_DISK;
        $fs = $this->fs($disk);

        $id = StringHelper::UUID();
        // Staged files have no extension, so web servers don’t serve them as pages or SVGs before Craft checks them
        $path = self::STAGING_FOLDER . "/$id";
        $urlExpiresAt = DateTimeHelper::currentUTCDateTime()->modify(sprintf('+%d seconds', self::URL_DURATION));
        $expiresAt = DateTimeHelper::currentUTCDateTime()->modify(sprintf('+%d seconds', self::SESSION_DURATION));

        $upload = null;

        if ($fs instanceof TemporaryUploadUrlInterface) {
            $mimeType = $filename !== null ? BaseFileHelper::getMimeTypeByExtension($filename) : null;

            // Craft sanitizes SVGs before saving them, so storage must not serve unsanitized ones as images
            if ($mimeType === 'image/svg+xml') {
                $mimeType = 'application/octet-stream';
            }

            $upload = $fs->temporaryUploadUrl($path, $urlExpiresAt, array_filter([
                'ContentType' => $mimeType,
                'ContentLength' => $size,
            ], fn($value) => $value !== null));
        }

        $uploader = $upload !== null ? self::UPLOADER_DIRECT : self::UPLOADER_CRAFT;

        if ($upload === null) {
            // The session enforces the URL’s expiry and single use, so the token lasts as long as the session, and late
            // or repeated requests get a JSON error rather than Craft’s invalid token page
            $token = Craft::$app->getTokens()->createToken(['uploads/transfer', ['uploadId' => $id]], null, $expiresAt);

            if ($token === false) {
                throw new ServerErrorHttpException('Could not create an upload URL.');
            }

            $upload = [
                'url' => UrlHelper::urlWithToken($transferUrl ?? UrlHelper::actionUrl('uploads/transfer'), $token),
                'headers' => [],
            ];
        }

        Db::insert(Table::UPLOADSESSIONS, [
            'id' => $id,
            'owner' => $this->owner($user),
            'handler' => $handler,
            'uploader' => $uploader,
            'disk' => $disk,
            // Craft 6's columns aren't nullable
            'filename' => $filename ?? '',
            'size' => $size ?? 0,
            'chunkSize' => max(1, $size ?? 1),
            'parameters' => $params,
            'state' => ['path' => $path, 'urlExpiresAt' => $urlExpiresAt->getTimestamp()],
            'expiresAt' => Db::prepareDateForDb($expiresAt),
        ]);

        return [
            'id' => $id,
            'url' => $upload['url'],
            'method' => 'PUT',
            'headers' => $upload['headers'],
            'expiresAt' => DateTimeHelper::toIso8601($urlExpiresAt),
        ];
    }

    /**
     * Stores the body of a request sent to Craft’s upload URL.
     *
     * @param string $id The upload’s ID
     * @param resource $stream The request body
     * @return int The number of bytes stored
     * @throws HttpException if the file can’t be received
     */
    public function transfer(string $id, $stream): int
    {
        // Uploads that start before the URL expires may finish after it
        $startedAt = time();
        $session = $this->session($id);

        if (!$session || $session['uploader'] !== self::UPLOADER_CRAFT || !empty($session['state']['canceled'])) {
            throw new NotFoundHttpException('This upload URL is not valid, or its upload was already used.');
        }

        // Buffer the body locally first, so oversized files never reach the filesystem
        $limit = $session['size'] ?? $this->maxSize();
        $buffer = tmpfile();

        try {
            $size = stream_copy_to_stream($stream, $buffer, $limit + 1);

            $this->withLock($id, function() use ($id, $startedAt, $size, $limit, $buffer) {
                // Read the session again while holding the lock, since other requests may have changed it
                $session = $this->session($id);

                if (!$session || !empty($session['state']['canceled'])) {
                    throw new NotFoundHttpException('This upload URL is not valid, or its upload was already used.');
                }

                if (isset($session['state']['received'])) {
                    throw new ConflictHttpException('A file was already uploaded to this URL.');
                }

                if ($session['state']['urlExpiresAt'] < $startedAt) {
                    throw new GoneHttpException('This upload URL has expired.');
                }

                if (!$size) {
                    throw new BadRequestHttpException('The request body was empty.');
                }

                if ($size > $limit) {
                    throw new HttpException(413, 'The file exceeds the maximum upload size.');
                }

                if ($session['size'] !== null && $size !== $session['size']) {
                    throw new BadRequestHttpException('The file isn’t the size the upload was started with.');
                }

                rewind($buffer);
                $this->fs($session['disk'])->writeFileFromStream($session['state']['path'], $buffer);
                // Only mark the file received once it’s stored in full, so it can’t be completed before then
                $this->updateSession($id, ['state' => $session['state'] + ['received' => $size]]);
            });
        } finally {
            fclose($buffer);
        }

        return $size;
    }

    /**
     * Completes an upload, passing its file to its handler, and returns the handler’s response data.
     *
     * Completing an upload again returns the same data.
     *
     * @param string $id The upload’s ID
     * @param User|null $user The uploader. Defaults to the current user, or a guest.
     * @return array
     * @throws HttpException if the upload can’t be completed
     */
    public function complete(string $id, ?User $user = null): array
    {
        return $this->consume($id, $user, function(StagedUpload $upload, array $session) use ($id): array {
            $result = $this->handler($session['handler'])->complete($session['parameters'], $upload);
            $this->updateSession($id, ['result' => $result]);
            return $result;
        });
    }

    /**
     * Passes an upload’s file to a callback instead of its handler. The upload can’t be claimed or completed again.
     *
     * The file is deleted once the callback returns, so copy it if you need it afterward.
     *
     * @template T
     * @param string $id The upload’s ID
     * @param Closure(StagedUpload): T $callback
     * @param User|null $user The uploader. Defaults to the current user, or a guest.
     * @return T
     * @throws HttpException if the upload can’t be claimed
     */
    public function claim(string $id, Closure $callback, ?User $user = null): mixed
    {
        return $this->consume($id, $user, function(StagedUpload $upload, array $session) use ($id, $callback): mixed {
            $result = $callback($upload);
            // Keep the session until it expires, so garbage collection deletes a file sent to its URL again
            $this->updateSession($id, ['state' => $session['state'] + ['claimed' => true]]);
            return $result;
        });
    }

    /**
     * Cancels an upload and deletes its file.
     *
     * The upload is kept until it expires, since direct upload URLs can’t be revoked, so garbage collection deletes
     * any file sent to its URL afterward.
     *
     * @param string $id The upload’s ID
     * @param User|null $user The uploader. Defaults to the current user, or a guest.
     */
    public function cancel(string $id, ?User $user = null): void
    {
        $user ??= Craft::$app->getUser()->getIdentity();

        $this->withLock($id, function() use ($id, $user) {
            $session = $this->session($id);

            if ($session && hash_equals($session['owner'], $this->owner($user))) {
                $this->deleteStagedFile($session);
                $this->updateSession($id, ['state' => $session['state'] + ['canceled' => true]]);
            }
        });
    }

    /**
     * Deletes expired uploads and their files.
     *
     * @return int The number of uploads deleted
     */
    public function deleteExpiredUploads(): int
    {
        $deleted = 0;
        $ids = (new Query())
            ->select(['id'])
            ->from(Table::UPLOADSESSIONS)
            ->where(['<', 'expiresAt', Db::prepareDateForDb(new DateTime())])
            ->column();

        foreach ($ids as $id) {
            try {
                $this->withLock($id, function() use ($id, &$deleted) {
                    if ($session = $this->session($id)) {
                        $this->deleteSession($session);
                        $deleted++;
                    }
                });
            } catch (Throwable $e) {
                Craft::warning("Couldn’t delete expired upload $id: {$e->getMessage()}", __METHOD__);
            }
        }

        return $deleted;
    }

    /**
     * @param Closure(StagedUpload, array): mixed $callback
     */
    private function consume(string $id, ?User $user, Closure $callback): mixed
    {
        $user ??= Craft::$app->getUser()->getIdentity();

        // Hold the lock while the file is processed, so concurrent requests can't process it twice
        return $this->withLock($id, function() use ($id, $user, $callback) {
            $session = $this->session($id);

            if (!$session || !hash_equals($session['owner'], $this->owner($user)) || new DateTime($session['expiresAt'], new \DateTimeZone('UTC')) < new DateTime() || !empty($session['state']['claimed']) || !empty($session['state']['canceled'])) {
                throw new NotFoundHttpException('Upload not found.');
            }

            if ($session['result'] !== null) {
                return $session['result'];
            }

            $handler = $this->handler($session['handler']);
            $params = $this->authorize($handler, $session['parameters'], $session['filename'], $session['size'], $user);

            if (!$this->sameParams($params, $session['parameters'])) {
                throw new ForbiddenHttpException('The upload’s destination is no longer allowed.');
            }

            $fs = $this->fs($session['disk']);
            $path = $session['state']['path'];
            $received = $session['uploader'] === self::UPLOADER_CRAFT
                ? isset($session['state']['received'])
                : $fs->fileExists($path);

            if (!$received) {
                throw new BadRequestHttpException('Nothing has been uploaded yet.');
            }

            $size = $fs->getFileSize($path);

            // Direct upload URLs may not enforce a size, so check it before using the file. A direct URL still works
            // until it expires, so the client can send the file again.
            if (($session['size'] !== null && $size !== $session['size']) || $size > $this->maxSize()) {
                $fs->deleteFile($path);
                throw new BadRequestHttpException($size > $this->maxSize()
                    ? 'The file exceeds the maximum upload size.'
                    : 'The file isn’t the size the upload was started with.');
            }

            $result = $callback(new StagedUpload($fs, $path, $session['filename'], $size), $session);

            // Handlers may have moved the file already
            if ($fs->fileExists($path)) {
                $fs->deleteFile($path);
            }

            return $result;
        });
    }

    private function authorize(UploadHandlerInterface $handler, array $params, ?string $filename, ?int $size, ?User $user): array
    {
        $params = $handler->authorize($params, $filename, $size, $user);

        $event = new AuthorizeUploadEvent([
            'handler' => $handler::class,
            'filename' => $filename,
            'size' => $size,
            'params' => $params,
            'user' => $user,
            'isValid' => $user !== null,
        ]);

        if ($this->hasEventHandlers(self::EVENT_AUTHORIZE_UPLOAD)) {
            $this->trigger(self::EVENT_AUTHORIZE_UPLOAD, $event);
        }

        if (!$event->isValid) {
            throw new ForbiddenHttpException('You are not allowed to upload this file.');
        }

        return $params;
    }

    private function handler(string $class): UploadHandlerInterface
    {
        if (!is_subclass_of($class, UploadHandlerInterface::class)) {
            throw new InvalidArgumentException("$class doesn’t implement " . UploadHandlerInterface::class);
        }

        return Craft::createObject($class);
    }

    private function fs(string $disk): FsInterface
    {
        if ($disk === self::TEMP_DISK) {
            return Craft::$app->getAssets()->getTempAssetUploadFs();
        }

        $volume = Craft::$app->getVolumes()->getVolumeByUid($disk);

        if (!$volume) {
            throw new NotFoundHttpException('Upload not found.');
        }

        return $volume->getFs();
    }

    /**
     * Identifies who started an upload: a user, or a guest’s session.
     */
    private function owner(?User $user): string
    {
        if ($user) {
            return "user:$user->id";
        }

        $session = Craft::$app->getSession();
        $session->open();

        return 'guest:' . hash('sha256', (string)$session->getId());
    }

    private function maxSize(): int
    {
        return ConfigHelper::sizeInBytes(Craft::$app->getConfig()->getGeneral()->maxUploadFileSize);
    }

    private function session(string $id): ?array
    {
        if (!StringHelper::isUUID($id)) {
            return null;
        }

        $session = (new Query())->from(Table::UPLOADSESSIONS)->where(['id' => $id])->one();

        if (!$session) {
            return null;
        }

        $session['filename'] = $session['filename'] !== '' ? $session['filename'] : null;
        $session['size'] = (int)$session['size'] ?: null;
        foreach (['parameters', 'state', 'result'] as $column) {
            if (is_string($session[$column])) {
                $session[$column] = Json::decode($session[$column]);
            }
        }

        $session['parameters'] ??= [];

        return $session;
    }

    private function updateSession(string $id, array $values): void
    {
        Db::update(Table::UPLOADSESSIONS, $values, ['id' => $id]);
    }

    private function deleteSession(array $session): void
    {
        $this->deleteStagedFile($session);
        Db::delete(Table::UPLOADSESSIONS, ['id' => $session['id']]);
    }

    private function deleteStagedFile(array $session): void
    {
        try {
            $fs = $this->fs($session['disk']);

            if ($fs->fileExists($session['state']['path'])) {
                $fs->deleteFile($session['state']['path']);
            }
        } catch (NotFoundHttpException) {
            // The volume is gone, and its files with it
        }
    }

    private function sameParams(array $a, array $b): bool
    {
        $normalize = function(array $params) use (&$normalize): array {
            ksort($params);
            return array_map(fn($value) => is_array($value) ? $normalize($value) : $value, $params);
        };

        return Json::encode($normalize($a)) === Json::encode($normalize($b));
    }

    /**
     * @template T
     * @param Closure(): T $callback
     * @return T
     */
    private function withLock(string $id, Closure $callback): mixed
    {
        $mutex = Craft::$app->getMutex();
        $name = "craft-upload:$id";

        if (!$mutex->acquire($name, 5)) {
            throw new ConflictHttpException('Another request is using this upload. Try again.');
        }

        try {
            return $callback();
        } finally {
            $mutex->release($name);
        }
    }
}
