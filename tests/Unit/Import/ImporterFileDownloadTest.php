<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Tests\Support\Import\TestImporter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->originalRoot = Aliases::get('@root');
    $this->packageRoot = dirname(__DIR__, 3);
    Aliases::set('@root', $this->packageRoot);

    $this->importer = new TestImporter;
    $this->importer::$resolvedIps = ['93.184.216.34'];
    $this->importer::$lookups = 0;

    $this->directory = Path::temp('import-download-test-'.uniqid());
});

afterEach(function () {
    Aliases::set('@root', $this->originalRoot);
    File::deleteDirectory($this->directory);
});

it('downloads a remote file with the extension of its content type', function () {
    Http::fake(['example.com/*' => Http::response('[{"title":"first"}]', 200, ['Content-Type' => 'application/json; charset=utf-8'])]);

    $filePath = $this->importer->source('https://example.com/api/entries')->downloadFile($this->directory);

    expect($filePath)->toStartWith($this->directory)
        ->and($filePath)->toEndWith('.json')
        ->and(file_get_contents($filePath))->toBe('[{"title":"first"}]');
});

it('falls back to the URL’s extension for a generic content type', function () {
    Http::fake(['example.com/*' => Http::response("title,body\nfirst,one\nsecond,two\n", 200, ['Content-Type' => 'text/plain'])]);

    expect($this->importer->source('https://example.com/data.csv')->downloadFile($this->directory))->toEndWith('.csv');
});

it('rejects a remote file whose type can’t be determined', function () {
    Http::fake(['example.com/*' => Http::response('<html><body>Hi</body></html>', 200, ['Content-Type' => 'text/html'])]);

    expect(fn () => $this->importer->source('https://example.com/export')->downloadFile($this->directory))
        ->toThrow(InvalidArgumentException::class, 'Unable to determine the type of the file at “https://example.com/export”.')
        ->and(File::files($this->directory))->toBeEmpty();
});

it('rejects a remote file whose contents don’t match its type', function () {
    Http::fake(['example.com/*' => Http::response('<!DOCTYPE html><html><body>Hi</body></html>', 200, ['Content-Type' => 'application/json'])]);

    expect(fn () => $this->importer->source('https://example.com/data.json')->downloadFile($this->directory))
        ->toThrow(InvalidArgumentException::class, 'don’t match its type (json)')
        ->and(File::files($this->directory))->toBeEmpty();
});

it('rejects a failed response', function () {
    Http::fake(['example.com/*' => Http::response('not found', 404)]);

    expect(fn () => $this->importer->source('https://example.com/data.json')->downloadFile($this->directory))
        ->toThrow(InvalidArgumentException::class, 'Unable to download the file from “https://example.com/data.json”.')
        ->and(File::files($this->directory))->toBeEmpty();
});

it('validates the URL before downloading it', function () {
    Http::fake();
    $this->importer::$resolvedIps = ['127.0.0.1'];

    expect(fn () => $this->importer->source('https://example.com/data.json')->downloadFile($this->directory))
        ->toThrow(InvalidArgumentException::class)
        ->and($this->importer::$lookups)->toBe(1);

    Http::assertNothingSent();
});

it('deletes a downloaded file once the callback returns', function () {
    Http::fake(['example.com/*' => Http::response('[{"title":"first"}]', 200, ['Content-Type' => 'application/json'])]);

    $filePath = $this->importer->source('https://example.com/data.json')->withLocalFile(function (string $filePath) {
        expect(file_get_contents($filePath))->toBe('[{"title":"first"}]');

        return $filePath;
    });

    expect(file_exists($filePath))->toBeFalse();
});

it('passes a local file to the callback as it is', function () {
    Http::fake();

    $filePath = $this->importer->source('tests/Fixtures/Import/entries.csv')->withLocalFile(fn (string $filePath) => $filePath);

    expect($filePath)->toBe("{$this->packageRoot}/tests/Fixtures/Import/entries.csv")
        ->and(file_exists($filePath))->toBeTrue()
        ->and($this->importer::isRemoteSource($this->importer->source))->toBeFalse();

    Http::assertNothingSent();
});
