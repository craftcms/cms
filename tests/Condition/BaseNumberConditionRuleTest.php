<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Conditions\IdConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;

beforeEach(function () {
    $this->entries = collect([
        EntryModel::factory()->create(),
        EntryModel::factory()->create(),
        EntryModel::factory()->create(),
    ]);

    $this->elements = $this->entries->map(
        fn (EntryModel $entry) => Entry::find()->id($entry->id)->one(),
    );

    $this->condition = new ElementCondition(Entry::class);
});

function createRule(ElementCondition $condition, string $operator, string $value = '', string $maxValue = ''): IdConditionRule
{
    /** @var IdConditionRule $rule */
    $rule = $condition->createConditionRule(IdConditionRule::class);
    $rule->operator = $operator;
    $rule->value = $value;
    $rule->maxValue = $maxValue;

    return $rule;
}

describe('matchElement', function () {
    test('equals operator matches element with same ID', function () {
        $element = $this->elements->first();
        $rule = createRule($this->condition, '=', (string) $element->id);

        expect($rule->matchElement($element))->toBeTrue();
    });

    test('equals operator does not match element with different ID', function () {
        $element = $this->elements->first();
        $other = $this->elements->last();
        $rule = createRule($this->condition, '=', (string) $other->id);

        expect($rule->matchElement($element))->toBeFalse();
    });

    test('not equals operator does not match element with same ID', function () {
        $element = $this->elements->first();
        $rule = createRule($this->condition, '!=', (string) $element->id);

        expect($rule->matchElement($element))->toBeFalse();
    });

    test('not equals operator matches element with different ID', function () {
        $element = $this->elements->first();
        $other = $this->elements->last();
        $rule = createRule($this->condition, '!=', (string) $other->id);

        expect($rule->matchElement($element))->toBeTrue();
    });

    test('less than operator', function () {
        $element = $this->elements->first();
        $rule = createRule($this->condition, '<', (string) ($element->id + 1));

        expect($rule->matchElement($element))->toBeTrue();

        $rule = createRule($this->condition, '<', (string) $element->id);

        expect($rule->matchElement($element))->toBeFalse();
    });

    test('less than or equal operator', function () {
        $element = $this->elements->first();
        $rule = createRule($this->condition, '<=', (string) $element->id);

        expect($rule->matchElement($element))->toBeTrue();

        $rule = createRule($this->condition, '<=', (string) ($element->id - 1));

        expect($rule->matchElement($element))->toBeFalse();
    });

    test('greater than operator', function () {
        $element = $this->elements->first();
        $rule = createRule($this->condition, '>', (string) ($element->id - 1));

        expect($rule->matchElement($element))->toBeTrue();

        $rule = createRule($this->condition, '>', (string) $element->id);

        expect($rule->matchElement($element))->toBeFalse();
    });

    test('greater than or equal operator', function () {
        $element = $this->elements->first();
        $rule = createRule($this->condition, '>=', (string) $element->id);

        expect($rule->matchElement($element))->toBeTrue();

        $rule = createRule($this->condition, '>=', (string) ($element->id + 1));

        expect($rule->matchElement($element))->toBeFalse();
    });

    test('between operator matches when value is within bounds', function () {
        $element = $this->elements[1];
        $min = (string) $this->elements->first()->id;
        $max = (string) $this->elements->last()->id;

        $rule = createRule($this->condition, 'between', $min, $max);

        expect($rule->matchElement($element))->toBeTrue();
    });

    test('between operator matches when value equals lower bound', function () {
        $element = $this->elements->first();
        $rule = createRule($this->condition, 'between', (string) $element->id, (string) $this->elements->last()->id);

        expect($rule->matchElement($element))->toBeTrue();
    });

    test('between operator matches when value equals upper bound', function () {
        $element = $this->elements->last();
        $rule = createRule($this->condition, 'between', (string) $this->elements->first()->id, (string) $element->id);

        expect($rule->matchElement($element))->toBeTrue();
    });

    test('between operator does not match when value is below lower bound', function () {
        $element = $this->elements->first();
        $rule = createRule($this->condition, 'between', (string) ($element->id + 1), (string) $this->elements->last()->id);

        expect($rule->matchElement($element))->toBeFalse();
    });

    test('between operator does not match when value is above upper bound', function () {
        $element = $this->elements->last();
        $rule = createRule($this->condition, 'between', (string) $this->elements->first()->id, (string) ($element->id - 1));

        expect($rule->matchElement($element))->toBeFalse();
    });

    test('between operator with only min set matches values at or above min', function () {
        $element = $this->elements->last();
        $rule = createRule($this->condition, 'between', (string) $this->elements->first()->id);

        expect($rule->matchElement($element))->toBeTrue();

        $lowElement = $this->elements->first();
        $rule = createRule($this->condition, 'between', (string) ($lowElement->id + 1));

        expect($rule->matchElement($lowElement))->toBeFalse();
    });

    test('between operator with only max set matches values at or below max', function () {
        $element = $this->elements->first();
        $rule = createRule($this->condition, 'between', '', (string) $this->elements->last()->id);

        expect($rule->matchElement($element))->toBeTrue();

        $highElement = $this->elements->last();
        $rule = createRule($this->condition, 'between', '', (string) ($highElement->id - 1));

        expect($rule->matchElement($highElement))->toBeFalse();
    });

    test('between operator with neither bound set matches everything', function () {
        $rule = createRule($this->condition, 'between');

        $this->elements->each(function ($element) use ($rule) {
            expect($rule->matchElement($element))->toBeTrue();
        });
    });

    test('empty value with non-between operator matches everything', function () {
        $rule = createRule($this->condition, '=', '');

        $this->elements->each(function ($element) use ($rule) {
            expect($rule->matchElement($element))->toBeTrue();
        });
    });
});

describe('modifyQuery', function () {
    test('equals operator filters to matching entry', function () {
        $entry = $this->entries->first();
        $rule = createRule($this->condition, '=', (string) $entry->id);

        $query = Entry::find();
        $rule->modifyQuery($query);

        $results = $query->all();

        expect($results)->toHaveCount(1)
            ->and($results[0]->id)->toBe($entry->id);
    });

    test('not equals operator excludes matching entry', function () {
        $entry = $this->entries->first();
        $rule = createRule($this->condition, '!=', (string) $entry->id);

        $query = Entry::find();
        $rule->modifyQuery($query);

        $results = $query->all();

        expect($results)->toHaveCount(2)
            ->and(collect($results)->pluck('id')->toArray())->not->toContain($entry->id);
    });

    test('greater than operator filters correctly', function () {
        $entry = $this->entries->first();
        $rule = createRule($this->condition, '>', (string) $entry->id);

        $query = Entry::find();
        $rule->modifyQuery($query);

        $results = $query->all();

        expect(collect($results)->every(fn ($el) => $el->id > $entry->id))->toBeTrue()
            ->and($results)->toHaveCount(2);
    });

    test('less than or equal operator filters correctly', function () {
        $entry = $this->entries[1];
        $rule = createRule($this->condition, '<=', (string) $entry->id);

        $query = Entry::find();
        $rule->modifyQuery($query);

        $results = $query->all();

        expect(collect($results)->every(fn ($el) => $el->id <= $entry->id))->toBeTrue()
            ->and($results)->toHaveCount(2);
    });

    test('between operator with both bounds filters correctly', function () {
        $min = (string) $this->entries->first()->id;
        $max = (string) $this->entries->last()->id;
        $rule = createRule($this->condition, 'between', $min, $max);

        $query = Entry::find();
        $rule->modifyQuery($query);

        $results = $query->all();

        expect($results)->toHaveCount(3);
    });

    test('between operator with only min filters correctly', function () {
        $min = (string) $this->entries[1]->id;
        $rule = createRule($this->condition, 'between', $min);

        $query = Entry::find();
        $rule->modifyQuery($query);

        $results = $query->all();

        expect($results)->toHaveCount(2)
            ->and(collect($results)->every(fn ($el) => $el->id >= (int) $min))->toBeTrue();
    });

    test('between operator with only max filters correctly', function () {
        $max = (string) $this->entries[1]->id;
        $rule = createRule($this->condition, 'between', '', $max);

        $query = Entry::find();
        $rule->modifyQuery($query);

        $results = $query->all();

        expect(collect($results)->every(fn ($el) => $el->id <= (int) $max))->toBeTrue();
    });

    test('between operator with neither bound returns all entries', function () {
        $rule = createRule($this->condition, 'between');

        $query = Entry::find();
        $rule->modifyQuery($query);

        $results = $query->all();

        expect(count($results))->toBeGreaterThanOrEqual(3);
    });
});

describe('getConfig', function () {
    it('includes expected keys in config', function (string $operator, string $value, string $maxValue, string $key, mixed $expectedValue) {
        $rule = createRule($this->condition, $operator, $value, $maxValue);
        $config = $rule->getConfig();

        expect($config)->toHaveKey($key, $expectedValue);
    })->with([
        'maxValue' => ['between', '10', '20', 'maxValue', '20'],
        'step' => ['=', '5', '', 'step', 1],
        'value' => ['=', '42', '', 'value', '42'],
        'operator' => ['between', '1', '10', 'operator', 'between'],
    ]);
});

describe('supportsProjectConfig', function () {
    test('returns false for IdConditionRule', function () {
        expect(IdConditionRule::supportsProjectConfig())->toBeFalse();
    });
});
