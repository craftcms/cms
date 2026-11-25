<?php

namespace crafttests\unit\services\uploads;

use craft\base\TemporaryUploadUrlInterface;
use craft\fs\Local;
use DateTimeInterface;

/**
 * A local filesystem that pretends to accept direct uploads, as S3 would. Its URLs are the file paths under
 * `https://storage.test/`, so tests can “send” a file by writing it to the path.
 */
class DirectUploadFs extends Local implements TemporaryUploadUrlInterface
{
    public static bool $enabled = true;
    public static array $calls = [];

    public function temporaryUploadUrl(string $path, DateTimeInterface $expiresAt, array $options = []): ?array
    {
        if (!self::$enabled) {
            return null;
        }

        self::$calls[] = [$path, $options];

        return ['url' => "https://storage.test/$path", 'headers' => ['x-amz-meta-visibility' => 'private']];
    }
}
