<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\UrlValidator\UrlValidator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->originalRoot = Aliases::get('@root');
    $this->packageRoot = dirname(__DIR__, 3);
    Aliases::set('@root', $this->packageRoot);

    $this->importer = new class extends BaseImporter
    {
        public static array $resolvedIps = [];

        public static int $lookups = 0;

        public static function targetClass(): string
        {
            return stdClass::class;
        }

        public static function create(): self
        {
            return new self;
        }

        public static function displayName(): string
        {
            return 'Test';
        }

        public function settingsForm(FormContext $context): array
        {
            return [];
        }

        public function refreshSettingsForm(array $settings): void {}

        public function storeSettings(array $settings): void {}

        public function getSettings(): array
        {
            return [];
        }

        public static function getDefaultTransformer(): ?string
        {
            return null;
        }

        protected static function urlValidator(?callable $resolver = null): UrlValidator
        {
            return parent::urlValidator(function (string $host): array {
                self::$lookups++;

                return self::$resolvedIps;
            });
        }
    };

    $this->importer::$resolvedIps = ['93.184.216.34'];
    $this->importer::$lookups = 0;

    $this->fileError = function (?string $value): ?string {
        $error = null;

        $this->importer::validateFile($value, 'file', function (string $attribute, ?string $message = null) use (&$error) {
            $error = $message ?? $attribute;
        }, Validator::make([], []));

        return $error;
    };
});

afterEach(function () {
    Aliases::set('@root', $this->originalRoot);
});

it('accepts a valid local file', function (string $file) {
    expect(($this->fileError)($file))->toBeNull()
        ->and($this->importer::isFileValid($file))->toBeTrue();
})->with([
    'tests/Fixtures/Import/entries.csv',
    'tests/Fixtures/Import/entries-plain-text.json',
    'tests/Fixtures/Import/entries.xml',
    '@root/tests/Fixtures/Import/entries.csv',
]);

it('rejects an invalid local file', function (?string $file, string $message) {
    expect(($this->fileError)($file))->toContain($message)
        ->and($this->importer::isFileValid($file))->toBeFalse();
})->with([
    'empty' => [null, 'File must be provided.'],
    'absolute path' => ['/etc/passwd', 'File paths must be relative to the project root or start with an alias.'],
    'missing file' => ['tests/Fixtures/Import/missing.csv', 'does not exist.'],
    'directory' => ['tests/Fixtures/Import', 'does not exist.'],
    'dotfile' => ['tests/Fixtures/Import/.hidden.csv', 'is not permitted.'],
    'path traversal' => ['../../../../../../../../../../etc/passwd', 'is not permitted.'],
    'unsupported type' => ['tests/Fixtures/Import/unsupported.txt', 'Only files with these MIME types are allowed'],
    'stream wrapper' => ['data:text/plain,a', 'Access to this file (data:text/plain,a) is not permitted.'],
]);

it('rejects a URL with a scheme other than http or https', function (string $url) {
    expect(($this->fileError)($url))->toBe("URL “{$url}” is not permitted.")
        ->and($this->importer::isFileValid($url))->toBeFalse();
})->with([
    'file:///etc/passwd',
    'php://filter/resource=/etc/passwd',
    'glob://*.csv',
]);

it('rejects a disallowed hostname without resolving it', function (string $url) {
    expect(($this->fileError)($url))->toBe("URL “{$url}” is not permitted.")
        ->and($this->importer::$lookups)->toBe(0);
})->with([
    'https://169.254.169.254/data.json',
    'http://metadata.google.internal/data.json',
]);

it('accepts a URL that resolves to a public IP', function () {
    expect(($this->fileError)('https://example.com/data.json'))->toBeNull()
        ->and($this->importer::$lookups)->toBe(1);
});

it('rejects a URL that resolves to a disallowed IP', function (array $ips) {
    $this->importer::$resolvedIps = $ips;

    expect(($this->fileError)('https://example.com/data.json'))->toBe('URL “https://example.com/data.json” is not permitted.');
})->with([
    'loopback' => [['127.0.0.1']],
    'cloud metadata' => [['169.254.169.254']],
    'unresolvable' => [[]],
]);

it('checks a URL without resolving it in isFileValid()', function () {
    $this->importer::$resolvedIps = ['127.0.0.1'];

    expect($this->importer::isFileValid('https://example.com/data.json'))->toBeTrue()
        ->and($this->importer::$lookups)->toBe(0)
        ->and(($this->fileError)('https://example.com/data.json'))->not->toBeNull();
});

it('throws for an unknown alias', function () {
    ($this->fileError)('@nope/data.csv');
})->throws(InvalidArgumentException::class);

it('resolves relative paths against @root and leaves URLs and aliases as they are', function () {
    expect(BaseImporter::resolvedFilePath('tests/Fixtures/Import/entries.csv'))->toBe("{$this->packageRoot}/tests/Fixtures/Import/entries.csv")
        ->and(BaseImporter::resolvedFilePath('@root/data.csv'))->toBe("{$this->packageRoot}/data.csv")
        ->and(BaseImporter::resolvedFilePath('https://example.com/data.json'))->toBe('https://example.com/data.json')
        ->and(BaseImporter::resolvedFilePath(null))->toBeNull();
});

it('stores the file as given and leaves checking it to validate()', function () {
    expect($this->importer->file('https://example.com/data.json')->file)->toBe('https://example.com/data.json')
        ->and($this->importer->file('tests/Fixtures/Import/missing.csv')->file)->toBe('tests/Fixtures/Import/missing.csv')
        ->and(fn () => $this->importer->validate())->toThrow(ValidationException::class, 'does not exist.');
});
