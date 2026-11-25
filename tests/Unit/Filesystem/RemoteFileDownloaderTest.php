<?php

declare(strict_types=1);

use CraftCms\Cms\Filesystem\RemoteFileDownloader;
use CraftCms\UrlValidator\UrlValidator;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->path = tempnam(sys_get_temp_dir(), 'remote-file-');
    $this->privateResolver = static fn (string $host): array => ['10.0.0.5'];

    Http::fake(['http://api.internal/file.txt' => Http::response('Internal content')]);
});

afterEach(function () {
    @unlink($this->path);
});

test('download returns the response when the validator allows the private host', function () {
    $downloader = new RemoteFileDownloader(new UrlValidator($this->privateResolver, ['allowedPrivateHosts' => ['api.internal']]));

    $response = $downloader->download('http://api.internal/file.txt', $this->path, 1024);

    expect($response->successful())->toBeTrue()
        ->and(file_get_contents($this->path))->toBe('Internal content');
});

test('download rejects private addresses the validator does not allow', function () {
    $downloader = new RemoteFileDownloader(new UrlValidator($this->privateResolver));

    expect(fn () => $downloader->download('http://api.internal/file.txt', $this->path, 1024))
        ->toThrow(RuntimeException::class, 'The file URL must resolve to a public HTTP or HTTPS address.');
});
