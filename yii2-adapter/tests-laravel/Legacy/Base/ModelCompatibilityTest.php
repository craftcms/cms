<?php

declare(strict_types=1);

use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;

test('legacy model arrays do not expose validation ruleset state', function() {
    $model = new class(['foo' => 'bar']) extends Model {
        public ?string $foo = null;
    };

    expect($model->attributes())->not->toContain('ruleset')
        ->and($model->toArray())->toBe(['foo' => 'bar']);
});

test('legacy model unsafe attribute assignment ignores validation ruleset state', function() {
    $model = new class(['foo' => 'bar']) extends Model {
        public ?string $foo = null;
    };

    $model->setAttributes([
        'foo' => 'baz',
        'ruleset' => [],
    ], false);

    expect($model->foo)->toBe('baz');
});

test('legacy model validation keeps environment variable references', function() {
    $_SERVER['LEGACY_MODEL_SECRET'] = 'resolved-secret';

    try {
        $model = new class(['secret' => '$LEGACY_MODEL_SECRET']) extends Model {
            public ?string $secret = null;

            public function behaviors(): array
            {
                return [
                    'parser' => [
                        'class' => EnvAttributeParserBehavior::class,
                        'attributes' => ['secret'],
                    ],
                ];
            }

            protected function defineRules(): array
            {
                return [
                    [['secret'], 'string', 'max' => 16],
                ];
            }
        };

        expect($model->validate())->toBeTrue()
            ->and($model->secret)->toBe('$LEGACY_MODEL_SECRET');
    } finally {
        unset($_SERVER['LEGACY_MODEL_SECRET']);
    }
});
