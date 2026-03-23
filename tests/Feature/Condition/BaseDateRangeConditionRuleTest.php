<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Conditions\DateCreatedConditionRule;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Entry\Conditions\ExpiryDateConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Shared\Enums\DateRangePeriod;
use CraftCms\Cms\Shared\Enums\DateRangeType;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

function createDateRangeRule(array $attributes = []): DateCreatedConditionRule
{
    $condition = new ElementCondition(Entry::class);
    $rule = $condition->createConditionRule(DateCreatedConditionRule::class);

    foreach ($attributes as $key => $value) {
        $rule->$key = $value;
    }

    return $rule;
}

function createExpiryDateRule(array $attributes = []): ExpiryDateConditionRule
{
    $condition = new ElementCondition(Entry::class);
    $rule = $condition->createConditionRule(ExpiryDateConditionRule::class);

    foreach ($attributes as $key => $value) {
        $rule->$key = $value;
    }

    return $rule;
}

function createEntryWithDate(DateTime $date): Entry
{
    $model = EntryModel::factory()->create(['postDate' => $date]);

    return Entry::find()->id($model->id)->status(null)->one();
}

beforeEach(function () {
    actingAs(User::findOne());
});

describe('matchElement', function () {
    it('matches with TYPE_TODAY', function (Closure $createDate, bool $expected) {
        $entry = createEntryWithDate($createDate());

        $rule = createDateRangeRule([
            'rangeType' => DateRangeType::Today->value,
        ]);

        expect($rule->matchElement($entry))->toBe($expected);
    })->with([
        'created today' => [fn () => DateTimeHelper::today()->modify('+12 hours'), true],
        'created yesterday' => [DateTimeHelper::yesterday(...), false],
    ]);

    it('matches with TYPE_RANGE', function (string $entryDate, array $ruleAttributes, bool $expected) {
        $entry = createEntryWithDate(new DateTime($entryDate));

        $rule = createDateRangeRule([
            'rangeType' => DateRangeType::Range->value,
            ...$ruleAttributes,
        ]);

        expect($rule->matchElement($entry))->toBe($expected);
    })->with([
        'within range' => ['2024-06-15', ['startDate' => '2024-06-01', 'endDate' => '2024-06-30'], true],
        'outside range' => ['2024-07-15', ['startDate' => '2024-06-01', 'endDate' => '2024-06-30'], false],
        'after startDate only' => ['2024-06-15', ['startDate' => '2024-01-01'], true],
        'before startDate only' => ['2023-06-15', ['startDate' => '2024-01-01'], false],
        'before endDate only' => ['2024-06-15', ['endDate' => '2024-12-31'], true],
        'after endDate only' => ['2025-06-15', ['endDate' => '2024-12-31'], false],
        'no bounds set' => ['2024-06-15', [], true],
    ]);

    it('matches with empty/notempty using ExpiryDateConditionRule', function (string $rangeType, bool $expected) {
        $model = EntryModel::factory()->create();
        $entry = Entry::find()->id($model->id)->one();

        // Entries without an expiry date have null expiryDate
        $rule = createExpiryDateRule([
            'rangeType' => $rangeType,
        ]);

        expect($entry->expiryDate)->toBeNull();
        expect($rule->matchElement($entry))->toBe($expected);
    })->with([
        'empty matches null date' => ['empty', true],
        'notempty does not match null date' => ['notempty', false],
    ]);

    it('matches with empty/notempty using DateCreatedConditionRule', function (string $rangeType, bool $expected) {
        $entry = createEntryWithDate(DateTimeHelper::now());

        // dateCreated is always set
        $rule = createDateRangeRule([
            'rangeType' => $rangeType,
        ]);

        expect($rule->matchElement($entry))->toBe($expected);
    })->with([
        'empty does not match non-null date' => ['empty', false],
        'notempty matches non-null date' => ['notempty', true],
    ]);

    it('matches with relative date types', function (string $rangeType, array $ruleAttributes, bool $expected) {
        $entry = createEntryWithDate(DateTimeHelper::now());

        $rule = createDateRangeRule([
            'rangeType' => $rangeType,
            ...$ruleAttributes,
        ]);

        expect($rule->matchElement($entry))->toBe($expected);
    })->with([
        'before 1 day from now' => [DateRangeType::Before->value, ['periodValue' => 1, 'periodType' => DateRangePeriod::DaysFromNow->value], true],
        'before 1 day ago' => [DateRangeType::Before->value, ['periodValue' => 1, 'periodType' => DateRangePeriod::DaysAgo->value], false],
        'before with no periodValue' => [DateRangeType::Before->value, ['periodValue' => null], true],
        'after 1 day ago' => [DateRangeType::After->value, ['periodValue' => 1, 'periodType' => DateRangePeriod::DaysAgo->value], true],
        'after 1 day from now' => [DateRangeType::After->value, ['periodValue' => 1, 'periodType' => DateRangePeriod::DaysFromNow->value], false],
    ]);
});

describe('modifyQuery', function () {
    it('filters entries by TYPE_RANGE with both bounds', function () {
        $entry = createEntryWithDate(new DateTime('2024-06-15'));

        $rule = createDateRangeRule([
            'rangeType' => DateRangeType::Range->value,
            'startDate' => '2024-06-01',
            'endDate' => '2024-06-30',
        ]);

        $query = Entry::find();
        $rule->modifyQuery($query);

        $results = $query->all();

        expect($results)->not->toBeEmpty()
            ->and(collect($results)->pluck('id')->toArray())->toContain($entry->id);
    });

    it('returns no results for TYPE_RANGE outside of any entry dates', function () {
        createEntryWithDate(new DateTime('2024-06-15'));

        $rule = createDateRangeRule([
            'rangeType' => DateRangeType::Range->value,
            'startDate' => '2020-01-01',
            'endDate' => '2020-01-02',
        ]);

        $query = Entry::find();
        $rule->modifyQuery($query);

        $results = $query->all();

        expect($results)->toBeEmpty();
    });

    it('does not filter when TYPE_RANGE has no bounds', function () {
        createEntryWithDate(new DateTime('2024-06-15'));
        createEntryWithDate(new DateTime('2023-01-01'));

        $rule = createDateRangeRule([
            'rangeType' => DateRangeType::Range->value,
        ]);

        $query = Entry::find();
        $rule->modifyQuery($query);

        expect($query->count())->toBeGreaterThanOrEqual(2);
    });

    it('filters with OPERATOR_NOT_EMPTY for dateCreated', function () {
        createEntryWithDate(DateTimeHelper::now());

        $rule = createDateRangeRule([
            'rangeType' => 'notempty',
        ]);

        $query = Entry::find();
        $rule->modifyQuery($query);

        expect($query->count())->toBeGreaterThanOrEqual(1);
    });

    it('filters with OPERATOR_EMPTY for expiryDate', function () {
        EntryModel::factory()->create();

        $rule = createExpiryDateRule([
            'rangeType' => 'empty',
        ]);

        $query = Entry::find();
        $rule->modifyQuery($query);

        // Entries without expiry dates should be returned
        expect($query->count())->toBeGreaterThanOrEqual(1);
    });
});

describe('getConfig', function () {
    it('includes expected keys', function (array $attributes, array $expectedKeys) {
        $rule = createDateRangeRule($attributes);
        $config = $rule->getConfig();

        foreach ($expectedKeys as $key => $value) {
            if ($value === null) {
                expect($config)->toHaveKey($key)
                    ->and($config[$key])->not->toBeNull();
            } else {
                expect($config)->toHaveKey($key, $value);
            }
        }
    })->with([
        'rangeType' => [
            ['rangeType' => DateRangeType::Range->value],
            ['rangeType' => DateRangeType::Range->value],
        ],
        'periodType and periodValue' => [
            ['rangeType' => DateRangeType::Before->value, 'periodType' => DateRangePeriod::HoursAgo->value, 'periodValue' => 5.0],
            ['periodType' => DateRangePeriod::HoursAgo->value, 'periodValue' => 5.0],
        ],
        'startDate and endDate' => [
            ['rangeType' => DateRangeType::Range->value, 'startDate' => '2024-01-01', 'endDate' => '2024-12-31'],
            ['startDate' => null, 'endDate' => null], // null means "key exists and is not null"
        ],
        'class and uid' => [
            [],
            ['class' => DateCreatedConditionRule::class, 'uid' => null],
        ],
    ]);
});

describe('config round-trip', function () {
    it('can restore a rule from config', function (array $attributes, array $expectedProperties) {
        $rule = createDateRangeRule($attributes);

        $config = $rule->getConfig();
        unset($config['class']);

        $restoredRule = new DateCreatedConditionRule($config);

        expect($restoredRule)->toBeInstanceOf(DateCreatedConditionRule::class);
        expect($restoredRule->uid)->toBe($rule->uid);

        foreach ($expectedProperties as $property) {
            expect($restoredRule->$property)->toBe($rule->$property);
        }
    })->with([
        'TYPE_RANGE with dates' => [
            ['rangeType' => DateRangeType::Range->value, 'startDate' => '2024-06-01', 'endDate' => '2024-06-30'],
            ['rangeType', 'startDate', 'endDate'],
        ],
        'TYPE_BEFORE with period' => [
            ['rangeType' => DateRangeType::Before->value, 'periodType' => DateRangePeriod::HoursAgo->value, 'periodValue' => 12.0],
            ['rangeType', 'periodType', 'periodValue'],
        ],
    ]);
});
