<?php

declare(strict_types=1);

use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

beforeEach(function () {
    $this->root = storage_path('framework/testing/uploaded-file-'.uniqid());
    File::ensureDirectoryExists($this->root);
});

afterEach(function () {
    File::deleteDirectory($this->root);
});

it('does not keep a partial local copy after a failed read', function () {
    $contents = random_bytes(10_000);
    file_put_contents("$this->root/source.bin", $contents);

    $adapter = new class("$this->root") extends LocalFilesystemAdapter
    {
        public int $truncatedReads = 1;

        public function readStream(string $path)
        {
            $source = parent::readStream($path);

            if ($this->truncatedReads-- <= 0) {
                return $source;
            }

            $partial = fopen('php://temp', 'w+b');
            stream_copy_to_stream($source, $partial, 5_000);
            fclose($source);
            rewind($partial);

            return $partial;
        }
    };
    $disk = new FilesystemAdapter(new Filesystem($adapter), $adapter, ['root' => $this->root, 'throw' => true]);
    $filename = 'cms-uploaded-file-'.uniqid().'.bin';
    $file = new UploadedFile($disk, 'source.bin', $filename);
    $tempCopies = fn () => glob(Path::temp(pathinfo($filename, PATHINFO_FILENAME).'*'));

    expect(fn () => $file->localPath())->toThrow(RuntimeException::class, 'Unable to read the complete uploaded file.')
        ->and($file->isLocal())->toBeFalse()
        ->and($tempCopies())->toBe([]);

    $path = $file->localPath();
    $destination = Storage::build(['driver' => 'local', 'root' => "$this->root/destination", 'throw' => true]);
    $file->storeAs($destination, 'stored.bin', 'application/octet-stream', $path);

    expect(file_get_contents($path))->toBe($contents)
        ->and($destination->get('stored.bin'))->toBe($contents);

    $file->release();
});
