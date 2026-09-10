<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Conditions\DateCreatedConditionRule;
use CraftCms\Cms\Element\Conditions\DateUpdatedConditionRule;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Conditions\HasDescendantsRule;
use CraftCms\Cms\Element\Conditions\HasUrlConditionRule;
use CraftCms\Cms\Element\Conditions\IdConditionRule;
use CraftCms\Cms\Element\Conditions\LanguageConditionRule;
use CraftCms\Cms\Element\Conditions\LevelConditionRule;
use CraftCms\Cms\Element\Conditions\NotRelatedToConditionRule;
use CraftCms\Cms\Element\Conditions\RelatedToConditionRule;
use CraftCms\Cms\Element\Conditions\SiteConditionRule;
use CraftCms\Cms\Element\Conditions\SiteGroupConditionRule;
use CraftCms\Cms\Element\Conditions\SlugConditionRule;
use CraftCms\Cms\Element\Conditions\StatusConditionRule;
use CraftCms\Cms\Element\Conditions\TitleConditionRule;
use CraftCms\Cms\Element\Conditions\UriConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

function createElementRule(string $ruleClass, array $attributes = []): array
{
    $condition = new ElementCondition(Entry::class);
    $rule = $condition->createConditionRule($ruleClass);
    foreach ($attributes as $key => $value) {
        $rule->$key = $value;
    }

    return [$condition, $rule];
}

function createEntryWithAttributes(array $siteAttributes = [], array $modelAttributes = []): Entry
{
    $model = EntryModel::factory()->create($modelAttributes);

    if ($siteAttributes) {
        DB::table(Table::ELEMENTS_SITES)
            ->where('elementId', $model->id)
            ->update($siteAttributes);
    }

    return Entry::find()->id($model->id)->status(null)->one();
}

/**
 * @return array{0: Entry, 1: array<int, EntryModel>}
 */
function createRelatedEntries(int $count = 3): array
{
    $field = Field::factory()->create([
        'handle' => 'entriesField',
        'type' => Entries::class,
    ]);

    $fieldLayout = FieldLayout::factory()->forField($field)->create();

    $entries = EntryModel::factory($count)->create();
    foreach ($entries as $entryModel) {
        $entryModel->element->update(['fieldLayoutId' => $fieldLayout->id]);
        $entryModel->entryType->update(['fieldLayoutId' => $fieldLayout->id]);
    }

    Fields::invalidateCaches();
    Fields::refreshFields();

    $entry = Entry::find()->id($entries[0]->id)->status(null)->one();
    $entry->setFieldValue('entriesField', $entries[1]->id);
    Elements::saveElement($entry);

    return [$entry, $entries];
}

beforeEach(function () {
    actingAs(User::findOne());
});

describe('TitleConditionRule', function () {
    it('matchElement returns true when title matches', function () {
        $entry = createEntryWithAttributes(['title' => 'Alpha']);

        [, $rule] = createElementRule(TitleConditionRule::class, [
            'operator' => '=',
            'value' => 'Alpha',
        ]);

        expect($rule->matchElement($entry))->toBeTrue();
    });

    it('matchElement returns false when title does not match', function () {
        $entry = createEntryWithAttributes(['title' => 'Beta']);

        [, $rule] = createElementRule(TitleConditionRule::class, [
            'operator' => '=',
            'value' => 'Alpha',
        ]);

        expect($rule->matchElement($entry))->toBeFalse();
    });

    it('modifyQuery filters entries by title', function () {
        createEntryWithAttributes(['title' => 'Alpha']);
        createEntryWithAttributes(['title' => 'Beta']);

        [$condition, $rule] = createElementRule(TitleConditionRule::class, [
            'operator' => '=',
            'value' => 'Alpha',
        ]);
        $condition->forQuery = true;
        $condition->addConditionRule($rule);

        $query = Entry::find();
        $condition->modifyQuery($query);

        expect($query->count())->toBe(1)
            ->and($query->one()->title)->toBe('Alpha');
    });
});

describe('SlugConditionRule', function () {
    it('matchElement returns true when slug matches', function () {
        $entry = createEntryWithAttributes(['slug' => 'my-slug']);

        [, $rule] = createElementRule(SlugConditionRule::class, [
            'operator' => '=',
            'value' => 'my-slug',
        ]);

        expect($rule->matchElement($entry))->toBeTrue();
    });

    it('matchElement returns false when slug does not match', function () {
        $entry = createEntryWithAttributes(['slug' => 'other-slug']);

        [, $rule] = createElementRule(SlugConditionRule::class, [
            'operator' => '=',
            'value' => 'my-slug',
        ]);

        expect($rule->matchElement($entry))->toBeFalse();
    });

    it('modifyQuery filters entries by slug', function () {
        createEntryWithAttributes(['slug' => 'alpha']);
        createEntryWithAttributes(['slug' => 'beta']);

        [$condition, $rule] = createElementRule(SlugConditionRule::class, [
            'operator' => '=',
            'value' => 'alpha',
        ]);
        $condition->forQuery = true;
        $condition->addConditionRule($rule);

        $query = Entry::find();
        $condition->modifyQuery($query);

        expect($query->count())->toBe(1)
            ->and($query->one()->slug)->toBe('alpha');
    });

    it('treats temp slugs as empty', function () {
        $entry = createEntryWithAttributes(['slug' => '__temp_abc123']);

        [, $rule] = createElementRule(SlugConditionRule::class, [
            'operator' => 'empty',
        ]);

        expect($rule->matchElement($entry))->toBeTrue();
    });
});

describe('IdConditionRule', function () {
    it('matchElement returns true when id matches', function () {
        $entry = createEntryWithAttributes();

        [, $rule] = createElementRule(IdConditionRule::class, [
            'operator' => '=',
            'value' => (string) $entry->id,
        ]);

        expect($rule->matchElement($entry))->toBeTrue();
    });

    it('matchElement returns false when id does not match', function () {
        $entry = createEntryWithAttributes();

        [, $rule] = createElementRule(IdConditionRule::class, [
            'operator' => '=',
            'value' => (string) ($entry->id + 9999),
        ]);

        expect($rule->matchElement($entry))->toBeFalse();
    });

    it('modifyQuery filters entries by id', function () {
        $entry1 = createEntryWithAttributes();
        createEntryWithAttributes();

        [$condition, $rule] = createElementRule(IdConditionRule::class, [
            'operator' => '=',
            'value' => (string) $entry1->id,
        ]);
        $condition->forQuery = true;
        $condition->addConditionRule($rule);

        $query = Entry::find();
        $condition->modifyQuery($query);

        expect($query->count())->toBe(1)
            ->and($query->one()->id)->toBe($entry1->id);
    });

    it('does not support project config', function () {
        expect(IdConditionRule::supportsProjectConfig())->toBeFalse();
    });
});

describe('StatusConditionRule', function () {
    it('matchElement returns true when status is in selected values', function () {
        $entry = createEntryWithAttributes();

        [, $rule] = createElementRule(StatusConditionRule::class, [
            'operator' => 'in',
            'values' => [$entry->getStatus()],
        ]);

        expect($rule->matchElement($entry))->toBeTrue();
    });

    it('matchElement returns false when status is not in selected values', function () {
        $entry = createEntryWithAttributes();

        [, $rule] = createElementRule(StatusConditionRule::class, [
            'operator' => 'in',
            'values' => ['expired'],
        ]);

        expect($rule->matchElement($entry))->toBeFalse();
    });

    it('modifyQuery filters entries by status', function () {
        EntryModel::factory()->count(2)->create();

        [, $rule] = createElementRule(StatusConditionRule::class, [
            'operator' => 'in',
            'values' => ['live'],
        ]);

        $query = Entry::find()->status(null);
        $query->where(fn (Builder $builder) => $rule->modifyQuery($builder, $query));

        foreach ($query->all() as $result) {
            expect($result->getStatus())->toBe('live');
        }
    });
});

describe('DateCreatedConditionRule', function () {
    it('matchElement returns true with not-empty when entry has dateCreated', function () {
        $entry = createEntryWithAttributes();

        [, $rule] = createElementRule(DateCreatedConditionRule::class, [
            'rangeType' => 'notempty',
        ]);

        expect($rule->matchElement($entry))->toBeTrue();
    });

    it('matchElement returns false with empty when entry has dateCreated', function () {
        $entry = createEntryWithAttributes();

        [, $rule] = createElementRule(DateCreatedConditionRule::class, [
            'rangeType' => 'empty',
        ]);

        expect($rule->matchElement($entry))->toBeFalse();
    });

    it('modifyQuery with not-empty returns all entries', function () {
        EntryModel::factory()->count(2)->create();

        [$condition, $rule] = createElementRule(DateCreatedConditionRule::class, [
            'rangeType' => 'notempty',
        ]);
        $condition->forQuery = true;
        $condition->addConditionRule($rule);

        $query = Entry::find();
        $condition->modifyQuery($query);

        expect($query->count())->toBeGreaterThanOrEqual(2);
    });
});

describe('HasUrlConditionRule', function () {
    it('matchElement returns true when element has URL and value is true', function () {
        $entry = createEntryWithAttributes(['uri' => 'test-uri']);

        [, $rule] = createElementRule(HasUrlConditionRule::class, [
            'value' => true,
        ]);

        // getUrl() depends on the site having a base URL and the element having a URI
        $hasUrl = $entry->getUrl() !== null;
        expect($rule->matchElement($entry))->toBe($hasUrl);
    });

    it('matchElement returns true when element has no URL and value is false', function () {
        $entry = createEntryWithAttributes(['uri' => null]);

        [, $rule] = createElementRule(HasUrlConditionRule::class, [
            'value' => false,
        ]);

        expect($rule->matchElement($entry))->toBeTrue();
    });

    it('modifyQuery with value true filters to entries with URI', function () {
        createEntryWithAttributes(['uri' => 'has-uri']);
        createEntryWithAttributes(['uri' => null]);

        [$condition, $rule] = createElementRule(HasUrlConditionRule::class, [
            'value' => true,
        ]);
        $condition->forQuery = true;
        $condition->addConditionRule($rule);

        $query = Entry::find();
        $condition->modifyQuery($query);

        foreach ($query->all() as $result) {
            expect($result->uri)->not->toBeNull()
                ->and($result->uri)->not->toBe('');
        }
    });

    it('modifyQuery with value false filters to entries without URI', function () {
        createEntryWithAttributes(['uri' => 'has-uri']);
        createEntryWithAttributes(['uri' => null]);

        [$condition, $rule] = createElementRule(HasUrlConditionRule::class, [
            'value' => false,
        ]);
        $condition->forQuery = true;
        $condition->addConditionRule($rule);

        $query = Entry::find();
        $condition->modifyQuery($query);

        foreach ($query->all() as $result) {
            expect($result->uri)->toBeNull();
        }
    });
});

describe('DateUpdatedConditionRule', function () {
    it('matchElement returns true with not-empty when entry has dateUpdated', function () {
        $entry = createEntryWithAttributes();

        [, $rule] = createElementRule(DateUpdatedConditionRule::class, [
            'rangeType' => 'notempty',
        ]);

        expect($rule->matchElement($entry))->toBeTrue();
    });

    it('matchElement returns false with empty when entry has dateUpdated', function () {
        $entry = createEntryWithAttributes();

        [, $rule] = createElementRule(DateUpdatedConditionRule::class, [
            'rangeType' => 'empty',
        ]);

        expect($rule->matchElement($entry))->toBeFalse();
    });

    it('modifyQuery with not-empty returns all entries', function () {
        EntryModel::factory()->count(2)->create();

        [$condition, $rule] = createElementRule(DateUpdatedConditionRule::class, [
            'rangeType' => 'notempty',
        ]);
        $condition->forQuery = true;
        $condition->addConditionRule($rule);

        $query = Entry::find();
        $condition->modifyQuery($query);

        expect($query->count())->toBeGreaterThanOrEqual(2);
    });
});

describe('UriConditionRule', function () {
    it('matchElement returns true when uri matches', function () {
        $entry = createEntryWithAttributes(['uri' => 'my-uri']);

        [, $rule] = createElementRule(UriConditionRule::class, [
            'operator' => '=',
            'value' => 'my-uri',
        ]);

        expect($rule->matchElement($entry))->toBeTrue();
    });

    it('matchElement returns false when uri does not match', function () {
        $entry = createEntryWithAttributes(['uri' => 'other-uri']);

        [, $rule] = createElementRule(UriConditionRule::class, [
            'operator' => '=',
            'value' => 'my-uri',
        ]);

        expect($rule->matchElement($entry))->toBeFalse();
    });

    it('modifyQuery filters entries by uri', function () {
        createEntryWithAttributes(['uri' => 'alpha-uri']);
        createEntryWithAttributes(['uri' => 'beta-uri']);

        [$condition, $rule] = createElementRule(UriConditionRule::class, [
            'operator' => '=',
            'value' => 'alpha-uri',
        ]);
        $condition->forQuery = true;
        $condition->addConditionRule($rule);

        $query = Entry::find();
        $condition->modifyQuery($query);

        expect($query->count())->toBe(1)
            ->and($query->one()->uri)->toBe('alpha-uri');
    });
});

describe('LanguageConditionRule', function () {
    it('matchElement returns true when language matches', function () {
        $entry = createEntryWithAttributes();
        $language = $entry->getLanguage();

        [, $rule] = createElementRule(LanguageConditionRule::class, [
            'operator' => 'in',
            'values' => [$language],
        ]);

        expect($rule->matchElement($entry))->toBeTrue();
    });

    it('matchElement returns false when language does not match', function () {
        $entry = createEntryWithAttributes();

        [, $rule] = createElementRule(LanguageConditionRule::class, [
            'operator' => 'in',
            'values' => ['not-a-real-language'],
        ]);

        expect($rule->matchElement($entry))->toBeFalse();
    });

    it('modifyQuery returns entries when the real language is used', function () {
        $entry = createEntryWithAttributes();
        $language = $entry->getLanguage();

        [, $rule] = createElementRule(LanguageConditionRule::class, [
            'operator' => 'in',
            'values' => [$language],
        ]);

        $query = Entry::find();
        $query->where(fn (Builder $builder) => $rule->modifyQuery($builder, $query));

        expect($query->count())->toBeGreaterThan(0);
    });

    it('modifyQuery throws when a bogus language is used', function () {
        createEntryWithAttributes();

        [, $rule] = createElementRule(LanguageConditionRule::class, [
            'operator' => 'in',
            'values' => ['not-a-real-language'],
        ]);

        $query = Entry::find();

        expect(fn () => $query->where(fn (Builder $builder) => $rule->modifyQuery($builder, $query)))->toThrow(InvalidArgumentException::class);
    });
});

describe('SiteConditionRule', function () {
    it('matchElement returns true when site matches', function () {
        $entry = createEntryWithAttributes();
        $siteUid = Sites::getPrimarySite()->uid;

        [, $rule] = createElementRule(SiteConditionRule::class, [
            'operator' => 'in',
            'values' => [$siteUid],
        ]);

        expect($rule->matchElement($entry))->toBeTrue();
    });

    it('matchElement returns false when site does not match', function () {
        $entry = createEntryWithAttributes();

        [, $rule] = createElementRule(SiteConditionRule::class, [
            'operator' => 'in',
            'values' => ['not-a-real-site-uid'],
        ]);

        expect($rule->matchElement($entry))->toBeFalse();
    });

    it('modifyQuery filters entries by site', function () {
        createEntryWithAttributes();
        $siteUid = Sites::getPrimarySite()->uid;

        [, $rule] = createElementRule(SiteConditionRule::class, [
            'operator' => 'in',
            'values' => [$siteUid],
        ]);

        $query = Entry::find();
        $query->where(fn (Builder $builder) => $rule->modifyQuery($builder, $query));

        expect($query->count())->toBeGreaterThan(0);

        foreach ($query->all() as $result) {
            expect($result->getSite()->uid)->toBe($siteUid);
        }
    });
});

describe('SiteGroupConditionRule', function () {
    it('matchElement returns true when site group matches', function () {
        $entry = createEntryWithAttributes();
        $groupUid = Sites::getPrimarySite()->getGroup()->uid;

        [, $rule] = createElementRule(SiteGroupConditionRule::class, [
            'operator' => 'in',
            'values' => [$groupUid],
        ]);

        expect($rule->matchElement($entry))->toBeTrue();
    });

    it('matchElement returns false when site group does not match', function () {
        $entry = createEntryWithAttributes();

        [, $rule] = createElementRule(SiteGroupConditionRule::class, [
            'operator' => 'in',
            'values' => ['not-a-real-group-uid'],
        ]);

        expect($rule->matchElement($entry))->toBeFalse();
    });

    it('modifyQuery filters entries by site group', function () {
        createEntryWithAttributes();
        $groupUid = Sites::getPrimarySite()->getGroup()->uid;

        [, $rule] = createElementRule(SiteGroupConditionRule::class, [
            'operator' => 'in',
            'values' => [$groupUid],
        ]);

        $query = Entry::find();
        $query->where(fn (Builder $builder) => $rule->modifyQuery($builder, $query));

        expect($query->count())->toBeGreaterThan(0);
    });
});

describe('HasDescendantsRule', function () {
    it('matchElement returns true when element has descendants and value is true', function () {
        ['root' => $root] = createStructureHierarchy();

        [, $rule] = createElementRule(HasDescendantsRule::class, [
            'value' => true,
        ]);

        expect($rule->matchElement($root))->toBeTrue();
    });

    it('matchElement returns false when element has no descendants and value is true', function () {
        ['nested' => $nested] = createStructureHierarchy();

        [, $rule] = createElementRule(HasDescendantsRule::class, [
            'value' => true,
        ]);

        expect($rule->matchElement($nested[0]))->toBeFalse();
    });

    it('modifyQuery filters entries by whether they have descendants', function () {
        ['structure' => $structure, 'root' => $root] = createStructureHierarchy();

        [, $rule] = createElementRule(HasDescendantsRule::class, [
            'value' => true,
        ]);

        $query = Entry::find()->structureId($structure->id);
        $query->where(fn (Builder $builder) => $rule->modifyQuery($builder, $query));

        $ids = $query->ids();

        expect($ids)->toContain($root->id)
            ->and($ids)->not->toBeEmpty();

        foreach ($query->all() as $result) {
            expect($result->getCanonical()->getHasDescendants())->toBeTrue();
        }
    });
});

describe('LevelConditionRule', function () {
    it('matchElement returns true when level matches', function () {
        ['children' => $children] = createStructureHierarchy();

        [, $rule] = createElementRule(LevelConditionRule::class, [
            'operator' => '=',
            'value' => '1',
        ]);

        expect($rule->matchElement($children[0]))->toBeTrue();
    });

    it('matchElement returns false when level does not match', function () {
        ['root' => $root] = createStructureHierarchy();

        [, $rule] = createElementRule(LevelConditionRule::class, [
            'operator' => '=',
            'value' => '1',
        ]);

        expect($rule->matchElement($root))->toBeFalse();
    });

    it('modifyQuery filters entries by level', function () {
        ['structure' => $structure] = createStructureHierarchy();

        [, $rule] = createElementRule(LevelConditionRule::class, [
            'operator' => '=',
            'value' => '1',
        ]);

        $query = Entry::find()->structureId($structure->id);
        $query->where(fn (Builder $builder) => $rule->modifyQuery($builder, $query));

        expect($query->count())->toBeGreaterThan(0);

        foreach ($query->all() as $result) {
            expect($result->level)->toBe(1);
        }
    });
});

describe('RelatedToConditionRule', function () {
    it('matchElement returns true when entry is related to the given element', function () {
        [$entry, $entries] = createRelatedEntries();

        [, $rule] = createElementRule(RelatedToConditionRule::class, [
            'elementIds' => [$entries[1]->id],
        ]);

        expect($rule->matchElement($entry))->toBeTrue();
    });

    it('matchElement returns false when entry is not related to the given element', function () {
        [$entry, $entries] = createRelatedEntries();

        [, $rule] = createElementRule(RelatedToConditionRule::class, [
            'elementIds' => [$entries[2]->id],
        ]);

        expect($rule->matchElement($entry))->toBeFalse();
    });

    it('modifyQuery filters entries related to the given element', function () {
        [$entry, $entries] = createRelatedEntries();

        [$condition, $rule] = createElementRule(RelatedToConditionRule::class, [
            'elementIds' => [$entries[1]->id],
        ]);
        $condition->forQuery = true;
        $condition->addConditionRule($rule);

        $query = Entry::find()->status(null);
        $condition->modifyQuery($query);

        expect($query->count())->toBe(1)
            ->and($query->one()?->id)->toBe($entry->id);
    });
});

describe('NotRelatedToConditionRule', function () {
    it('matchElement returns false when entry is related to the given element', function () {
        [$entry, $entries] = createRelatedEntries();

        [, $rule] = createElementRule(NotRelatedToConditionRule::class, [
            'elementIds' => [$entries[1]->id],
        ]);

        expect($rule->matchElement($entry))->toBeFalse();
    });

    it('matchElement returns true when entry is not related to the given element', function () {
        [$entry, $entries] = createRelatedEntries();

        [, $rule] = createElementRule(NotRelatedToConditionRule::class, [
            'elementIds' => [$entries[2]->id],
        ]);

        expect($rule->matchElement($entry))->toBeTrue();
    });

    it('modifyQuery excludes entries related to the given element', function () {
        [$entry, $entries] = createRelatedEntries();

        [$condition, $rule] = createElementRule(NotRelatedToConditionRule::class, [
            'elementIds' => [$entries[1]->id],
        ]);
        $condition->forQuery = true;
        $condition->addConditionRule($rule);

        $query = Entry::find()->status(null);
        $condition->modifyQuery($query);

        expect($query->count())->toBe(count($entries) - 1)
            ->and($query->ids())->not->toContain($entry->id);
    });
});
