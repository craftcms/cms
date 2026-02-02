<?php

use craft\helpers\Assets;
use CraftCms\Cms\Cp\SelectOptions;

describe('getEnvSuggestions', function () {
    it('returns environment variable suggestions without aliases', function () {
        $suggestions = SelectOptions::getEnvSuggestions();

        expect($suggestions)->toHaveCount(1)
            ->and($suggestions[0])->toHaveKey('label')
            ->and($suggestions[0])->toHaveKey('options')
            ->and($suggestions[0]['options'])->toBeArray();
    });

    it('returns suggestions with aliases when includeAliases is true', function () {
        $suggestions = SelectOptions::getEnvSuggestions(includeAliases: true);

        expect($suggestions)->toHaveCount(2)
            ->and($suggestions[0])->toHaveKey('label')
            ->and($suggestions[1])->toHaveKey('label')
            ->and($suggestions[0]['options'])->toBeArray()
            ->and($suggestions[1]['options'])->toBeArray();
    });

    it('filters suggestions based on filter callback', function () {
        $filter = fn ($value) => is_string($value) && strlen($value) > 5;
        $suggestions = SelectOptions::getEnvSuggestions(filter: $filter);

        expect($suggestions[0]['options'])->toBeArray();

        foreach ($suggestions[0]['options'] as $suggestion) {
            expect($suggestion)->toHaveKey('label')
                ->and($suggestion)->toHaveKey('data.hint');
        }
    });

    it('excludes HTTP_ prefixed environment variables', function () {
        $_SERVER['HTTP_TEST_VAR'] = 'test';

        $suggestions = SelectOptions::getEnvSuggestions();

        foreach ($suggestions[0]['options'] as $suggestion) {
            expect($suggestion['label'])->not->toStartWith('$HTTP_');
        }

        unset($_SERVER['HTTP_TEST_VAR']);
    });

    it('excludes @web aliases when includeAliases is true', function () {
        $suggestions = SelectOptions::getEnvSuggestions(includeAliases: true);

        if (isset($suggestions[1]['options'])) {
            foreach ($suggestions[1]['options'] as $suggestion) {
                expect($suggestion['label'])->not->toBe('@web')
                    ->and($suggestion['label'])->not->toStartWith('@web/');
            }
        }
    });

    it('formats env var names with $ prefix', function () {
        $suggestions = SelectOptions::getEnvSuggestions();

        foreach ($suggestions[0]['options'] as $suggestion) {
            expect($suggestion['label'])->toStartWith('$');
        }
    });
});

describe('getEnvOptions', function () {
    it('returns environment variable options', function () {
        $options = SelectOptions::getEnvOptions();

        expect($options)->toBeArray();

        foreach ($options as $option) {
            if (isset($option['options'])) {
                continue;
            }
            expect($option)->toHaveKey('label')
                ->and($option)->toHaveKey('value')
                ->and($option['label'])->toStartWith('$')
                ->and($option['value'])->toStartWith('$');
        }
    });

    it('returns empty array when allowedValues is empty', function () {
        expect(SelectOptions::getEnvOptions([]))->toBeEmpty();
    });

    it('filters options by allowedValues', function () {
        $_SERVER['TEST_ENV_VAR'] = 'allowed_value';

        $options = SelectOptions::getEnvOptions(['allowed_value', 'other_value']);
        $values = array_column(array_filter($options, fn ($opt) => ! isset($opt['optgroup'])), 'value');

        if (! empty($values)) {
            expect($values)->toContain('$TEST_ENV_VAR');
        }

        unset($_SERVER['TEST_ENV_VAR']);
    });

    it('excludes HTTP_ prefixed variables', function () {
        $_SERVER['HTTP_TEST'] = 'test';

        $options = SelectOptions::getEnvOptions();

        foreach ($options as $option) {
            if (! isset($option['options'])) {
                expect($option['value'])->not->toStartWith('$HTTP_');
            }
        }

        unset($_SERVER['HTTP_TEST']);
    });

    it('includes hint for non-empty values', function () {
        $_SERVER['TEST_ENV_WITH_VALUE'] = 'some_value';

        $options = SelectOptions::getEnvOptions();

        $found = false;
        foreach ($options as $option) {
            if (isset($option['value']) && $option['value'] === '$TEST_ENV_WITH_VALUE') {
                expect($option)->toHaveKey('data')
                    ->and($option['data'])->toHaveKey('hint');
                $found = true;
                break;
            }
        }

        unset($_SERVER['TEST_ENV_WITH_VALUE']);
    });

    it('sorts options alphabetically', function () {
        $options = SelectOptions::getEnvOptions();
        $values = array_column(array_filter($options, fn ($opt) => ! isset($opt['optgroup'])), 'value');
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
        $_SERVER['TEST_NON_BOOL'] = 'not_a_boolean';

        $options = SelectOptions::getBooleanEnvOptions();
        $values = array_column(array_filter($options, fn ($opt) => isset($opt['value'])), 'value');

        expect($values)->not->toContain('$TEST_NON_BOOL');

        unset($_SERVER['TEST_NON_BOOL']);
    });

    it('excludes empty values', function () {
        $_SERVER['TEST_EMPTY'] = '';

        $options = SelectOptions::getBooleanEnvOptions();
        $values = array_column(array_filter($options, fn ($opt) => isset($opt['value'])), 'value');

        expect($values)->not->toContain('$TEST_EMPTY');

        unset($_SERVER['TEST_EMPTY']);
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
        $options = SelectOptions::getFsOptions();

        foreach ($options as $option) {
            $fs = Craft::$app->getFs()->getFilesystemByHandle($option['value']);
            if ($fs) {
                expect(Assets::isTempUploadFs($fs))->toBeFalse();
            }
        }
    });

    it('sorts options by label', function () {
        $options = SelectOptions::getFsOptions();
        $labels = array_column($options, 'label');
        $sorted = $labels;
        sort($sorted);

        expect($labels)->toBe($sorted);
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
        $values = array_column(array_slice($formatted, 1), 'value');
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
