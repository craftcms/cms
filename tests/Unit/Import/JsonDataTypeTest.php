<?php

declare(strict_types=1);

use CraftCms\Cms\Import\DataTypes\Json;

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

it('reports JSON that isn’t an array or object', function () {
    $result = Json::format('"just a string"');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toStartWith('Invalid JSON:');
});

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

it('marks each part of a heading that holds a list, leaving its value alone', function () {
    $result = Json::getHeadings('[{"author": {"name": "Ada"}, "related": ["a"], "blocks": [{"title": "x", "steps": [{"text": "y"}]}]}]');

    expect(array_column($result, 'label', 'value'))->toBe([
        'author' => 'author',
        'author.name' => 'author.name',
        'blocks' => 'blocks[]',
        'blocks.steps' => 'blocks[].steps[]',
        'blocks.steps.text' => 'blocks[].steps[].text',
        'blocks.title' => 'blocks[].title',
        'related' => 'related[]',
    ]);
});

it('marks a list that only a later item fills in', function () {
    $result = Json::getHeadings('[{"tips": []}, {"steps": [{"tips": [{"text": "x"}]}]}]');

    expect(array_column($result, 'label', 'value'))
        ->toMatchArray(['tips' => 'tips[]', 'steps.tips' => 'steps[].tips[]', 'steps.tips.text' => 'steps[].tips[].text']);
});

it('hints a deeply nested heading with a value from whichever item has one', function () {
    $result = Json::getHeadings(json_encode([
        ['content' => [['type' => 'text', 'body' => 'Hi'], ['steps' => [['tips' => []], ['tips' => [['tone' => '']]]]]]],
        ['content' => [['steps' => [['tips' => [['tone' => 'warning']]]]]]],
    ]));

    expect(collect($result)->firstWhere('value', 'content.steps.tips.tone')['data']['hint'] ?? null)->toBe('warning');
});

it('hints a boolean heading the way its preview reads', function () {
    $result = Json::getHeadings('[{"optional": false}]');

    expect($result[0]['data']['hint'] ?? null)->toBe('false');
});
