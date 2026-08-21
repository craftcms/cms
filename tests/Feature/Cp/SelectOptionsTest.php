<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Elements\ContentBlock as ContentBlockElement;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Filesystem\Filesystems\Local;
use CraftCms\Cms\Support\Facades\Filesystems;

describe('getEnvSuggestions', function () {
    it('returns environment variable suggestions without aliases', function () {
        $suggestions = SelectOptions::getEnvSuggestions();

        expect($suggestions)->toHaveCount(1)
            ->and($suggestions[0])->toHaveKey('label')
            ->and($suggestions[0])->toHaveKey('options')
            ->and($suggestions[0]['options'])->toBeArray();
    });

    it('filters suggestions based on filter callback', function () {
        $_SERVER['TEST_SHORT_ENV'] = 'no';
        $_SERVER['TEST_LONG_ENV'] = 'longer';

        $filter = fn ($value) => is_string($value) && strlen($value) > 5;
        $suggestions = SelectOptions::getEnvSuggestions(filter: $filter);
        $values = array_column($suggestions[0]['options'], 'value');

        expect($values)->toContain('$TEST_LONG_ENV')
            ->not->toContain('$TEST_SHORT_ENV');

        foreach ($suggestions[0]['options'] as $suggestion) {
            expect($suggestion)->toHaveKey('label')
                ->and($suggestion)->toHaveKey('data.hint');
        }

        unset($_SERVER['TEST_SHORT_ENV'], $_SERVER['TEST_LONG_ENV']);
    });

    it('excludes HTTP_ prefixed environment variables', function () {
        $_SERVER['TEST_NON_HTTP_VAR'] = 'test';
        $_SERVER['HTTP_TEST_VAR'] = 'test';

        $suggestions = SelectOptions::getEnvSuggestions();
        $values = array_column($suggestions[0]['options'], 'value');

        expect($values)->toContain('$TEST_NON_HTTP_VAR')
            ->not->toContain('$HTTP_TEST_VAR');

        unset($_SERVER['TEST_NON_HTTP_VAR'], $_SERVER['HTTP_TEST_VAR']);
    });

    it('formats env var names with $ prefix', function () {
        $_SERVER['TEST_FORMAT_ENV_VAR'] = 'test';

        $suggestions = SelectOptions::getEnvSuggestions();
        $values = array_column($suggestions[0]['options'], 'value');

        expect($values)->toContain('$TEST_FORMAT_ENV_VAR');

        foreach ($suggestions[0]['options'] as $suggestion) {
            expect($suggestion['label'])->toStartWith('$');
        }

        unset($_SERVER['TEST_FORMAT_ENV_VAR']);
    });
});

describe('getEnvTextExpanderTriggers', function () {
    it('returns position-aware environment triggers', function () {
        $_SERVER['TEST_TEXT_EXPANDER'] = 'https://example.com';

        try {
            $triggers = SelectOptions::getEnvTextExpanderTriggers();
            [$directTrigger, $embeddedTrigger] = $triggers;
            $direct = collect($directTrigger['options'])->firstWhere('value', '$TEST_TEXT_EXPANDER');
            $embedded = collect($embeddedTrigger['options'])->firstWhere('value', '${TEST_TEXT_EXPANDER}');

            expect($triggers)->toHaveCount(2)
                ->and($directTrigger['trigger'])->toBe('$')
                ->and($directTrigger['boundary'])->toBe('start')
                ->and($embeddedTrigger['trigger'])->toBe('$')
                ->and($embeddedTrigger['boundary'])->toBe('anywhere')
                ->and($direct['data']['hint'])->toBe('https://example.com')
                ->and($embedded['label'])->toBe('$TEST_TEXT_EXPANDER')
                ->and($embedded['data']['hint'])->toBe('https://example.com');
        } finally {
            unset($_SERVER['TEST_TEXT_EXPANDER']);
        }
    });

    it('omits empty triggers', function () {
        expect(SelectOptions::getEnvTextExpanderTriggers(fn () => false))->toBeEmpty();
    });
});

describe('getObjectTemplateTextExpanderTriggers', function () {
    it('uses the base element suggestions by default', function () {
        [$trigger] = SelectOptions::getObjectTemplateTextExpanderTriggers();
        $values = array_column($trigger['options'], 'value');

        expect($values)->toContain('{site.handle}')
            ->not->toContain('{owner.id}');
    });

    it('returns built-in, custom field, and nested content block properties', function () {
        $nestedField = new PlainText([
            'name' => 'Meta Description',
            'handle' => 'metaDescription',
            'uid' => 'meta-description-field',
        ]);
        $contentBlockLayout = FieldLayout::make(ContentBlockElement::class)
            ->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab->add(new CustomField($nestedField)));
        $contentBlock = new ContentBlock([
            'name' => 'SEO',
            'handle' => 'seo',
            'uid' => 'seo-field',
            'fieldLayout' => $contentBlockLayout,
        ]);
        $summary = new PlainText([
            'name' => 'Summary',
            'handle' => 'summary',
            'uid' => 'summary-field',
        ]);
        $layout = FieldLayout::make(Entry::class)
            ->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab
                ->add(new CustomField($summary))
                ->add(new CustomField($contentBlock)));

        [$trigger] = SelectOptions::getObjectTemplateTextExpanderTriggers(Entry::class, [$layout]);
        $options = collect($trigger['options'])->keyBy('value');

        expect($trigger['trigger'])->toBe('{')
            ->and($trigger['boundary'])->toBe('anywhere')
            ->and($options)->toHaveKeys([
                '{site.handle}',
                '{owner.id}',
                '{author.username}',
                '{summary}',
                '{seo.metaDescription}',
            ])
            ->and($options['{seo.metaDescription}']['data']['hint'])->toBe('{seo.metaDescription}')
            ->and($options['{seo.metaDescription}']['keywords'])->toBe(['seo.metaDescription']);
    });
});

describe('getEnvOptions', function () {
    it('returns environment variable options', function () {
        $_SERVER['TEST_ENV_OPTIONS'] = 'env_option_value';

        $options = SelectOptions::getEnvOptions();
        $option = collect($options[0]['options'])->firstWhere('value', '$TEST_ENV_OPTIONS');

        expect($option)->not->toBeNull()
            ->and($option['label'])->toBe('$TEST_ENV_OPTIONS')
            ->and($option['value'])->toBe('$TEST_ENV_OPTIONS')
            ->and($option['data']['hint'])->toBe('env_option_value');

        unset($_SERVER['TEST_ENV_OPTIONS']);
    });

    it('returns empty array when allowedValues is empty', function () {
        expect(SelectOptions::getEnvOptions([]))->toBeEmpty();
    });

    it('filters options by allowedValues', function () {
        $_SERVER['TEST_ENV_VAR'] = 'allowed_value';

        $options = SelectOptions::getEnvOptions(['allowed_value', 'other_value']);
        $values = array_column($options[0]['options'], 'value');

        expect($values)->toContain('$TEST_ENV_VAR');

        unset($_SERVER['TEST_ENV_VAR']);
    });

    it('excludes HTTP_ prefixed variables', function () {
        $_SERVER['TEST_NON_HTTP'] = 'test';
        $_SERVER['HTTP_TEST'] = 'test';

        $options = SelectOptions::getEnvOptions();
        $values = array_column($options[0]['options'], 'value');

        expect($values)->toContain('$TEST_NON_HTTP')
            ->not->toContain('$HTTP_TEST');

        unset($_SERVER['TEST_NON_HTTP'], $_SERVER['HTTP_TEST']);
    });

    it('includes hint for non-empty values', function () {
        $_SERVER['TEST_ENV_WITH_VALUE'] = 'some_value';

        $options = SelectOptions::getEnvOptions();
        $option = collect($options[0]['options'])->firstWhere('value', '$TEST_ENV_WITH_VALUE');

        expect($option)->not->toBeNull()
            ->and($option)->toHaveKey('data')
            ->and($option['data'])->toHaveKey('hint')
            ->and($option['data']['hint'])->toBe('some_value');

        unset($_SERVER['TEST_ENV_WITH_VALUE']);
    });

    it('sorts options alphabetically', function () {
        $options = SelectOptions::getEnvOptions();
        $values = array_column($options[0]['options'], 'value');
        $sorted = $values;
        sort($sorted);

        expect($values)->toBe($sorted);
    });
});

describe('getBooleanEnvOptions', function () {
    it('returns boolean environment variable options', function () {
        $_SERVER['TEST_BOOL_TRUE'] = 'true';
        $_SERVER['TEST_BOOL_FALSE'] = 'false';

        $groups = SelectOptions::getBooleanEnvOptions();
        $foundTrue = $foundFalse = false;

        foreach ($groups as $options) {
            foreach ($options['options'] as $option) {
                expect($option)->toHaveKey('label')
                    ->and($option)->toHaveKey('value')
                    ->and($option)->toHaveKey('data')
                    ->and($option['data'])->toHaveKey('boolean');

                if ($option['value'] === '$TEST_BOOL_TRUE') {
                    expect($option['data']['boolean'])->toBe('1');
                    $foundTrue = true;
                }

                if ($option['value'] === '$TEST_BOOL_FALSE') {
                    expect($option['data']['boolean'])->toBe('0');
                    $foundFalse = true;
                }
            }
        }

        expect($foundTrue)->toBeTrue()
            ->and($foundFalse)->toBeTrue();

        unset($_SERVER['TEST_BOOL_TRUE'], $_SERVER['TEST_BOOL_FALSE']);
    });

    it('excludes non-boolean values', function () {
        $_SERVER['TEST_BOOL_INCLUDED'] = 'true';
        $_SERVER['TEST_NON_BOOL'] = 'not_a_boolean';

        $groups = SelectOptions::getBooleanEnvOptions();
        $values = array_column($groups[0]['options']->all(), 'value');

        expect($values)->toContain('$TEST_BOOL_INCLUDED')
            ->not->toContain('$TEST_NON_BOOL');

        unset($_SERVER['TEST_BOOL_INCLUDED'], $_SERVER['TEST_NON_BOOL']);
    });

    it('excludes empty values', function () {
        $_SERVER['TEST_BOOL_INCLUDED'] = 'true';
        $_SERVER['TEST_EMPTY'] = '';

        $groups = SelectOptions::getBooleanEnvOptions();
        $values = array_column($groups[0]['options']->all(), 'value');

        expect($values)->toContain('$TEST_BOOL_INCLUDED')
            ->not->toContain('$TEST_EMPTY');

        unset($_SERVER['TEST_BOOL_INCLUDED'], $_SERVER['TEST_EMPTY']);
    });

    it('handles numeric boolean values', function () {
        $_SERVER['TEST_NUM_TRUE'] = '1';
        $_SERVER['TEST_NUM_FALSE'] = '0';

        $groups = SelectOptions::getBooleanEnvOptions();

        $found = 0;
        foreach ($groups as $options) {
            foreach ($options['options'] as $option) {
                if (isset($option['value']) && in_array($option['value'], ['$TEST_NUM_TRUE', '$TEST_NUM_FALSE'])) {
                    expect($option['data']['boolean'])->toBeIn(['0', '1']);
                    $found++;
                }
            }
        }

        expect($found)->toBeGreaterThanOrEqual(2);

        unset($_SERVER['TEST_NUM_TRUE'], $_SERVER['TEST_NUM_FALSE']);
    });
});

describe('getLanguageOptions', function () {
    it('returns language options', function () {
        $options = SelectOptions::getLanguageOptions();

        expect($options)->not->toBeEmpty();

        foreach ($options as $option) {
            expect($option)->toHaveKey('label')
                ->and($option)->toHaveKey('value')
                ->and($option)->toHaveKey('data');
        }
    });

    it('includes locale IDs when showLocaleIds is true', function () {
        $options = SelectOptions::getLanguageOptions(showLocaleIds: true);
        $hasHint = array_any($options, fn ($option) => isset($option['data']['hint']));

        expect($hasHint)->toBeTrue();
    });

    it('includes localized names when showLocalizedNames is true', function () {
        $options = SelectOptions::getLanguageOptions(showLocalizedNames: true);
        $hasHintLang = array_any($options, fn ($option) => isset($option['data']['hint']));

        expect($hasHintLang)->toBeTrue();
    });

    it('limits to app locales when appLocales is true', function () {
        $allOptions = SelectOptions::getLanguageOptions(appLocales: false);
        $appOptions = SelectOptions::getLanguageOptions(appLocales: true);

        expect(count($appOptions))->toBeLessThanOrEqual(count($allOptions));
    });
});

describe('getFsOptions', function () {
    it('returns filesystem options', function () {
        $options = SelectOptions::getFsOptions();

        expect($options)->toBeArray();

        foreach ($options as $option) {
            expect($option)->toHaveKey('label')
                ->and($option)->toHaveKey('value');
        }
    });

    it('excludes temp upload filesystems', function () {
        $previousTempAssetUploadFs = Cms::config()->tempAssetUploadFs;

        try {
            $included = Filesystems::createFilesystem([
                'type' => Local::class,
                'name' => 'Included Select Options Filesystem',
                'handle' => 'includedSelectOptionsFs',
                'settings' => [
                    'path' => storage_path('framework/testing/select-options/included-select-options-fs'),
                ],
            ]);
            $excluded = Filesystems::createFilesystem([
                'type' => Local::class,
                'name' => 'Temp Select Options Filesystem',
                'handle' => 'tempSelectOptionsFs',
                'settings' => [
                    'path' => storage_path('framework/testing/select-options/temp-select-options-fs'),
                ],
            ]);

            expect(Filesystems::saveFilesystem($included, false))->toBeTrue()
                ->and(Filesystems::saveFilesystem($excluded, false))->toBeTrue();

            Cms::config()->tempAssetUploadFs = 'tempSelectOptionsFs';

            $values = array_column(SelectOptions::getFsOptions(), 'value');

            expect(AssetsHelper::isTempUploadFs($excluded))->toBeTrue()
                ->and($values)->toContain('includedSelectOptionsFs')
                ->not->toContain('tempSelectOptionsFs');
        } finally {
            Cms::config()->tempAssetUploadFs = $previousTempAssetUploadFs;
        }
    });

    it('sorts options by label', function () {
        $options = SelectOptions::getFsOptions();
        $labels = array_column($options, 'label');
        $sorted = $labels;
        sort($sorted);

        expect($labels)->toBe($sorted);
    });

    it('includes manually configured Laravel disks with a disk: prefix', function () {
        config()->set('filesystems.disks.manual-select-options-disk', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/select-options/manual-select-options-disk'),
        ]);

        $values = array_column(SelectOptions::getFsOptions(), 'value');

        expect($values)->toContain('disk:manual-select-options-disk');
    });

    it('excludes internal and Craft-registered system disks', function () {
        config()->set('filesystems.disks.craft-tmp', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/select-options/craft-tmp'),
        ]);

        $values = array_column(SelectOptions::getFsOptions(), 'value');

        expect($values)->not->toContain('disk:craft-tmp');
    });
});

describe('getTimeZoneOptions', function () {
    it('returns timezone options', function () {
        $options = SelectOptions::getTimeZoneOptions();

        expect($options)->not->toBeEmpty();

        foreach ($options as $option) {
            expect($option)->toHaveKey('label')
                ->and($option)->toHaveKey('value')
                ->and($option['label'])->toStartWith('(GMT');
        }
    });

    it('includes UTC timezone', function () {
        $options = SelectOptions::getTimeZoneOptions();
        $values = array_column($options, 'value');

        expect($values)->toContain('UTC');
    });

    it('includes hints for non-UTC timezones', function () {
        $options = SelectOptions::getTimeZoneOptions();

        foreach ($options as $option) {
            if ($option['value'] !== 'UTC' && str_contains((string) $option['value'], '/')) {
                expect($option)->toHaveKey('data')
                    ->and($option['data'])->toHaveKey('hint');
            }
        }
    });

    it('formats timezone hints correctly', function () {
        $options = SelectOptions::getTimeZoneOptions();

        foreach ($options as $option) {
            if (isset($option['data']['hint'])) {
                expect($option['data']['hint'])->not->toContain('_');
            }
        }
    });

    it('accepts custom offset date', function () {
        $customDate = new DateTime('2024-07-01');
        $options = SelectOptions::getTimeZoneOptions($customDate);

        expect($options)->not->toBeEmpty();
    });
});

describe('formatEnvOptions', function () {
    it('adds optgroup header', function () {
        $options = [
            ['label' => '$VAR1', 'value' => '$VAR1'],
            ['label' => '$VAR2', 'value' => '$VAR2'],
        ];

        $formatted = SelectOptions::formatEnvOptions($options);

        expect($formatted[0])->toHaveKey('options')
            ->and($formatted[0]['label'])->toBe('Environment Variables');
    });

    it('sorts options by value', function () {
        $options = [
            ['label' => '$ZZZ', 'value' => '$ZZZ'],
            ['label' => '$AAA', 'value' => '$AAA'],
            ['label' => '$MMM', 'value' => '$MMM'],
        ];

        $formatted = SelectOptions::formatEnvOptions($options);
        $values = array_column($formatted[0]['options'], 'value');
        $sorted = $values;
        sort($sorted);

        expect($values)->toBe($sorted);
    });

    it('preserves option data', function () {
        $options = [
            [
                'label' => '$VAR1',
                'value' => '$VAR1',
                'data' => ['hint' => 'test hint'],
            ],
        ];

        $formatted = SelectOptions::formatEnvOptions($options);

        expect($formatted[0]['options'][0]['data']['hint'])->toBe('test hint');
    });
});
