<?php

declare(strict_types=1);

use CraftCms\Cms\Translation\I18N;
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

it('resolves ICU patterns after locale alias and timezone changes', function () {
    $locale = new Locale('en-US');
    $originalTimezone = date_default_timezone_get();

    try {
        foreach (['UTC', 'Asia/Tokyo'] as $timezone) {
            date_default_timezone_set($timezone);

            foreach (['en-US', 'nl-BE'] as $localeId) {
                $locale->id = $localeId;

                foreach ([null, 'fr-FR'] as $alias) {
                    $locale->aliasOf = $alias;

                    foreach (['short' => IntlDateFormatter::SHORT, 'full' => IntlDateFormatter::FULL] as $length => $style) {
                        $expected = new IntlDateFormatter($alias ?? $localeId, $style, $style)->getPattern();

                        expect($locale->getDateTimeFormat($length))->toBe(strtr($expected, ['yyyy' => 'yyyy', 'yy' => 'yyyy']));
                    }
                }
            }
        }
    } finally {
        date_default_timezone_set($originalTimezone);
    }
});

it('keeps formatter customization local to each locale instance', function () {
    $first = app(I18N::class)->getLocaleById('en-US')->getFormatter();
    $originalFormats = $first->dateTimeFormats;
    $first->dateTimeFormats['short']['date'] = 'yyyy';
    $first->timeZone = 'Asia/Tokyo';

    $second = app(I18N::class)->getLocaleById('en-US')->getFormatter();

    expect($second)->not->toBe($first)
        ->and($second->dateTimeFormats)->toBe($originalFormats)
        ->and($second->timeZone)->not->toBe('Asia/Tokyo');
});
