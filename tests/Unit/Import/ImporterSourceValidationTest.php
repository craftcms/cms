<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Tests\Support\Import\TestImporter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->originalRoot = Aliases::get('@root');
    $this->packageRoot = dirname(__DIR__, 3);
    Aliases::set('@root', $this->packageRoot);

    $this->importer = new TestImporter;

    $this->importer::$resolvedIps = ['93.184.216.34'];
    $this->importer::$lookups = 0;

    $this->sourceError = function (?string $value): ?string {
        $error = null;

        $this->importer::validateSource($value, 'source', function (string $attribute, ?string $message = null) use (&$error) {
            $error = $message ?? $attribute;
        }, Validator::make([], []));

        return $error;
    };
});

afterEach(function () {
    Aliases::set('@root', $this->originalRoot);
});

it('accepts a valid local file', function (string $source) {
    expect(($this->sourceError)($source))->toBeNull()
        ->and($this->importer::isSourceValid($source))->toBeTrue();
})->with([
    'tests/Fixtures/Import/entries.csv',
    'tests/Fixtures/Import/entries-plain-text.json',
    'tests/Fixtures/Import/entries.xml',
    '@root/tests/Fixtures/Import/entries.csv',
]);

it('rejects an invalid local file', function (?string $source, string $message) {
    expect(($this->sourceError)($source))->toContain($message)
        ->and($this->importer::isSourceValid($source))->toBeFalse();
})->with([
    'empty' => [null, 'Source must be provided.'],
    'absolute path' => ['/etc/passwd', 'File paths must be relative to the project root or start with an alias.'],
    'missing file' => ['tests/Fixtures/Import/missing.csv', 'does not exist.'],
    'directory' => ['tests/Fixtures/Import', 'does not exist.'],
    'dotfile' => ['tests/Fixtures/Import/.hidden.csv', 'is not permitted.'],
    'path traversal' => ['../../../../../../../../../../etc/passwd', 'is not permitted.'],
    'unsupported type' => ['tests/Fixtures/Import/unsupported.txt', 'Only files with these MIME types are allowed'],
    'contents not matching the extension' => ['tests/Fixtures/Import/json-content.csv', 'don’t match its type (csv).'],
    'stream wrapper' => ['data:text/plain,a', 'Access to this file (data:text/plain,a) is not permitted.'],
]);

it('rejects a URL with a scheme other than http or https', function (string $url) {
    expect(($this->sourceError)($url))->toBe("URL “{$url}” is not permitted.")
        ->and($this->importer::isSourceValid($url))->toBeFalse();
})->with([
    'file:///etc/passwd',
    'php://filter/resource=/etc/passwd',
    'glob://*.csv',
]);

it('rejects a disallowed hostname without resolving it', function (string $url) {
    expect(($this->sourceError)($url))->toBe("URL “{$url}” is not permitted.")
        ->and($this->importer::$lookups)->toBe(0);
})->with([
    'https://169.254.169.254/data.json',
    'http://metadata.google.internal/data.json',
]);

it('accepts a URL that resolves to a public IP', function () {
    expect(($this->sourceError)('https://example.com/data.json'))->toBeNull()
        ->and($this->importer::$lookups)->toBe(1);
});

it('accepts a URL with a supported extension or no extension', function (string $url) {
    expect(($this->sourceError)($url))->toBeNull()
        ->and($this->importer::isSourceValid($url))->toBeTrue();
})->with([
    'https://example.com/data.json?token=x',
    'https://example.com/api/entries',
]);

it('rejects a URL with an unsupported extension', function () {
    expect(($this->sourceError)('https://example.com/data.txt'))->toContain('Only files with these MIME types are allowed')
        ->and($this->importer::isSourceValid('https://example.com/data.txt'))->toBeFalse();
});

it('rejects a URL that resolves to a disallowed IP', function (array $ips) {
    $this->importer::$resolvedIps = $ips;

    expect(($this->sourceError)('https://example.com/data.json'))->toBe('URL “https://example.com/data.json” is not permitted.');
})->with([
    'loopback' => [['127.0.0.1']],
    'cloud metadata' => [['169.254.169.254']],
    'unresolvable' => [[]],
]);

it('checks a URL without resolving it in isSourceValid()', function () {
    $this->importer::$resolvedIps = ['127.0.0.1'];

    expect($this->importer::isSourceValid('https://example.com/data.json'))->toBeTrue()
        ->and($this->importer::$lookups)->toBe(0)
        ->and(($this->sourceError)('https://example.com/data.json'))->not->toBeNull();
});

it('throws for an unknown alias', function () {
    ($this->sourceError)('@nope/data.csv');
})->throws(InvalidArgumentException::class);

it('resolves relative paths against @root and leaves URLs and aliases as they are', function () {
    expect(BaseImporter::resolvedSourcePath('tests/Fixtures/Import/entries.csv'))->toBe("{$this->packageRoot}/tests/Fixtures/Import/entries.csv")
        ->and(BaseImporter::resolvedSourcePath('@root/data.csv'))->toBe("{$this->packageRoot}/data.csv")
        ->and(BaseImporter::resolvedSourcePath('https://example.com/data.json'))->toBe('https://example.com/data.json')
        ->and(BaseImporter::resolvedSourcePath(null))->toBeNull();
});

it('stores the source as given and leaves checking it to validate()', function () {
    expect($this->importer->source('https://example.com/data.json')->source)->toBe('https://example.com/data.json')
        ->and($this->importer->source('tests/Fixtures/Import/missing.csv')->source)->toBe('tests/Fixtures/Import/missing.csv')
        ->and(fn () => $this->importer->validate())->toThrow(ValidationException::class, 'does not exist.');
});
