<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Conditions\StatusConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());

    $this->condition = new ElementCondition(Entry::class);
});

describe('setValues', function () {
    test('accepts an array of values', function () {
        $rule = $this->condition->createConditionRule(StatusConditionRule::class);
        $rule->values = ['live', 'pending'];

        expect($rule->values)->toBe(['live', 'pending']);
    });

    test('setValues accepts a single string value', function () {
        $rule = $this->condition->createConditionRule(StatusConditionRule::class);
        $rule->setValues('live');

        expect($rule->values)->toBe(['live']);
    });

    test('setValues with empty string resets to empty array', function () {
        $rule = $this->condition->createConditionRule(StatusConditionRule::class);
        $rule->values = ['live'];
        $rule->setValues('');

        expect($rule->values)->toBe([]);
    });
});

describe('matchElement', function () {
    it('evaluates element against operator and values', function (string $operator, ?array $values, bool $expected) {
        $entryModel = EntryModel::factory()->create();
        $entry = Entry::find()->id($entryModel->id)->one();

        $rule = $this->condition->createConditionRule(StatusConditionRule::class);
        $rule->operator = $operator;
        $rule->values = $values ?? [$entry->getStatus()];

        expect($rule->matchElement($entry))->toBe($expected);
    })->with([
        'in: matches element with matching status' => ['in', null, true],
        'in: does not match with different status' => ['in', ['expired'], false],
        'in: matches any when values empty' => ['in', [], true],
        'ni: matches when status not in values' => ['ni', ['expired', 'pending'], true],
        'ni: does not match when status in values' => ['ni', null, false],
        'ni: matches any when values empty' => ['ni', [], true],
    ]);
});

describe('modifyQuery', function () {
    test('filters query by status with in operator', function () {
        EntryModel::factory()->count(3)->create();

        $rule = $this->condition->createConditionRule(StatusConditionRule::class);
        $rule->operator = 'in';
        $rule->values = ['live'];

        $query = Entry::find()->status(null);
        $rule->modifyQuery($query);

        $results = $query->all();

        foreach ($results as $result) {
            expect($result->getStatus())->toBe('live');
        }
    });

    test('filters query by status with not-in operator', function () {
        EntryModel::factory()->count(3)->create();

        $allCount = Entry::find()->status(null)->count();

        $rule = $this->condition->createConditionRule(StatusConditionRule::class);
        $rule->operator = 'ni';
        $rule->values = ['live'];

        $query = Entry::find()->status(null);
        $rule->modifyQuery($query);

        $results = $query->all();

        // Should exclude live entries, so count should differ
        $liveCount = Entry::find()->status('live')->count();
        expect(count($results))->toBe($allCount - $liveCount);

        foreach ($results as $result) {
            expect($result->getStatus())->not()->toBe('live');
        }
    });
});

describe('getConfig', function () {
    test('includes class, uid, operator, and values', function () {
        $rule = $this->condition->createConditionRule(StatusConditionRule::class);
        $rule->operator = 'in';
        $rule->values = ['live', 'pending'];

        $config = $rule->config;

        expect($config)
            ->toHaveKey('class', StatusConditionRule::class)
            ->toHaveKey('uid')
            ->toHaveKey('operator', 'in')
            ->toHaveKey('values', ['live', 'pending']);
    });

    test('includes empty values array when no values set', function () {
        $rule = $this->condition->createConditionRule(StatusConditionRule::class);

        $config = $rule->config;

        expect($config)->toHaveKey('values', []);
    });

    test('includes not-in operator', function () {
        $rule = $this->condition->createConditionRule(StatusConditionRule::class);
        $rule->operator = 'ni';
        $rule->values = ['expired'];

        $config = $rule->config;

        expect($config)
            ->toHaveKey('operator', 'ni')
            ->toHaveKey('values', ['expired']);
    });
});
