<?php

declare(strict_types=1);

use craft\helpers\DateRange;
use CraftCms\Cms\Entry\Conditions\AuthorConditionRule;
use CraftCms\Cms\Entry\Conditions\EntryCondition;
use CraftCms\Cms\Entry\Conditions\ExpiryDateConditionRule;
use CraftCms\Cms\Entry\Conditions\PostDateConditionRule;
use CraftCms\Cms\Entry\Conditions\SectionConditionRule;
use CraftCms\Cms\Entry\Conditions\TypeConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Models\User as UserModel;

describe('SectionConditionRule', function () {
    it('matches an element in the selected section', function () {
        $section1 = Section::factory()->create(['type' => SectionType::Channel]);
        $section2 = Section::factory()->create(['type' => SectionType::Channel]);
        Sections::refreshSections();

        $entry1 = EntryModel::factory()->create(['sectionId' => $section1->id]);
        EntryModel::factory()->create(['sectionId' => $section2->id]);

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(SectionConditionRule::class);
        $rule->operator = 'in';
        $rule->values = [$section1->uid];

        $element1 = Entry::find()->id($entry1->id)->one();

        expect($rule->matchElement($element1))->toBeTrue();
    });

    it('does not match an element in a different section', function () {
        $section1 = Section::factory()->create(['type' => SectionType::Channel]);
        $section2 = Section::factory()->create(['type' => SectionType::Channel]);
        Sections::refreshSections();

        EntryModel::factory()->create(['sectionId' => $section1->id]);
        $entry2 = EntryModel::factory()->create(['sectionId' => $section2->id]);

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(SectionConditionRule::class);
        $rule->operator = 'in';
        $rule->values = [$section1->uid];

        $element2 = Entry::find()->id($entry2->id)->one();

        expect($rule->matchElement($element2))->toBeFalse();
    });

    it('supports not_in operator to exclude sections', function () {
        $section1 = Section::factory()->create(['type' => SectionType::Channel]);
        $section2 = Section::factory()->create(['type' => SectionType::Channel]);
        Sections::refreshSections();

        $entry1 = EntryModel::factory()->create(['sectionId' => $section1->id]);
        $entry2 = EntryModel::factory()->create(['sectionId' => $section2->id]);

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(SectionConditionRule::class);
        $rule->operator = 'ni';
        $rule->values = [$section1->uid];

        $element1 = Entry::find()->id($entry1->id)->one();
        $element2 = Entry::find()->id($entry2->id)->one();

        expect($rule->matchElement($element1))->toBeFalse();
        expect($rule->matchElement($element2))->toBeTrue();
    });

    it('supports not_empty operator to match entries with any section', function () {
        $section = Section::factory()->create(['type' => SectionType::Channel]);
        Sections::refreshSections();
        EntryModel::factory()->create(['sectionId' => $section->id]);

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(SectionConditionRule::class);
        $rule->operator = 'notempty';

        $element = Entry::find()->one();

        expect($rule->matchElement($element))->toBeTrue();
    });

    it('modifies query with not_empty operator', function () {
        $section = Section::factory()->create(['type' => SectionType::Channel]);
        Sections::refreshSections();
        EntryModel::factory()->create(['sectionId' => $section->id]);

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(SectionConditionRule::class);
        $rule->operator = 'notempty';

        $query = Entry::find();
        $rule->modifyQuery($query);

        expect($query->count())->toBeGreaterThanOrEqual(1);
    });
});

describe('TypeConditionRule', function () {
    it('matches an element with the selected entry type', function () {
        $entryType1 = EntryType::factory()->create();
        $entryType2 = EntryType::factory()->create();

        $section = Section::factory()->create(['type' => SectionType::Channel]);
        $section->entryTypes()->attach($entryType1, ['sortOrder' => 1]);
        $section->entryTypes()->attach($entryType2, ['sortOrder' => 2]);

        $entry1 = EntryModel::factory()->create(['sectionId' => $section->id, 'typeId' => $entryType1->id]);

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(TypeConditionRule::class);
        $rule->operator = 'in';
        $rule->values = [$entryType1->uid];

        $element1 = Entry::find()->id($entry1->id)->one();

        expect($rule->matchElement($element1))->toBeTrue();
    });

    it('does not match an element with a different entry type', function () {
        $entryType1 = EntryType::factory()->create();
        $entryType2 = EntryType::factory()->create();

        $section = Section::factory()->create(['type' => SectionType::Channel]);
        $section->entryTypes()->attach($entryType1, ['sortOrder' => 1]);
        $section->entryTypes()->attach($entryType2, ['sortOrder' => 2]);

        $entry2 = EntryModel::factory()->create(['sectionId' => $section->id, 'typeId' => $entryType2->id]);

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(TypeConditionRule::class);
        $rule->operator = 'in';
        $rule->values = [$entryType1->uid];

        $element2 = Entry::find()->id($entry2->id)->one();

        expect($rule->matchElement($element2))->toBeFalse();
    });

    it('supports not_in operator to exclude entry types', function () {
        $entryType1 = EntryType::factory()->create();
        $entryType2 = EntryType::factory()->create();

        $section = Section::factory()->create(['type' => SectionType::Channel]);
        $section->entryTypes()->attach($entryType1, ['sortOrder' => 1]);
        $section->entryTypes()->attach($entryType2, ['sortOrder' => 2]);

        $entry1 = EntryModel::factory()->create(['sectionId' => $section->id, 'typeId' => $entryType1->id]);
        $entry2 = EntryModel::factory()->create(['sectionId' => $section->id, 'typeId' => $entryType2->id]);

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(TypeConditionRule::class);
        $rule->operator = 'ni';
        $rule->values = [$entryType1->uid];

        $element1 = Entry::find()->id($entry1->id)->one();
        $element2 = Entry::find()->id($entry2->id)->one();

        expect($rule->matchElement($element1))->toBeFalse();
        expect($rule->matchElement($element2))->toBeTrue();
    });
});

describe('AuthorConditionRule', function () {
    it('matches an element with the selected author', function () {
        $author = UserModel::factory()->create();

        $entry = EntryModel::factory()->create();
        Sections::refreshSections();
        $element = Entry::find()->id($entry->id)->one();
        $element->setAuthorId($author->id);
        Craft::$app->getElements()->saveElement($element);

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(AuthorConditionRule::class);
        $rule->setElementIds([$author->id]);

        $element = Entry::find()->id($entry->id)->one();

        expect($rule->matchElement($element))->toBeTrue();
    });

    it('does not match an element with a different author', function () {
        $author1 = UserModel::factory()->create();
        $author2 = UserModel::factory()->create();

        $entry = EntryModel::factory()->create();
        Sections::refreshSections();
        $element = Entry::find()->id($entry->id)->one();
        $element->setAuthorId($author1->id);
        Craft::$app->getElements()->saveElement($element);

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(AuthorConditionRule::class);
        $rule->setElementIds([$author2->id]);

        $element = Entry::find()->id($entry->id)->one();

        expect($rule->matchElement($element))->toBeFalse();
    });

    it('filters query to only entries by the selected author', function () {
        $author1 = UserModel::factory()->create();
        $author2 = UserModel::factory()->create();

        $entry1 = EntryModel::factory()->create();
        $entry2 = EntryModel::factory()->create();
        Sections::refreshSections();

        $element1 = Entry::find()->id($entry1->id)->one();
        $element1->setAuthorId($author1->id);
        Craft::$app->getElements()->saveElement($element1);

        $element2 = Entry::find()->id($entry2->id)->one();
        $element2->setAuthorId($author2->id);
        Craft::$app->getElements()->saveElement($element2);

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(AuthorConditionRule::class);
        $rule->setElementIds([$author1->id]);

        $condition->addConditionRule($rule);

        $query = Entry::find();
        $condition->modifyQuery($query);

        $results = $query->all();

        expect($results)->toHaveCount(1);
        expect($results[0]->getAuthorId())->toBe($author1->id);
    });
});

describe('PostDateConditionRule', function () {
    it('matchElement with PostDateConditionRule', function (string $postDate, bool $expected) {
        $entry = EntryModel::factory()->create([
            'postDate' => new DateTime($postDate),
        ]);

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(PostDateConditionRule::class);
        $rule->rangeType = DateRange::TYPE_RANGE;
        $rule->startDate = '2025-06-01';
        $rule->endDate = '2025-06-30';

        $element = Entry::find()->id($entry->id)->status(null)->one();

        expect($rule->matchElement($element))->toBe($expected);
    })->with([
        'matches element with post date in range' => ['2025-06-15', true],
        'does not match element with post date outside range' => ['2025-03-01', false],
    ]);

    it('filters query by post date range', function () {
        EntryModel::factory()->create([
            'postDate' => new DateTime('2025-06-15'),
        ]);

        EntryModel::factory()->create([
            'postDate' => new DateTime('2025-03-01'),
        ]);

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(PostDateConditionRule::class);
        $rule->rangeType = DateRange::TYPE_RANGE;
        $rule->startDate = '2025-06-01';
        $rule->endDate = '2025-06-30';

        $condition->addConditionRule($rule);

        $query = Entry::find()->status(null);
        $condition->modifyQuery($query);

        expect($query->count())->toBe(1);
    });
});

describe('ExpiryDateConditionRule', function () {
    it('matchElement with ExpiryDateConditionRule', function (Closure $createEntry, string $rangeType, bool $useStatusNull, bool $expected) {
        $entry = $createEntry();

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(ExpiryDateConditionRule::class);
        $rule->rangeType = $rangeType;

        $query = Entry::find()->id($entry->id);
        if ($useStatusNull) {
            $query->status(null);
        }
        $element = $query->one();

        expect($rule->matchElement($element))->toBe($expected);
    })->with([
        'notempty matches entry with expiry date' => [fn () => EntryModel::factory()->expired()->create(), 'notempty', true, true],
        'notempty does not match entry without expiry date' => [fn () => EntryModel::factory()->create(), 'notempty', false, false],
        'empty matches entry without expiry date' => [fn () => EntryModel::factory()->create(), 'empty', false, true],
    ]);
});
