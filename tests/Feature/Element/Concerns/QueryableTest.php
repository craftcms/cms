<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;

beforeEach(function () {
    $this->entry1 = EntryModel::factory()->create();
    $this->entry2 = EntryModel::factory()->create();
    $this->entry3 = EntryModel::factory()->create();
});

describe('find', function () {
    test('returns an ElementQuery instance', function () {
        $query = Entry::find();

        expect($query)->toBeInstanceOf(ElementQuery::class);
    });

    test('returns query for the correct element class', function () {
        $query = Entry::find();

        expect($query->elementType)->toBe(Entry::class);
    });
});

describe('findOne', function () {
    test('returns a single element by ID', function () {
        $result = Entry::findOne($this->entry1->id);

        expect($result)->toBeInstanceOf(Entry::class)
            ->and($result->id)->toBe($this->entry1->id);
    });

    test('returns null when element not found', function () {
        $result = Entry::findOne(99999999);

        expect($result)->toBeNull();
    });

    test('accepts criteria array', function () {
        $result = Entry::findOne(['id' => $this->entry2->id]);

        expect($result)->toBeInstanceOf(Entry::class)
            ->and($result->id)->toBe($this->entry2->id);
    });
});

describe('findAll', function () {
    test('returns all elements when no criteria', function () {
        $results = Entry::findAll();

        expect($results)->toBeArray()
            ->and(count($results))->toBeGreaterThanOrEqual(3);
    });

    test('returns elements matching criteria', function () {
        $results = Entry::findAll([
            'id' => [$this->entry1->id, $this->entry2->id],
        ]);

        expect($results)->toBeArray()
            ->toHaveCount(2);
    });

    test('returns empty array when no matches', function () {
        $results = Entry::findAll(['id' => 99999999]);

        expect($results)->toBeArray()
            ->toBeEmpty();
    });
});

describe('get', function () {
    test('returns element by ID with all draft/revision states', function () {
        $result = Entry::get($this->entry1->id);

        expect($result)->toBeInstanceOf(Entry::class)
            ->and($result->id)->toBe($this->entry1->id);
    });

    test('accepts string ID', function () {
        $result = Entry::get((string) $this->entry1->id);

        expect($result)->toBeInstanceOf(Entry::class)
            ->and($result->id)->toBe($this->entry1->id);
    });

    test('returns null when element not found', function () {
        $result = Entry::get(99999999);

        expect($result)->toBeNull();
    });
});

describe('createCondition', function () {
    test('returns ElementCondition instance', function () {
        $condition = Element::createCondition();

        expect($condition)->toBeInstanceOf(ElementConditionInterface::class);
    });

    test('condition is configured for the element type', function () {
        $condition = Element::createCondition();

        expect($condition->elementType)->toBe(Element::class);
    });
});
