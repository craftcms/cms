<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Entry\Conditions\AuthorConditionRule;
use CraftCms\Cms\Entry\Conditions\AuthorGroupConditionRule;
use CraftCms\Cms\Entry\Conditions\EntryCondition;
use CraftCms\Cms\Entry\Conditions\ExpiryDateConditionRule;
use CraftCms\Cms\Entry\Conditions\FieldConditionRule;
use CraftCms\Cms\Entry\Conditions\PostDateConditionRule;
use CraftCms\Cms\Entry\Conditions\SavableConditionRule;
use CraftCms\Cms\Entry\Conditions\SectionConditionRule;
use CraftCms\Cms\Entry\Conditions\SectionTypeConditionRule;
use CraftCms\Cms\Entry\Conditions\TypeConditionRule;
use CraftCms\Cms\Entry\Conditions\ViewableConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Shared\Enums\DateRangeType;
use CraftCms\Cms\Structure\Models\Structure;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Models\UserGroup as UserGroupModel;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

describe('SectionConditionRule', function () {
    it('matches an element in the selected section', function () {
        $section1 = Section::factory()->create(['type' => SectionType::Channel]);
        $section2 = Section::factory()->create(['type' => SectionType::Channel]);
        Sections::refreshSections();

        $entry1 = EntryModel::factory()->forSection($section1)->create();
        EntryModel::factory()->forSection($section2)->create();

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

        EntryModel::factory()->forSection($section1)->create();
        $entry2 = EntryModel::factory()->forSection($section2)->create();

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

        $entry1 = EntryModel::factory()->forSection($section1)->create();
        $entry2 = EntryModel::factory()->forSection($section2)->create();

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
        EntryModel::factory()->forSection($section)->create();

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(SectionConditionRule::class);
        $rule->operator = 'notempty';

        $element = Entry::find()->one();

        expect($rule->matchElement($element))->toBeTrue();
    });

    it('modifies the query', function (string $operator, array $expected) {
        $sections = [
            'news' => Section::factory()->create(['type' => SectionType::Channel]),
            'blog' => Section::factory()->create(['type' => SectionType::Channel]),
            'pages' => Section::factory()->create(['type' => SectionType::Channel]),
            'trashed' => Section::factory()->create(['type' => SectionType::Channel]),
        ];

        $ids = array_map(fn (Section $section) => EntryModel::factory()->forSection($section)->create()->id, $sections);

        DB::table(Table::SECTIONS)->where('id', $sections['trashed']->id)->update(['dateDeleted' => now()]);
        DB::table(Table::ELEMENTS)->where('id', $ids['trashed'])->update(['dateDeleted' => now()]);
        Sections::refreshSections();

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(SectionConditionRule::class);
        $rule->operator = $operator;
        $rule->values = [$sections['news']->uid, $sections['blog']->uid];

        $query = Entry::find()->id(array_values($ids))->trashed(null);
        $query->where(fn (Builder $builder) => $rule->modifyQuery($builder, $query));

        expect($query->ids())->toEqualCanonicalizing(array_values(array_intersect_key($ids, array_flip($expected))));
    })->with([
        'in' => ['in', ['news', 'blog']],
        'not in' => ['ni', ['pages']],
        'not empty' => ['notempty', ['news', 'blog', 'pages']],
    ]);

    it('doesn’t match sectionless entries, which its query leaves out', function () {
        $section = Section::factory()->create(['type' => SectionType::Channel]);
        Sections::refreshSections();

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(SectionConditionRule::class);
        $rule->operator = 'ni';
        $rule->values = [$section->uid];

        expect($rule->matchElement(new Entry))->toBeFalse();
    });
});

it('leaves the query’s structure settings alone, since other rules in an “or” group can match other sections', function (string $ruleClass, Closure $values) {
    $structure = Section::factory()->create([
        'type' => SectionType::Structure,
        'structureId' => Structure::factory()->create()->id,
    ]);
    $channel = Section::factory()->create(['type' => SectionType::Channel]);
    Sections::refreshSections();

    $structureEntry = EntryModel::factory()->forSection($structure)->create();
    $channelEntry = EntryModel::factory()->forSection($channel)->create();

    $rule = new EntryCondition(Entry::class)->createConditionRule($ruleClass);
    $rule->operator = 'in';
    $rule->values = $values($structure);

    $query = Entry::find()->id([$structureEntry->id, $channelEntry->id]);
    $query->where(fn (Builder $builder) => $builder
        ->orWhere(fn (Builder $builder) => $rule->modifyQuery($builder, $query))
        ->orWhere('elements.id', $channelEntry->id));

    $entries = collect($query->all())->keyBy('id');

    expect($entries->keys()->all())->toEqualCanonicalizing([$structureEntry->id, $channelEntry->id])
        ->and($entries[$channelEntry->id]->structureId)->toBeNull();
})->with([
    'section' => [SectionConditionRule::class, fn (Section $structure) => [$structure->uid]],
    'section type' => [SectionTypeConditionRule::class, fn () => [SectionType::Structure->value]],
]);

describe('SectionTypeConditionRule', function () {
    beforeEach(function () {
        $this->single = EntryModel::factory()->forSection(Section::factory()->create(['type' => SectionType::Single]))->create();
        $this->channel = EntryModel::factory()->forSection(Section::factory()->create(['type' => SectionType::Channel]))->create();
        $this->structure = EntryModel::factory()->forSection(Section::factory()->create(['type' => SectionType::Structure]))->create();
        Sections::refreshSections();
    });

    it('matches and queries entries in sections of the selected types', function (string $operator, array $expected) {
        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(SectionTypeConditionRule::class);
        $rule->operator = $operator;
        $rule->values = [SectionType::Single->value, SectionType::Structure->value];

        $ids = ['single' => $this->single->id, 'channel' => $this->channel->id, 'structure' => $this->structure->id];
        $expectedIds = array_map(fn (string $key) => $ids[$key], $expected);

        $query = Entry::find()->id(array_values($ids))->status(null);
        $query->where(fn (Builder $builder) => $rule->modifyQuery($builder, $query));

        $matched = array_keys(array_filter(
            $ids,
            fn (int $id) => $rule->matchElement(Entry::find()->id($id)->status(null)->one()),
        ));

        expect($query->ids())->toEqualCanonicalizing($expectedIds)
            ->and($matched)->toEqualCanonicalizing($expected);
    })->with([
        'in' => ['in', ['single', 'structure']],
        'not in' => ['ni', ['channel']],
    ]);

    it('doesn’t match sectionless entries, which its query leaves out', function () {
        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(SectionTypeConditionRule::class);
        $rule->operator = 'ni';
        $rule->values = [SectionType::Single->value];

        expect($rule->matchElement(new Entry))->toBeFalse();
    });
});

describe('TypeConditionRule', function () {
    it('matches an element with the selected entry type', function () {
        $entryType1 = EntryType::factory()->create();
        $entryType2 = EntryType::factory()->create();

        $section = Section::factory()->withEntryTypes($entryType1, $entryType2)->create(['type' => SectionType::Channel]);

        $entry1 = EntryModel::factory()->forSection($section)->forEntryType($entryType1)->create();

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

        $section = Section::factory()->withEntryTypes($entryType1, $entryType2)->create(['type' => SectionType::Channel]);

        $entry2 = EntryModel::factory()->forSection($section)->forEntryType($entryType2)->create();

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

        $section = Section::factory()->withEntryTypes($entryType1, $entryType2)->create(['type' => SectionType::Channel]);

        $entry1 = EntryModel::factory()->forSection($section)->forEntryType($entryType1)->create();
        $entry2 = EntryModel::factory()->forSection($section)->forEntryType($entryType2)->create();

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(TypeConditionRule::class);
        $rule->operator = 'ni';
        $rule->values = [$entryType1->uid];

        $element1 = Entry::find()->id($entry1->id)->one();
        $element2 = Entry::find()->id($entry2->id)->one();

        expect($rule->matchElement($element1))->toBeFalse();
        expect($rule->matchElement($element2))->toBeTrue();
    });

    it('filters query to only entries with the selected entry type', function () {
        $entryType1 = EntryType::factory()->create();
        $entryType2 = EntryType::factory()->create();

        $section = Section::factory()->withEntryTypes($entryType1, $entryType2)->create(['type' => SectionType::Channel]);

        $entry1 = EntryModel::factory()->forSection($section)->forEntryType($entryType1)->create();
        EntryModel::factory()->forSection($section)->forEntryType($entryType2)->create();

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(TypeConditionRule::class);
        $rule->operator = 'in';
        $rule->values = [$entryType1->uid];

        $query = Entry::find();
        $query->where(fn (Builder $builder) => $rule->modifyQuery($builder, $query));

        $results = $query->all();

        expect($results)->toHaveCount(1);
        expect($results[0]->id)->toBe($entry1->id);
    });

    it('filters query to entries without the selected entry types', function () {
        $entryType1 = EntryType::factory()->create();
        $entryType2 = EntryType::factory()->create();

        $section = Section::factory()->withEntryTypes($entryType1, $entryType2)->create(['type' => SectionType::Channel]);

        $entry1 = EntryModel::factory()->forSection($section)->forEntryType($entryType1)->create();
        $entry2 = EntryModel::factory()->forSection($section)->forEntryType($entryType2)->create();

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(TypeConditionRule::class);
        $rule->operator = 'ni';
        $rule->values = [$entryType1->uid];

        $query = Entry::find()->id([$entry1->id, $entry2->id]);
        $query->where(fn (Builder $builder) => $rule->modifyQuery($builder, $query));

        expect($query->ids())->toBe([$entry2->id]);

        $rule->values = EntryTypes::getAllEntryTypes()->pluck('uid')->all();

        $query = Entry::find()->id([$entry1->id, $entry2->id]);
        $query->where(fn (Builder $builder) => $rule->modifyQuery($builder, $query));

        expect($query->ids())->toBe([]);
    });
});

describe('AuthorConditionRule', function () {
    it('matches an element with the selected author', function () {
        $author = UserModel::factory()->create();

        $entry = EntryModel::factory()->create();
        Sections::refreshSections();
        $element = Entry::find()->id($entry->id)->one();
        $element->setAuthorId($author->id);
        Elements::saveElement($element);

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
        Elements::saveElement($element);

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
        Elements::saveElement($element1);

        $element2 = Entry::find()->id($entry2->id)->one();
        $element2->setAuthorId($author2->id);
        Elements::saveElement($element2);

        $condition = new EntryCondition(Entry::class);
        $condition->forQuery = true;
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
        $rule->rangeType = DateRangeType::Range->value;
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
        $condition->forQuery = true;
        $rule = $condition->createConditionRule(PostDateConditionRule::class);
        $rule->rangeType = DateRangeType::Range->value;
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

    it('filters query to only entries with an expiry date', function () {
        $expiredEntry = EntryModel::factory()->expired()->create();
        EntryModel::factory()->create();

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(ExpiryDateConditionRule::class);
        $rule->rangeType = 'notempty';

        $query = Entry::find()->status(null);
        $query->where(fn (Builder $builder) => $rule->modifyQuery($builder, $query));

        $results = $query->all();

        expect($results)->toHaveCount(1);
        expect($results[0]->id)->toBe($expiredEntry->id);
    });
});

describe('AuthorGroupConditionRule', function () {
    beforeEach(function () {
        DB::table(Table::USERGROUPS)->delete();
        Edition::set(Edition::Pro);
    });

    it('isSelectable returns true when groups exist', function () {
        UserGroupModel::factory()->create();

        expect(AuthorGroupConditionRule::isSelectable())->toBeTrue();
    });

    it('isSelectable returns false when no groups exist', function () {
        expect(AuthorGroupConditionRule::isSelectable())->toBeFalse();
    });

    it('matches an element whose author is in the selected group', function () {
        $group = UserGroupModel::factory()->create();
        $author = UserModel::factory()->create();
        $group->users()->attach($author);

        $entry = EntryModel::factory()->create();
        Sections::refreshSections();
        $element = Entry::find()->id($entry->id)->one();
        $element->setAuthorId($author->id);
        Elements::saveElement($element);

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(AuthorGroupConditionRule::class);
        $rule->operator = 'in';
        $rule->values = [$group->uid];

        $element = Entry::find()->id($entry->id)->one();

        expect($rule->matchElement($element))->toBeTrue();
    });

    it('does not match an element whose author is not in the selected group', function () {
        $group = UserGroupModel::factory()->create();
        $author = UserModel::factory()->create();

        $entry = EntryModel::factory()->create();
        Sections::refreshSections();
        $element = Entry::find()->id($entry->id)->one();
        $element->setAuthorId($author->id);
        Elements::saveElement($element);

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(AuthorGroupConditionRule::class);
        $rule->operator = 'in';
        $rule->values = [$group->uid];

        $element = Entry::find()->id($entry->id)->one();

        expect($rule->matchElement($element))->toBeFalse();
    });

    it('filters query to only entries authored by a member of the selected group', function () {
        $group = UserGroupModel::factory()->create();
        $author = UserModel::factory()->create();
        $otherAuthor = UserModel::factory()->create();
        $group->users()->attach($author);

        $entry1 = EntryModel::factory()->create();
        $entry2 = EntryModel::factory()->create();
        Sections::refreshSections();

        $element1 = Entry::find()->id($entry1->id)->one();
        $element1->setAuthorId($author->id);
        Elements::saveElement($element1);

        $element2 = Entry::find()->id($entry2->id)->one();
        $element2->setAuthorId($otherAuthor->id);
        Elements::saveElement($element2);

        $condition = new EntryCondition(Entry::class);
        $condition->forQuery = true;
        $rule = $condition->createConditionRule(AuthorGroupConditionRule::class);
        $rule->operator = 'in';
        $rule->values = [$group->uid];

        $condition->addConditionRule($rule);

        $query = Entry::find();
        $condition->modifyQuery($query);

        $results = $query->all();

        expect($results)->toHaveCount(1);
        expect($results[0]->id)->toBe($entry1->id);
    });
});

describe('SavableConditionRule', function () {
    beforeEach(function () {
        actingAs(UserModel::factory()->create(['admin' => true]));
    });

    it('matches an element the acting user can save', function () {
        $entry = EntryModel::factory()->create();
        Sections::refreshSections();
        $element = Entry::find()->id($entry->id)->one();

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(SavableConditionRule::class);
        $rule->value = true;

        expect($rule->matchElement($element))->toBeTrue();
    });

    it('modifies query without throwing and returns results for the acting user', function () {
        EntryModel::factory()->create();
        Sections::refreshSections();

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(SavableConditionRule::class);
        $rule->value = true;

        $query = Entry::find();
        $query->where(fn (Builder $builder) => $rule->modifyQuery($builder, $query));

        expect($query->count())->toBeGreaterThanOrEqual(1);
    });
});

describe('ViewableConditionRule', function () {
    beforeEach(function () {
        actingAs(UserModel::factory()->create(['admin' => true]));
    });

    it('matches an element the acting user can view', function () {
        $entry = EntryModel::factory()->create();
        Sections::refreshSections();
        $element = Entry::find()->id($entry->id)->one();

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(ViewableConditionRule::class);
        $rule->value = true;

        expect($rule->matchElement($element))->toBeTrue();
    });

    it('modifies query without throwing and returns results for the acting user', function () {
        EntryModel::factory()->create();
        Sections::refreshSections();

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(ViewableConditionRule::class);
        $rule->value = true;

        $query = Entry::find();
        $query->where(fn (Builder $builder) => $rule->modifyQuery($builder, $query));

        expect($query->count())->toBeGreaterThanOrEqual(1);
    });
});

describe('FieldConditionRule', function () {
    it('matches a top-level entry with the empty operator', function () {
        $entry = EntryModel::factory()->create();
        Sections::refreshSections();
        $element = Entry::find()->id($entry->id)->one();

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(FieldConditionRule::class);
        $rule->operator = 'empty';

        expect($rule->matchElement($element))->toBeTrue();
    });

    it('does not match a top-level entry with the not_empty operator', function () {
        $entry = EntryModel::factory()->create();
        Sections::refreshSections();
        $element = Entry::find()->id($entry->id)->one();

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(FieldConditionRule::class);
        $rule->operator = 'notempty';

        expect($rule->matchElement($element))->toBeFalse();
    });

    it('filters query to top-level entries with the empty operator', function () {
        $entry = EntryModel::factory()->create();
        Sections::refreshSections();

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(FieldConditionRule::class);
        $rule->operator = 'empty';

        $query = Entry::find();
        $query->where(fn (Builder $builder) => $rule->modifyQuery($builder, $query));

        $results = $query->all();

        expect(collect($results)->pluck('id')->toArray())->toContain($entry->id);
    });
});
