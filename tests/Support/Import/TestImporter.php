<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support\Import;

use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\UrlValidator\UrlValidator;
use Override;
use stdClass;

/**
 * A minimal importer whose URL validator resolves hostnames to `$resolvedIps` instead of using DNS.
 */
class TestImporter extends BaseImporter
{
    /**
     * @var string[] The IPs every hostname resolves to.
     */
    public static array $resolvedIps = [];

    /**
     * @var int The number of hostname lookups made.
     */
    public static int $lookups = 0;

    #[Override]
    public static function targetClass(): string
    {
        return stdClass::class;
    }

    #[Override]
    public static function create(): self
    {
        return new self;
    }

    #[Override]
    public static function displayName(): string
    {
        return 'Test';
    }

    #[Override]
    public function settingsUi(): array
    {
        return [];
    }

    #[Override]
    public function refreshSettingsUi(array $settings): void {}

    #[Override]
    public function storeSettings(array $settings): void {}

    #[Override]
    public function getSettings(): array
    {
        return [];
    }

    #[Override]
    public static function getDefaultTransformer(): ?string
    {
        return null;
    }

    #[Override]
    public static function urlValidator(?callable $resolver = null): UrlValidator
    {
        return parent::urlValidator(function (string $host): array {
            self::$lookups++;

            return self::$resolvedIps;
        });
    }
}
