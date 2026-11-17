<?php

namespace crafttests\unit\services;

use Craft;
use craft\db\Table;
use craft\elements\Asset;
use craft\elements\User;
use craft\events\AuthorizeUploadEvent;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\services\Uploads;
use craft\test\TestCase;
use craft\uploads\AssetUploadHandler;
use craft\uploads\StagedUpload;
use crafttests\fixtures\AssetFixture;
use crafttests\unit\services\uploads\DirectUploadFs;
use DateTime;
use yii\base\Event;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/** Exercises uploads that go straight to the destination filesystem, and the fallback through Craft. */
class UploadsTest extends TestCase
{
    private const FolderId = 1000;

    private string $root;
    private string $originalFsHandle;

    public function _fixtures(): array
    {
        return ['assets' => ['class' => AssetFixture::class]];
    }

    protected function _before(): void
    {
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());
        DirectUploadFs::$enabled = true;
        DirectUploadFs::$calls = [];

        $this->root = sys_get_temp_dir() . '/craft-uploads-test';
        FileHelper::removeDirectory($this->root);
        $fs = new DirectUploadFs(['name' => 'Direct Upload Test', 'handle' => 'directUploadTest', 'path' => $this->root, 'hasUrls' => false]);
        self::assertTrue(Craft::$app->getFs()->saveFilesystem($fs));

        $volume = Craft::$app->getVolumes()->getVolumeById(1000);
        $this->originalFsHandle = $volume->getFsHandle(false);
        $volume->setFsHandle('directUploadTest');
        self::assertTrue(Craft::$app->getVolumes()->saveVolume($volume));
    }

    protected function _after(): void
    {
        Event::off(Uploads::class, Uploads::EVENT_AUTHORIZE_UPLOAD);

        foreach (Asset::find()->volumeId(1000)->filename(['notes*', 'logo*'])->all() as $asset) {
            Craft::$app->getElements()->deleteElement($asset, true);
        }

        $volume = Craft::$app->getVolumes()->getVolumeById(1000);
        $volume->setFsHandle($this->originalFsHandle);
        Craft::$app->getVolumes()->saveVolume($volume);
        Craft::$app->getFs()->removeFilesystem(Craft::$app->getFs()->getFilesystemByHandle('directUploadTest'));
        FileHelper::removeDirectory($this->root);
        Craft::$app->getUser()->setIdentity(null);
    }

    public function testDirectUploadsAreMovedIntoPlaceAndCompleteOnce(): void
    {
        $contents = "Meeting notes\n";
        $upload = $this->start('notes.txt', strlen($contents));

        // The URL is signed for the file's type and size, and staged where indexing won't find it
        self::assertCount(1, DirectUploadFs::$calls);
        [$path, $options] = DirectUploadFs::$calls[0];
        self::assertStringStartsWith(Uploads::STAGING_FOLDER . '/', $path);
        self::assertSame(['ContentType' => 'text/plain', 'ContentLength' => strlen($contents)], $options);
        self::assertSame(['x-amz-meta-visibility' => 'private'], $upload['headers']);

        $this->send($upload, $contents);
        $result = Craft::$app->getUploads()->complete($upload['id']);

        $asset = Asset::find()->id($result['assetId'])->one();
        self::assertSame('notes.txt', $asset->getFilename());
        self::assertSame(self::FolderId, $asset->folderId);
        self::assertSame(strlen($contents), $asset->size);
        self::assertSame($contents, file_get_contents("$this->root/{$asset->getPath()}"));
        self::assertFileDoesNotExist("$this->root/$path", 'The staged file must be moved, not copied.');

        // Completing again returns the same result without creating another asset (PostgreSQL's jsonb reorders keys)
        self::assertEquals($result, Craft::$app->getUploads()->complete($upload['id']));
        self::assertSame(1, (int)Asset::find()->folderId(self::FolderId)->filename('notes*')->count());
    }

    public function testClaimedUploadsCantBeReusedAndLateFilesAreCollected(): void
    {
        $upload = $this->start('notes.txt', 3);
        $path = $this->send($upload, 'one');
        self::assertSame('one', Craft::$app->getUploads()->claim($upload['id'], fn(StagedUpload $file) => stream_get_contents($file->fs->getFileStream($file->path))));
        self::assertFileDoesNotExist($path);

        try {
            Craft::$app->getUploads()->claim($upload['id'], fn() => null);
            self::fail('Claiming again must fail.');
        } catch (NotFoundHttpException) {
        }

        // The direct URL still works after the claim, so a file sent again must be deleted once the upload expires
        $this->send($upload, 'two');
        Db::update(Table::UPLOADSESSIONS, ['expiresAt' => Db::prepareDateForDb(new DateTime('-1 minute'))], ['id' => $upload['id']]);
        self::assertSame(1, Craft::$app->getUploads()->deleteExpiredUploads());
        self::assertFileDoesNotExist($path);
    }

    public function testImageDimensionsAreReadPastLargeMetadata(): void
    {
        Craft::$app->getRequest()->setIsCpRequest(true);
        Craft::$app->getConfig()->getGeneral()->sanitizeCpImageUploads = false;

        // A JPEG whose size frame comes after 120 KB of metadata, like a photo with a large EXIF block
        $image = imagecreatetruecolor(37, 23);
        ob_start();
        imagejpeg($image);
        $jpeg = ob_get_clean();
        $segment = "\xFF\xE1" . pack('n', 60002) . str_repeat("\0", 60000);
        $jpeg = substr($jpeg, 0, 2) . $segment . $segment . substr($jpeg, 2);

        try {
            $upload = $this->start('logo.jpg', strlen($jpeg));
            $this->send($upload, $jpeg);
            $result = Craft::$app->getUploads()->complete($upload['id']);
        } finally {
            Craft::$app->getRequest()->setIsCpRequest(null);
            Craft::$app->getConfig()->getGeneral()->sanitizeCpImageUploads = true;
        }

        $asset = Asset::find()->id($result['assetId'])->one();
        self::assertSame($jpeg, file_get_contents("$this->root/{$asset->getPath()}"), 'The image must be moved into place, not cleaned.');
        self::assertSame([37, 23], [$asset->width, $asset->height]);
    }

    public function testFilenameConflictsKeepBothFiles(): void
    {
        $first = $this->start('notes.txt', 3);
        $this->send($first, 'one');
        Craft::$app->getUploads()->complete($first['id']);

        $second = $this->start('notes.txt', 3);
        $this->send($second, 'two');
        $result = Craft::$app->getUploads()->complete($second['id']);

        self::assertSame('notes.txt', $result['filename']);
        self::assertNotSame('notes.txt', $result['suggestedFilename']);
        $asset = Asset::find()->id($result['assetId'])->one();
        self::assertSame($result['suggestedFilename'], $asset->getFilename());
        self::assertSame('two', file_get_contents("$this->root/{$asset->getPath()}"));
    }

    public function testImagesCraftWouldCleanAreSavedThroughPhp(): void
    {
        // Front-end SVGs are sanitized, so they can't be moved into place untouched
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect width="1" height="1"/></svg>';
        $upload = $this->start('logo.svg', strlen($svg));
        $this->send($upload, $svg);
        $result = Craft::$app->getUploads()->complete($upload['id']);

        $asset = Asset::find()->id($result['assetId'])->one();
        $stored = file_get_contents("$this->root/{$asset->getPath()}");
        self::assertStringContainsString('<rect', $stored);
        self::assertStringNotContainsString('<script', $stored);
    }

    public function testSvgsAreStagedAsBinaryDataAndKeepTheirDimensions(): void
    {
        // Even when Craft wouldn't sanitize them, SVGs are saved from a local copy, which reads their dimensions
        Craft::$app->getRequest()->setIsCpRequest(true);
        Craft::$app->getConfig()->getGeneral()->sanitizeCpImageUploads = false;
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="30"><rect width="1" height="1"/></svg>';

        try {
            $upload = $this->start('logo.svg', strlen($svg));
            $this->send($upload, $svg);
            $result = Craft::$app->getUploads()->complete($upload['id']);
        } finally {
            Craft::$app->getRequest()->setIsCpRequest(null);
            Craft::$app->getConfig()->getGeneral()->sanitizeCpImageUploads = true;
        }

        // Storage must not serve the unsanitized file as an SVG while it's staged
        [$path, $options] = DirectUploadFs::$calls[0];
        self::assertSame(Uploads::STAGING_FOLDER . "/{$upload['id']}", $path);
        self::assertSame('application/octet-stream', $options['ContentType']);

        $asset = Asset::find()->id($result['assetId'])->one();
        self::assertSame([40, 30], [$asset->width, $asset->height]);
    }

    public function testCanceledUploadsCollectFilesSentAfterward(): void
    {
        $upload = $this->start('notes.txt', 3);
        Craft::$app->getUploads()->cancel($upload['id']);

        // Direct upload URLs can't be revoked, so a file can still arrive
        $path = $this->send($upload, 'one');

        try {
            Craft::$app->getUploads()->complete($upload['id']);
            self::fail('Canceled uploads must not complete.');
        } catch (NotFoundHttpException) {
        }

        Db::update(Table::UPLOADSESSIONS, ['expiresAt' => Db::prepareDateForDb(new DateTime('-1 minute'))], ['id' => $upload['id']]);
        self::assertSame(1, Craft::$app->getUploads()->deleteExpiredUploads());
        self::assertFileDoesNotExist($path);
    }

    public function testFilesOfTheWrongSizeAreRejectedAndDeleted(): void
    {
        $upload = $this->start('notes.txt', 3);
        $path = $this->send($upload, 'four');

        try {
            Craft::$app->getUploads()->complete($upload['id']);
            self::fail('Files must be the size the upload started with.');
        } catch (BadRequestHttpException $exception) {
            self::assertStringContainsString('size', $exception->getMessage());
        }

        self::assertFileDoesNotExist($path);

        // The URL still works, so the right file can be sent and completed
        $this->send($upload, 'one');
        self::assertArrayHasKey('assetId', Craft::$app->getUploads()->complete($upload['id']));
    }

    public function testOnlyTheUploaderCanCompleteOrCancel(): void
    {
        $upload = $this->start('notes.txt', 3);
        $this->send($upload, 'one');
        $other = new User(['id' => 99999, 'username' => 'other']);

        try {
            Craft::$app->getUploads()->complete($upload['id'], $other);
            self::fail('Other users must not complete the upload.');
        } catch (NotFoundHttpException) {
        }

        Craft::$app->getUploads()->cancel($upload['id'], $other);
        self::assertArrayHasKey('assetId', Craft::$app->getUploads()->complete($upload['id']));
    }

    public function testGuestsNeedAnEventToAllowThem(): void
    {
        Craft::$app->getUser()->setIdentity(null);

        try {
            $this->start('notes.txt', 3);
            self::fail('Guests must not upload by default.');
        } catch (ForbiddenHttpException) {
        }

        $seen = null;
        Event::on(Uploads::class, Uploads::EVENT_AUTHORIZE_UPLOAD, function(AuthorizeUploadEvent $event) use (&$seen) {
            $seen = $event;
            $event->isValid = $event->user === null && $event->params['folderId'] === self::FolderId && $event->size <= 10;
        });

        $upload = $this->start('notes.txt', 3);
        self::assertSame(AssetUploadHandler::class, $seen->handler);
        self::assertSame(['site' => 'form'], $seen->params['context']);
        $this->send($upload, 'one');
        $result = Craft::$app->getUploads()->complete($upload['id']);
        self::assertNull(Asset::find()->id($result['assetId'])->one()->uploaderId);

        // The event decides again when the upload completes
        $denied = $this->start('notes.txt', 3);
        $this->send($denied, 'two');
        Event::off(Uploads::class, Uploads::EVENT_AUTHORIZE_UPLOAD);
        $this->expectException(ForbiddenHttpException::class);
        Craft::$app->getUploads()->complete($denied['id']);
    }

    public function testFilesystemsWithoutDirectUploadsReceiveFilesThroughCraft(): void
    {
        DirectUploadFs::$enabled = false;
        $upload = $this->start('notes.txt', 3);

        parse_str((string)parse_url($upload['url'], PHP_URL_QUERY), $query);
        $token = $query[Craft::$app->getConfig()->getGeneral()->tokenParam];
        self::assertSame(['uploads/transfer', ['uploadId' => $upload['id']]], Craft::$app->getTokens()->getTokenRoute($token));
        self::assertSame([], $upload['headers']);

        try {
            Craft::$app->getUploads()->complete($upload['id']);
            self::fail('Uploads must not complete before their file arrives.');
        } catch (BadRequestHttpException) {
        }

        self::assertSame(3, Craft::$app->getUploads()->transfer($upload['id'], $this->stream('one')));

        try {
            Craft::$app->getUploads()->transfer($upload['id'], $this->stream('two'));
            self::fail('Each upload URL must only accept one file.');
        } catch (ConflictHttpException) {
        }

        $asset = Asset::find()->id(Craft::$app->getUploads()->complete($upload['id'])['assetId'])->one();
        self::assertSame('one', file_get_contents("$this->root/{$asset->getPath()}"));
    }

    private function start(string $filename, int $size): array
    {
        return Craft::$app->getUploads()->start(AssetUploadHandler::class, $filename, $size, [
            'folderId' => self::FolderId,
            'context' => ['site' => 'form'],
        ]);
    }

    /** Sends a file to a direct upload URL, as a client would, and returns where it was stored. */
    private function send(array $upload, string $contents): string
    {
        $path = "$this->root/" . substr($upload['url'], strlen('https://storage.test/'));
        FileHelper::writeToFile($path, $contents);
        return $path;
    }

    /** @return resource */
    private function stream(string $contents)
    {
        $stream = fopen('php://memory', 'w+b');
        fwrite($stream, $contents);
        rewind($stream);
        return $stream;
    }
}
