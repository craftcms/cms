<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Translation\I18N as I18NService;
use CraftCms\Cms\Translation\Locale;
use Illuminate\Support\Collection;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\MessageReaderInterface;

test('normalize language', function (string $expected, string $language) {
    expect(I18N::normalizeLanguage($language))->toBe($expected);
})->with([
    ['nl', 'nl'],
    ['en-US', 'en-US'],
    ['af', 'af'],
    ['af-NA', 'af-NA'],
    ['en-AG', 'en-ag'],
    ['en-AG', 'EN-AG'],
]);

test('normalization exception', function (string $language) {
    $this->expectException(InvalidArgumentException::class);

    I18N::normalizeLanguage($language);
})->with([
    'dutch',
    'notalang',
]);

test('normalize number', function (mixed $expected, mixed $number, ?string $localeId) {
    expect(I18N::normalizeNumber($number, $localeId))->toBe($expected);
})->with([
    ['2000000000', '20,0000,0000', null],
    ['20 0000 0000', '20 0000 0000', null],
    ['20.0000.0000', '20.0000.0000', null],
    [2000000000, 2000000000, null],
]);

test('getAllLocaleIds returns all ICU locales with hyphens', function () {
    $localeIds = I18N::getAllLocaleIds();

    expect($localeIds)->toBeInstanceOf(Collection::class)
        ->and($localeIds)->toContain('en-US')
        ->and($localeIds)->toContain('nl')
        ->and($localeIds->filter(fn (string $id) => str_contains($id, '_')))->toBeEmpty();
});

test('getAllLocaleIds includes custom locale aliases', function () {
    Cms::config()->localeAliases([
        'smj' => [
            'aliasOf' => 'sv',
            'displayName' => 'Lule Sámi',
        ],
    ]);

    app()->forgetInstance(I18NService::class);

    $localeIds = I18N::getAllLocaleIds();

    expect($localeIds)->toContain('smj');
});

test('getAllLocaleIds is sorted when aliases are present', function () {
    Cms::config()->localeAliases([
        'aaa-test' => [
            'aliasOf' => 'en',
            'displayName' => 'Test Locale',
        ],
    ]);

    app()->forgetInstance(I18NService::class);

    $localeIds = I18N::getAllLocaleIds()->values()->all();

    expect($localeIds)->toBe(collect($localeIds)->sort()->values()->all());
});

test('getLocaleById returns a locale with alias properties', function () {
    Cms::config()->localeAliases([
        'smj' => [
            'aliasOf' => 'sv',
            'displayName' => 'Lule Sámi',
        ],
    ]);

    app()->forgetInstance(I18NService::class);

    $locale = I18N::getLocaleById('smj');

    expect($locale)->toBeInstanceOf(Locale::class)
        ->and($locale->id)->toBe('smj')
        ->and($locale->aliasOf)->toBe('sv')
        ->and($locale->displayName)->toBe('Lule Sámi');
});

test('getLocaleById returns a locale without alias for standard locales', function () {
    $locale = I18N::getLocaleById('en-US');

    expect($locale)->toBeInstanceOf(Locale::class)
        ->and($locale->id)->toBe('en-US')
        ->and($locale->aliasOf)->toBeNull()
        ->and($locale->displayName)->toBeNull();
});

test('getAllLocales returns Locale objects with alias data', function () {
    Cms::config()->localeAliases([
        'smj' => [
            'aliasOf' => 'sv',
            'displayName' => 'Lule Sámi',
        ],
    ]);

    app()->forgetInstance(I18NService::class);

    $locales = I18N::getAllLocales();
    $aliased = $locales->first(fn (Locale $locale) => $locale->id === 'smj');

    expect($aliased)->not->toBeNull()
        ->and($aliased->aliasOf)->toBe('sv')
        ->and($aliased->displayName)->toBe('Lule Sámi');
});

test('nb is always present in all locale IDs', function () {
    app()->forgetInstance(I18NService::class);

    $localeIds = I18N::getAllLocaleIds();

    expect($localeIds)->toContain('nb');
});

test('user-configured locale alias overrides auto-alias', function () {
    Cms::config()->localeAliases([
        'custom-lang' => [
            'aliasOf' => 'en',
            'displayName' => 'Custom Language',
        ],
    ]);

    app()->forgetInstance(I18NService::class);

    $locale = I18N::getLocaleById('custom-lang');

    expect($locale->aliasOf)->toBe('en')
        ->and($locale->displayName)->toBe('Custom Language');
});

test('getAppLocales returns Locale objects with alias data', function () {
    Cms::config()->localeAliases([
        'nb' => [
            'aliasOf' => 'no',
            'displayName' => 'Custom Norwegian',
        ],
    ]);

    app()->forgetInstance(I18NService::class);

    $appLocales = I18N::getAppLocales();
    $nb = $appLocales->first(fn (Locale $locale) => $locale->id === 'nb');

    expect($nb)->not->toBeNull()
        ->and($nb->aliasOf)->toBe('no')
        ->and($nb->displayName)->toBe('Custom Norwegian');
});

test('normalizeLanguage resolves alias locale IDs', function () {
    Cms::config()->localeAliases([
        'smj' => [
            'aliasOf' => 'sv',
            'displayName' => 'Lule Sámi',
        ],
    ]);

    app()->forgetInstance(I18NService::class);

    expect(I18N::normalizeLanguage('smj'))->toBe('smj');
});

test('normalizeLanguage is case-insensitive for alias locale IDs', function () {
    Cms::config()->localeAliases([
        'smj' => [
            'aliasOf' => 'sv',
            'displayName' => 'Lule Sámi',
        ],
    ]);

    app()->forgetInstance(I18NService::class);

    expect(I18N::normalizeLanguage('SMJ'))->toBe('smj');
});

test('getAllLocaleIds caches the result', function () {
    $first = I18N::getAllLocaleIds();
    $second = I18N::getAllLocaleIds();

    expect($first)->toBe($second);
});

test('extraAppLocales are included in app locale IDs', function () {
    Cms::config()->extraAppLocales(['af']);

    app()->forgetInstance(I18NService::class);

    expect(I18N::getAppLocaleIds())->toContain('af');
    expect(I18N::validateAppLocaleId('af'))->toBeTrue();
});

test('defaultCpLanguage is included in app locale IDs', function () {
    Cms::config()->defaultCpLanguage('pt-BR');

    app()->forgetInstance(I18NService::class);

    expect(I18N::getAppLocaleIds())->toContain('pt-BR');
    expect(I18N::validateAppLocaleId('pt-BR'))->toBeTrue();
});

test('getAllTranslationsForLocale returns translations for registered categories', function () {
    $reader = new class implements MessageReaderInterface
    {
        public function getMessage(string $id, string $category, string $locale, array $parameters = []): ?string
        {
            return $this->getMessages($category, $locale)[$id]['message'] ?? null;
        }

        public function getMessages(string $category, string $locale): array
        {
            if ($locale === 'nl') {
                return [
                    'Save' => ['message' => 'Opslaan'],
                    'Cancel' => ['message' => 'Annuleren'],
                ];
            }

            return [];
        }
    };

    $source = new CategorySource('testcat', $reader);
    I18N::addCategorySources($source);

    $translations = I18N::getAllTranslationsForLocale('nl');

    expect($translations)->toHaveKey('testcat')
        ->and($translations['testcat'])->toBe([
            'Save' => 'Opslaan',
            'Cancel' => 'Annuleren',
        ]);
});

test('getAllTranslationsForLocale filters out untranslated messages', function () {
    $reader = new class implements MessageReaderInterface
    {
        public function getMessage(string $id, string $category, string $locale, array $parameters = []): ?string
        {
            return $this->getMessages($category, $locale)[$id]['message'] ?? null;
        }

        public function getMessages(string $category, string $locale): array
        {
            return [
                'Translated' => ['message' => 'Vertaald'],
                'Untranslated' => ['message' => 'Untranslated'],
            ];
        }
    };

    $source = new CategorySource('filtertest', $reader);
    I18N::addCategorySources($source);

    $translations = I18N::getAllTranslationsForLocale('nl');

    expect($translations['filtertest'])->toHaveKey('Translated')
        ->and($translations['filtertest'])->not->toHaveKey('Untranslated');
});

test('getAllTranslationsForLocale excludes categories with no translated messages', function () {
    $reader = new class implements MessageReaderInterface
    {
        public function getMessage(string $id, string $category, string $locale, array $parameters = []): ?string
        {
            return null;
        }

        public function getMessages(string $category, string $locale): array
        {
            return [
                'Same' => ['message' => 'Same'],
            ];
        }
    };

    $source = new CategorySource('emptycat', $reader);
    I18N::addCategorySources($source);

    $translations = I18N::getAllTranslationsForLocale('nl');

    expect($translations)->not->toHaveKey('emptycat');
});

test('getAllTranslationsForLocale handles locale fallback', function () {
    $reader = new class implements MessageReaderInterface
    {
        public function getMessage(string $id, string $category, string $locale, array $parameters = []): ?string
        {
            return $this->getMessages($category, $locale)[$id]['message'] ?? null;
        }

        public function getMessages(string $category, string $locale): array
        {
            if ($locale === 'fr') {
                return [
                    'Hello' => ['message' => 'Bonjour'],
                    'Goodbye' => ['message' => 'Au revoir'],
                ];
            }

            if ($locale === 'fr-CA') {
                return [
                    'Hello' => ['message' => 'Allô'],
                ];
            }

            return [];
        }
    };

    $source = new CategorySource('fallbacktest', $reader);
    I18N::addCategorySources($source);

    $translations = I18N::getAllTranslationsForLocale('fr-CA');

    // fr-CA should override fr for 'Hello', but 'Goodbye' should fall through from fr
    expect($translations['fallbacktest'])->toBe([
        'Hello' => 'Allô',
        'Goodbye' => 'Au revoir',
    ]);
});

test('getAllTranslationsForLocale returns real app translations for Dutch', function () {
    $translations = I18N::getAllTranslationsForLocale('nl');

    expect($translations)->toHaveKey('app')
        ->and($translations['app'])->toHaveKey('(blank)')
        ->and($translations['app']['(blank)'])->toBe('(leeg)');
});

test('getAllTranslationsForLocale returns few or no app translations for English', function () {
    $translations = I18N::getAllTranslationsForLocale('en-US');

    // en-US falls back to en, which has a few entries that differ from the source key
    // (e.g., capitalization fixes, template content). But it should be far fewer than
    // a fully translated locale like Dutch.
    $dutchTranslations = I18N::getAllTranslationsForLocale('nl');

    $enCount = isset($translations['app']) ? count($translations['app']) : 0;
    $nlCount = count($dutchTranslations['app']);

    expect($enCount)->toBeLessThan(50)
        ->and($nlCount)->toBeGreaterThan(500);
});
