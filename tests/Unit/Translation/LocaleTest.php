<?php

declare(strict_types=1);

use CraftCms\Cms\Translation\Locale;

test('language id', function (string $expected, string $locale) {
    expect(Locale::languageId($locale))->toBe($expected);
    expect(new Locale($locale)->getLanguageID())->toBe($expected);
})->with([
    ['en', 'en'],
    ['en', 'EN'],
    ['en', 'en-US'],
    ['en', 'EN-US'],
    ['zh', 'zh-Hans-CN'],
    ['de', 'de-DE'],
    ['', ''],
    ['pt', 'pt-BR'],
]);
