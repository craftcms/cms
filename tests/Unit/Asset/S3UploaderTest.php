<?php

declare(strict_types=1);

use Aws\CommandInterface;
use Aws\Result;
use Aws\S3\S3Client;
use CraftCms\Cms\Asset\Data\UploadedAssetFile;
use CraftCms\Cms\Asset\Models\UploadSession;
use CraftCms\Cms\Asset\Uploaders\S3Uploader;
use CraftCms\Cms\Filesystem\Filesystems;
use GuzzleHttp\Promise\Create;
use Illuminate\Filesystem\AwsS3V3Adapter;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter as S3Adapter;
use League\Flysystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    if (! class_exists(S3Client::class)) {
        $this->markTestSkipped('The optional S3 adapter and AWS SDK are not installed.');
    }

    $this->commands = [];
    $this->client = new S3Client([
        'version' => 'latest', 'region' => 'us-east-1',
        'credentials' => ['key' => 'test', 'secret' => 'test'],
        'handler' => function (CommandInterface $command) {
            $this->commands[] = $command;

            return Create::promiseFor(new Result(match ($command->getName()) {
                'CreateMultipartUpload' => ['UploadId' => 'multipart-id'],
                'ListParts' => ['Parts' => [
                    ['PartNumber' => 1, 'Size' => 8388608, 'ETag' => 'first'],
                    ['PartNumber' => 2, 'Size' => 3, 'ETag' => 'second'],
                ]],
                'HeadObject' => ['ContentLength' => 5368709121],
                'UploadPartCopy' => ['CopyPartResult' => ['ETag' => 'copied']],
                default => [],
            }));
        },
    ]);
    $this->disk = Mockery::mock(AwsS3V3Adapter::class);
    $this->disk->shouldReceive('getClient')->andReturn($this->client);
    $this->disk->shouldReceive('getConfig')->andReturn(['bucket' => 'upload-bucket', 'region' => 'us-east-1']);
    $this->disk->shouldReceive('path')->andReturnUsing(fn (string $path) => 'prefix/'.$path);
    $filesystems = Mockery::mock(Filesystems::class);
    $filesystems->shouldReceive('disk')->with('disk:uploads')->andReturn($this->disk);
    app()->instance(Filesystems::class, $filesystems);
});

it('signs direct multipart requests and completes using storage-verified parts', function () {
    $session = new UploadSession(['id' => 'session', 'disk' => 'disk:uploads', 'filename' => 'movie.mp4', 'size' => 8388611]);
    $uploader = app(S3Uploader::class);
    $uploader->start($session);
    $request = $uploader->partRequest($session, 2);

    expect($request->method)->toBe('PUT')
        ->and($request->url)->toContain('uploadId=multipart-id', 'partNumber=2', 'X-Amz-Signature=')
        ->and($session->chunkSize)->toBe(8388608);

    $this->disk->shouldReceive('exists')->once()->with($session->path())->andReturnFalse();
    $file = $uploader->complete($session);
    $completion = array_last($this->commands);
    expect($file->path)->toBe($session->path())
        ->and($completion->getName())->toBe('CompleteMultipartUpload')
        ->and($completion['MultipartUpload']['Parts'])->toBe([
            ['PartNumber' => 1, 'ETag' => 'first'], ['PartNumber' => 2, 'ETag' => 'second'],
        ]);
});

it('rejects storage parts that do not match the declared size', function () {
    $session = new UploadSession(['id' => 'session', 'disk' => 'disk:uploads', 'filename' => 'movie.mp4', 'size' => 8388612]);
    $uploader = app(S3Uploader::class);
    $uploader->start($session);
    $this->disk->shouldReceive('exists')->andReturnFalse();

    expect(fn () => $uploader->complete($session))->toThrow(HttpException::class, 'incorrect size');
    expect(array_map(fn ($command) => $command->getName(), $this->commands))->not->toContain('CompleteMultipartUpload');
});

it('uses multipart storage copies for objects larger than five gib', function () {
    $this->disk->shouldReceive('getDriver')->andReturn(new Filesystem(
        new S3Adapter($this->client, 'upload-bucket', 'prefix'),
    ));
    $file = new UploadedAssetFile($this->disk, 'staged/file', 'archive.zip');
    $file->storeAs($this->disk, 'assets/archive.zip', 'application/zip');

    $names = array_map(fn ($command) => $command->getName(), $this->commands);
    expect($names)->toContain('CreateMultipartUpload', 'UploadPartCopy', 'CompleteMultipartUpload')
        ->not->toContain('GetObject', 'PutObject');
    expect(count(array_filter($names, fn ($name) => $name === 'UploadPartCopy')))->toBeGreaterThan(1);
});

it('stores processed bytes when sanitization changed the local file', function () {
    $stream = fopen('php://temp', 'w+b');
    fwrite($stream, 'original');
    rewind($stream);
    $this->disk->shouldReceive('readStream')->once()->with('staged/file')->andReturn($stream);
    $this->disk->shouldReceive('size')->once()->with('staged/file')->andReturn(8);
    $file = new UploadedAssetFile($this->disk, 'staged/file', 'image.svg');

    try {
        $path = $file->localPath();
        file_put_contents($path, 'sanitized');
        $this->disk->shouldReceive('writeStream')->once()->withArgs(fn ($destination, $bytes, $options) => $destination === 'assets/image.svg'
            && stream_get_contents($bytes) === 'sanitized'
            && $options['mimetype'] === 'image/svg+xml')->andReturnTrue();

        $file->storeAs($this->disk, 'assets/image.svg', 'image/svg+xml', $path);
        expect($this->commands)->toBe([]);
    } finally {
        $file->release();
    }
});

it('aborts multipart storage and removes the staging prefix', function () {
    $session = new UploadSession(['id' => 'session', 'disk' => 'disk:uploads', 'state' => ['uploadId' => 'multipart-id']]);
    $this->disk->shouldReceive('deleteDirectory')->once()->with('upload-sessions/session')->andReturnTrue();
    app(S3Uploader::class)->abort($session);

    expect($this->commands[0]->getName())->toBe('AbortMultipartUpload')
        ->and($this->commands[0]['Key'])->toBe('prefix/upload-sessions/session/file')
        ->and($this->commands[0]['UploadId'])->toBe('multipart-id');
});
