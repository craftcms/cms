<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Conditions\TitleConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use Illuminate\Support\Facades\DB;

function createTitleRule(string $operator, string $value = ''): TitleConditionRule
{
    $condition = new ElementCondition(Entry::class);
    $rule = $condition->createConditionRule(TitleConditionRule::class);
    $rule->operator = $operator;
    $rule->value = $value;

    return $rule;
}

function createTitleConditionWithRule(string $operator, string $value = ''): ElementCondition
{
    $condition = new ElementCondition(Entry::class);
    $rule = $condition->createConditionRule(TitleConditionRule::class);
    $rule->operator = $operator;
    $rule->value = $value;
    $condition->addConditionRule($rule);

    return $condition;
}

function createEntryWithTitle(?string $title): Entry
{
    $model = EntryModel::factory()->create();

    DB::table(Table::ELEMENTS_SITES)
        ->where('elementId', $model->id)
        ->update(['title' => $title]);

    return Entry::find()->id($model->id)->one();
}

describe('matchElement', function () {
    it('evaluates the condition rule against the element', function (string $operator, string $ruleValue, ?string $title, bool $expected) {
        $rule = createTitleRule($operator, $ruleValue);
        $element = createEntryWithTitle($title);

        expect($rule->matchElement($element))->toBe($expected);
    })->with([
        'eq: matches exact title' => ['=', 'Hello', 'Hello', true],
        'eq: does not match when title differs' => ['=', 'Hello', 'Goodbye', false],
        'eq: is case-sensitive' => ['=', 'hello', 'Hello', false],
        'bw: matches when title begins with value' => ['bw', 'Hello', 'Hello World', true],
        'bw: does not match when title does not begin with value' => ['bw', 'World', 'Hello World', false],
        'bw: is case-insensitive' => ['bw', 'hello', 'Hello World', true],
        'ew: matches when title ends with value' => ['ew', 'World', 'Hello World', true],
        'ew: does not match when title does not end with value' => ['ew', 'Hello', 'Hello World', false],
        'ew: is case-insensitive' => ['ew', 'world', 'Hello World', true],
        'contains: matches when title contains value' => ['**', 'lo Wo', 'Hello World', true],
        'contains: does not match when title does not contain value' => ['**', 'Foo', 'Hello World', false],
        'contains: is case-insensitive' => ['**', 'lo wo', 'Hello World', true],
        'empty: matches when title is empty string' => ['empty', '', '', true],
        'empty: matches when title is null' => ['empty', '', null, true],
        'empty: does not match when title has a value' => ['empty', '', 'Hello', false],
        'notempty: matches when title has a value' => ['notempty', '', 'Hello', true],
        'notempty: does not match when title is empty string' => ['notempty', '', '', false],
        'notempty: does not match when title is null' => ['notempty', '', null, false],
        'passthrough: matches any title when rule value is empty string' => ['=', '', 'Anything', true],
        'passthrough: matches empty title when rule value is empty string' => ['bw', '', '', true],
    ]);
});

describe('modifyQuery', function () {
    describe('OPERATOR_EQ (=)', function () {
        it('returns only entries with matching title', function () {
            createEntryWithTitle('Hello');
            createEntryWithTitle('Goodbye');

            $condition = createTitleConditionWithRule('=', 'Hello');
            $query = Entry::find();
            $condition->modifyQuery($query);

            expect($query->count())->toBe(1);
            expect($query->one()->title)->toBe('Hello');
        });

        it('returns no entries when none match', function () {
            createEntryWithTitle('Hello');

            $condition = createTitleConditionWithRule('=', 'Nonexistent');
            $query = Entry::find();
            $condition->modifyQuery($query);

            expect($query->count())->toBe(0);
        });
    });

    describe('OPERATOR_BEGINS_WITH (bw)', function () {
        it('returns entries whose title begins with value', function () {
            createEntryWithTitle('Hello World');
            createEntryWithTitle('Hey There');
            createEntryWithTitle('Goodbye');

            $condition = createTitleConditionWithRule('bw', 'He');
            $query = Entry::find();
            $condition->modifyQuery($query);

            expect($query->count())->toBe(2);
        });
    });

    describe('OPERATOR_ENDS_WITH (ew)', function () {
        it('returns entries whose title ends with value', function () {
            createEntryWithTitle('Hello World');
            createEntryWithTitle('Brave New World');
            createEntryWithTitle('Goodbye');

            $condition = createTitleConditionWithRule('ew', 'World');
            $query = Entry::find();
            $condition->modifyQuery($query);

            expect($query->count())->toBe(2);
        });
    });

    describe('OPERATOR_CONTAINS (**)', function () {
        it('returns entries whose title contains value', function () {
            createEntryWithTitle('Hello World');
            createEntryWithTitle('World Hello');
            createEntryWithTitle('Goodbye');

            $condition = createTitleConditionWithRule('**', 'World');
            $query = Entry::find();
            $condition->modifyQuery($query);

            expect($query->count())->toBe(2);
        });

        it('returns no entries when none contain value', function () {
            createEntryWithTitle('Hello');

            $condition = createTitleConditionWithRule('**', 'xyz');
            $query = Entry::find();
            $condition->modifyQuery($query);

            expect($query->count())->toBe(0);
        });
    });

    describe('OPERATOR_EMPTY (empty)', function () {
        it('returns entries with null title', function () {
            createEntryWithTitle(null);
            createEntryWithTitle('Hello');

            $condition = createTitleConditionWithRule('empty');
            $query = Entry::find();
            $condition->modifyQuery($query);

            expect($query->count())->toBe(1);
            expect($query->one()->title)->toBeNull();
        });
    });

    describe('OPERATOR_NOT_EMPTY (notempty)', function () {
        it('returns entries with non-null title', function () {
            createEntryWithTitle('Hello');
            createEntryWithTitle(null);

            $condition = createTitleConditionWithRule('notempty');
            $query = Entry::find();
            $condition->modifyQuery($query);

            expect($query->count())->toBe(1);
            expect($query->one()->title)->toBe('Hello');
        });
    });

    describe('empty value passthrough', function () {
        it('does not filter when rule value is empty string', function () {
            createEntryWithTitle('Hello');
            createEntryWithTitle('World');

            $condition = createTitleConditionWithRule('=', '');
            $query = Entry::find();
            $condition->modifyQuery($query);

            expect($query->count())->toBe(2);
        });
    });
});
