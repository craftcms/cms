<?php

declare(strict_types=1);

use CraftCms\Cms\Mcp\SensitiveValues;
use Mcp\Exception\ToolCallException;

it('redacts sensitive YAML values', function () {
    $diff = implode("\n", [
        '   settings:',
        '-    apiKey: sk_live_old',
        '+    apiKey: \'$API_KEY\'',
        '     password: "hunter2"',
        '     region: us-east-1',
    ]);

    expect(app(SensitiveValues::class)->redactYaml($diff))->toBe(implode("\n", [
        '   settings:',
        '-    apiKey: •••••••••••',
        '+    apiKey: ••••••••',
        '     password: •••••••',
        '     region: us-east-1',
    ]));
});

it('keeps saved values for redacted placeholders', function () {
    $values = app(SensitiveValues::class);

    expect($values->restore(
        ['apiKey' => '•••', 'nested' => ['token' => '•'], 'region' => 'eu'],
        ['apiKey' => 'sk_live_123', 'nested' => ['token' => 'abc'], 'region' => 'us'],
    ))->toBe(['apiKey' => 'sk_live_123', 'nested' => ['token' => 'abc'], 'region' => 'eu'])
        ->and(fn () => $values->restore(['apiKey' => '•••'], []))
        ->toThrow(ToolCallException::class, 'apiKey is redacted, so it can only keep its saved value.');
});
