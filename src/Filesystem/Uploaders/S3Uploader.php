<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Uploaders;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use CraftCms\Cms\Filesystem\Contracts\Uploader;
use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\Filesystem\Data\UploadSetup;
use CraftCms\Cms\Filesystem\Filesystems;
use CraftCms\Cms\Filesystem\Models\UploadSession;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class S3Uploader implements Uploader
{
    public function __construct(private readonly Filesystems $filesystems) {}

    public function start(UploadSession $session): UploadSetup
    {
        // Match the default chunk sizing in @uppy/aws-s3.
        $chunkSize = max(5242880, (int) ceil($session->size / 10000));
        abort_if($chunkSize > 5368709120, 422, 'The file exceeds the S3 multipart upload limit.');

        $options = $this->disk($session)->getConfig()['options'] ?? [];
        $encryption = array_intersect_key($options, array_flip([
            'ServerSideEncryption', 'SSEKMSKeyId', 'BucketKeyEnabled', 'StorageClass',
        ]));
        $result = $this->client($session)->createMultipartUpload([
            ...$encryption,
            ...$this->location($session),
            'ContentType' => 'application/octet-stream',
        ]);

        $uploadId = (string) $result['UploadId'];

        return new UploadSetup(
            chunkSize: $chunkSize,
            state: ['uploadId' => $uploadId],
            transport: ['type' => 's3', 'options' => ['uploadId' => $uploadId, 'key' => $session->path()]],
        );
    }

    public function handleRequest(Request $request, UploadSession $session): JsonResponse
    {
        abort_unless($request->isMethod('POST') || $request->isMethod('DELETE'), 405);
        $data = $request->validate([
            'method' => ['required', Rule::in($request->isMethod('DELETE') ? ['DELETE'] : ['GET', 'PUT', 'POST'])],
            'key' => ['required', 'string'],
            'uploadId' => ['required', 'string'],
            'partNumber' => ['required_if:method,PUT', 'integer', 'min:1'],
        ]);

        abort_if($session->result !== null, 409, 'This upload has already completed.');
        abort_unless($data['key'] === $session->path() && $data['uploadId'] === $session->state['uploadId'], 404);

        return new JsonResponse($this->sign(
            $session,
            $data['method'],
            isset($data['partNumber']) ? (int) $data['partNumber'] : null,
        ));
    }

    /** @return array{url: string} */
    public function sign(UploadSession $session, string $method, ?int $part = null): array
    {
        $operation = match ($method) {
            'GET' => 'ListParts',
            'PUT' => 'UploadPart',
            'POST' => 'CompleteMultipartUpload',
            'DELETE' => 'AbortMultipartUpload',
            default => throw new InvalidArgumentException("Unsupported S3 upload method: $method."),
        };
        $parameters = match ($method) {
            'PUT' => ['PartNumber' => $part, 'ContentLength' => $session->partSize($part)],
            'POST' => ['MultipartUpload' => ['Parts' => $this->completedParts($session)]],
            default => [],
        };
        $command = $this->client($session)->getCommand($operation, [
            ...$this->location($session),
            'UploadId' => $session->state['uploadId'],
            ...$parameters,
        ]);
        $request = $this->client($session)->createPresignedRequest($command, '+15 minutes');

        return ['url' => (string) $request->getUri()];
    }

    public function uploaded(UploadSession $session): bool
    {
        $disk = $this->disk($session);

        if (! $disk->exists($session->path())) {
            return false;
        }

        abort_unless($disk->size($session->path()) === $session->size, 422, 'The uploaded file has an incorrect size.');

        return true;
    }

    /** @return list<array{PartNumber: int, ETag: string}> */
    private function completedParts(UploadSession $session): array
    {
        $parts = [];
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
                $parts[] = ['PartNumber' => $number, 'ETag' => (string) $part['ETag']];
            }

            $marker = $result['NextPartNumberMarker'] ?? null;
        } while ($result['IsTruncated'] ?? false);

        abort_unless(count($parts) === $session->partCount(), 422, 'The S3 upload is missing a part.');

        foreach ($parts as $index => $part) {
            abort_unless($part['PartNumber'] === $index + 1, 422, 'The S3 upload is missing a part.');
        }

        return $parts;
    }

    public function complete(UploadSession $session): UploadedFile
    {
        $disk = $this->disk($session);

        if (! $disk->exists($session->path())) {
            $this->client($session)->completeMultipartUpload([
                ...$this->location($session),
                'UploadId' => $session->state['uploadId'],
                'MultipartUpload' => ['Parts' => $this->completedParts($session)],
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
            } catch (S3Exception $exception) {
                if ($exception->getAwsErrorCode() !== 'NoSuchUpload') {
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

        return [
            'Bucket' => $disk->getConfig()['bucket'],
            'Key' => $disk->path($session->path()),
        ];
    }

    private function disk(UploadSession $session): AwsS3V3Adapter
    {
        $disk = $this->filesystems->disk($session->disk);

        if (! $disk instanceof AwsS3V3Adapter) {
            throw new InvalidArgumentException('The S3 uploader requires a Laravel S3 disk and league/flysystem-aws-s3-v3.');
        }

        return $disk;
    }

    private function client(UploadSession $session): S3Client
    {
        return $this->disk($session)->getClient();
    }
}
