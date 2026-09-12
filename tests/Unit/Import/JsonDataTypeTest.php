<?php

declare(strict_types=1);

use CraftCms\Cms\Import\DataTypes\Json;

// format()

it('formats a JSON list into rows', function () {
    $result = Json::format('[{"title": "one"}, {"title": "two"}]');

    expect($result['success'])->toBeTrue()
        ->and($result['data'])->toBe([['title' => 'one'], ['title' => 'two']]);
});

it('keeps nested structures intact when formatting', function () {
    $result = Json::format('[{"blocks": [{"type": "a", "fields": {"text": "x"}}]}]');

    expect($result['data'][0]['blocks'][0])->toBe(['type' => 'a', 'fields' => ['text' => 'x']]);
});

it('reports malformed JSON instead of throwing', function () {
    $result = Json::format('[{"title": "unterminated"');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toStartWith('Invalid JSON:');
});

it('reports malformed JSON when reading headings', function () {
    $result = Json::getHeadings('[{"title": "unterminated"');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toStartWith('Invalid JSON:');
});

// getHeadings()

it('returns top-level keys for a flat JSON array', function () {
    $result = Json::getHeadings('[{"name": "Alice", "age": 30}]');

    $values = array_column($result, 'value');
    expect($values)->toContain('name')->toContain('age');
});

it('returns dot-notation keys for nested JSON objects', function () {
    $result = Json::getHeadings('[{"user": {"name": "Alice", "address": {"city": "NYC"}}}]');

    $values = array_column($result, 'value');
    expect($values)
        ->toContain('user')
        ->toContain('user.name')
        ->toContain('user.address')
        ->toContain('user.address.city');
});

it('traverses array items without appending numeric index to the path', function () {
    $result = Json::getHeadings('[{"tags": [{"name": "foo"}, {"name": "bar"}]}]');

    $values = array_column($result, 'value');
    expect($values)->toContain('tags')->toContain('tags.name');
    expect($values)->not()->toContain('tags.0')->not()->toContain('tags.0.name');
});

it('deduplicates keys that appear across multiple top-level objects', function () {
    $result = Json::getHeadings('[{"a": 1}, {"a": 2, "b": 3}]');

    $values = array_column($result, 'value');
    expect(array_count_values($values)['a'])->toBe(1);
    expect($values)->toContain('b');
});

it('returns label and value for each heading', function () {
    $result = Json::getHeadings('[{"title": "Hello"}]');

    expect($result)->toHaveCount(1);
    expect($result[0])->toHaveKeys(['label', 'value']);
    expect($result[0]['label'])->toBe($result[0]['value']);
});
