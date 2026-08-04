<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Conditions\DateCreatedConditionRule;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Conditions\HasUrlConditionRule;
use CraftCms\Cms\Element\Conditions\IdConditionRule;
use CraftCms\Cms\Element\Conditions\SlugConditionRule;
use CraftCms\Cms\Element\Conditions\StatusConditionRule;
use CraftCms\Cms\Element\Conditions\TitleConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\User\Elements\User;
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

        [$condition, $rule] = createElementRule(StatusConditionRule::class, [
            'operator' => 'in',
            'values' => ['live'],
        ]);
        $condition->addConditionRule($rule);

        $query = Entry::find()->status(null);
        $condition->modifyQuery($query);

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
        $condition->addConditionRule($rule);

        $query = Entry::find();
        $condition->modifyQuery($query);

        foreach ($query->all() as $result) {
            expect($result->uri)->toBeNull();
        }
    });
});
