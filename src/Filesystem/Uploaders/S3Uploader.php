<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Uploaders;

use CraftCms\Cms\Filesystem\Contracts\Uploader;
use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\Filesystem\Data\UploadPartRequest;
use CraftCms\Cms\Filesystem\Filesystems;
use CraftCms\Cms\Filesystem\Models\UploadSession;
use Illuminate\Filesystem\AwsS3V3Adapter;
use InvalidArgumentException;
use Throwable;

class S3Uploader implements Uploader
{
    public function __construct(private readonly Filesystems $filesystems) {}

    public function start(UploadSession $session): void
    {
        $session->chunkSize = max(8388608, (int) ceil($session->size / 10000));
        abort_if($session->chunkSize > 5368709120, 422, 'The file exceeds the S3 multipart upload limit.');

        $options = $this->disk($session)->getConfig()['options'] ?? [];
        $encryption = array_intersect_key($options, array_flip([
            'ServerSideEncryption', 'SSEKMSKeyId', 'BucketKeyEnabled', 'StorageClass',
        ]));
        $result = $this->client($session)->createMultipartUpload([
            ...$encryption,
            ...$this->location($session),
            'ContentType' => 'application/octet-stream',
        ]);

        $session->state = ['uploadId' => (string) $result['UploadId']];
    }

    public function partRequest(UploadSession $session, int $part): UploadPartRequest
    {
        $command = $this->client($session)->getCommand('UploadPart', [
            ...$this->location($session),
            'UploadId' => $session->state['uploadId'],
            'PartNumber' => $part,
            'ContentLength' => $session->partSize($part),
        ]);
        $request = $this->client($session)->createPresignedRequest($command, '+15 minutes');
        $headers = [];

        foreach ($request->getHeaders() as $name => $values) {
            if (! in_array(strtolower($name), ['host', 'content-length'], true)) {
                $headers[$name] = implode(', ', $values);
            }
        }

        return new UploadPartRequest((string) $request->getUri(), 'PUT', $headers);
    }

    public function complete(UploadSession $session): UploadedFile
    {
        $disk = $this->disk($session);

        if (! $disk->exists($session->path())) {
            $uploadedParts = [];
            $marker = null;

            do {
                $result = $this->client($session)->listParts([
                    ...$this->location($session),
                    'UploadId' => $session->state['uploadId'],
                    ...($marker === null ? [] : ['PartNumberMarker' => $marker]),
                ]);

                foreach ($result['Parts'] ?? [] as $part) {
                    $number = (int) $part['PartNumber'];
                    abort_unless((int) $part['Size'] === $session->partSize($number), 422, 'An S3 upload part has an incorrect size.');
                    $uploadedParts[] = ['PartNumber' => $number, 'ETag' => $part['ETag']];
                }

                $marker = $result['NextPartNumberMarker'] ?? null;
            } while ($result['IsTruncated'] ?? false);

            abort_unless(count($uploadedParts) === $session->partCount(), 422, 'The S3 upload is missing a part.');

            foreach ($uploadedParts as $index => $part) {
                abort_unless($part['PartNumber'] === $index + 1, 422, 'The S3 upload is missing a part.');
            }

            $this->client($session)->completeMultipartUpload([
                ...$this->location($session),
                'UploadId' => $session->state['uploadId'],
                'MultipartUpload' => ['Parts' => $uploadedParts],
            ]);
        }

        return new UploadedFile($disk, $session->path(), $session->filename);
    }

    public function abort(UploadSession $session): void
    {
        if (isset($session->state['uploadId'])) {
            try {
                $this->client($session)->abortMultipartUpload([
                    ...$this->location($session),
                    'UploadId' => $session->state['uploadId'],
                ]);
            } catch (Throwable $exception) {
                if (! method_exists($exception, 'getAwsErrorCode') || $exception->getAwsErrorCode() !== 'NoSuchUpload') {
                    throw $exception;
                }
            }
        }

        if (! $this->disk($session)->deleteDirectory($session->prefix())) {
            throw new \RuntimeException('Unable to remove the temporary S3 upload.');
        }
    }

    /** @return array{Bucket: string, Key: string} */
    private function location(UploadSession $session): array
    {
        $disk = $this->disk($session);

        return ['Bucket' => $disk->getConfig()['bucket'], 'Key' => $disk->path($session->path())];
    }

    private function disk(UploadSession $session): AwsS3V3Adapter
    {
        $disk = $this->filesystems->disk($session->disk);

        if (! $disk instanceof AwsS3V3Adapter) {
            throw new InvalidArgumentException('The S3 uploader requires a Laravel S3 disk and league/flysystem-aws-s3-v3.');
        }

        return $disk;
    }

    /** The SDK is an optional dependency supplied by the S3 filesystem adapter. */
    private function client(UploadSession $session): mixed
    {
        return $this->disk($session)->getClient();
    }
}
