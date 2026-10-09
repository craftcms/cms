<?php

declare(strict_types=1);

use craft\base\Model;

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
