<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Validation\Rules\UniqueCaseInsensitiveRule;
use Illuminate\Support\Facades\DB;

it('passes when value does not exist in database', function () {
    $rule = new UniqueCaseInsensitiveRule(Table::DRAFTS, 'name');
    $valid = true;

    $rule->validate('name', 'nonexistent', function () use (&$valid) {
        $valid = false;
    });

    expect($valid)->toBeTrue();
});

it('fails case-insensitively when value exists', function (string $existing, string $input) {
    DB::table(Table::DRAFTS)->insert([
        'name' => $existing,
        'saved' => true,
    ]);

    $rule = new UniqueCaseInsensitiveRule(Table::DRAFTS, 'name');
    $valid = true;

    $rule->validate('name', $input, function () use (&$valid) {
        $valid = false;
    });

    expect($valid)->toBeFalse();
})->with([
    'exact match' => ['existing-draft', 'existing-draft'],
    'lowercase to mixed case' => ['MyDraft', 'mydraft'],
    'lowercase to uppercase' => ['testdraft', 'TESTDRAFT'],
    'uppercase to lowercase' => ['MYDRAFT', 'mydraft'],
    'mixed case variations' => ['TestDraft', 'tEsTdRaFt'],
]);

it('respects ignore parameter', function (bool $shouldPass, int $idOffset) {
    $id = DB::table(Table::DRAFTS)->insertGetId([
        'name' => 'mydraft',
        'saved' => true,
    ]);

    $rule = new UniqueCaseInsensitiveRule(Table::DRAFTS, 'name')->ignore($id + $idOffset);
    $valid = true;

    $rule->validate('name', 'mydraft', function () use (&$valid) {
        $valid = false;
    });

    expect($valid)->toBe($shouldPass);
})->with([
    'passes when ignoring same id' => [true, 0],
    'fails when ignoring different id' => [false, 1],
]);

it('respects where clauses', function (bool $shouldPass, bool $savedValue, bool $whereValue) {
    DB::table(Table::DRAFTS)->insert([
        'name' => 'conditionaldraft',
        'saved' => $savedValue,
    ]);

    $rule = new UniqueCaseInsensitiveRule(Table::DRAFTS, 'name')->where('saved', $whereValue);
    $valid = true;

    $rule->validate('name', 'conditionaldraft', function () use (&$valid) {
        $valid = false;
    });

    expect($valid)->toBe($shouldPass);
})->with([
    'passes when where clause does not match' => [true, false, true],
    'fails when where clause matches' => [false, true, true],
]);
