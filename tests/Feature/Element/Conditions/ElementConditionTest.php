<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Conditions\IdConditionRule;
use CraftCms\Cms\Element\Conditions\SlugConditionRule;
use CraftCms\Cms\Element\Conditions\StatusConditionRule;
use CraftCms\Cms\Element\Conditions\TitleConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

function createEntryForTest(?string $title = null, ?string $slug = null): Entry
{
    $model = EntryModel::factory()->create();

    $updates = [];
    if ($title !== null) {
        $updates['title'] = $title;
    }
    if ($slug !== null) {
        $updates['slug'] = $slug;
    }

    if ($updates) {
        DB::table(Table::ELEMENTS_SITES)
            ->where('elementId', $model->id)
            ->update($updates);
    }

    return Entry::find()->id($model->id)->status(null)->one();
}

function createCondition(): ElementCondition
{
    return new ElementCondition(Entry::class);
}

beforeEach(function () {
    actingAs(User::findOne());
});

describe('modifyQuery with multiple rules', function () {
    it('applies AND logic — only entries matching all rules are returned', function () {
        $entry1 = createEntryForTest(title: 'Alpha');
        $entry2 = createEntryForTest(title: 'Beta');

        $condition = createCondition();

        $titleRule = $condition->createConditionRule(TitleConditionRule::class);
        $titleRule->operator = '=';
        $titleRule->value = 'Alpha';
        $condition->addConditionRule($titleRule);

        $idRule = $condition->createConditionRule(IdConditionRule::class);
        $idRule->operator = '=';
        $idRule->value = (string) $entry1->id;
        $condition->addConditionRule($idRule);

        $query = Entry::find();
        $condition->modifyQuery($query);

        expect($query->count())->toBe(1)
            ->and($query->one()->id)->toBe($entry1->id)
            ->and($query->one()->title)->toBe('Alpha');
    });

    it('returns nothing when rules contradict each other', function () {
        $entry1 = createEntryForTest(title: 'Alpha');
        $entry2 = createEntryForTest(title: 'Beta');

        $condition = createCondition();

        $titleRule = $condition->createConditionRule(TitleConditionRule::class);
        $titleRule->operator = '=';
        $titleRule->value = 'Alpha';
        $condition->addConditionRule($titleRule);

        // ID rule points to a different entry
        $idRule = $condition->createConditionRule(IdConditionRule::class);
        $idRule->operator = '=';
        $idRule->value = (string) $entry2->id;
        $condition->addConditionRule($idRule);

        $query = Entry::find();
        $condition->modifyQuery($query);

        expect($query->count())->toBe(0);
    });
});

describe('matchElement with multiple rules', function () {
    it('returns true when all rules match', function () {
        $entry = createEntryForTest(title: 'Alpha', slug: 'alpha');

        $condition = createCondition();

        $titleRule = $condition->createConditionRule(TitleConditionRule::class);
        $titleRule->operator = '=';
        $titleRule->value = 'Alpha';
        $condition->addConditionRule($titleRule);

        $slugRule = $condition->createConditionRule(SlugConditionRule::class);
        $slugRule->operator = '=';
        $slugRule->value = 'alpha';
        $condition->addConditionRule($slugRule);

        expect($condition->matchElement($entry))->toBeTrue();
    });

    it('returns false when one rule fails', function () {
        $entry = createEntryForTest(title: 'Alpha', slug: 'alpha');

        $condition = createCondition();

        $titleRule = $condition->createConditionRule(TitleConditionRule::class);
        $titleRule->operator = '=';
        $titleRule->value = 'Alpha';
        $condition->addConditionRule($titleRule);

        $slugRule = $condition->createConditionRule(SlugConditionRule::class);
        $slugRule->operator = '=';
        $slugRule->value = 'wrong-slug';
        $condition->addConditionRule($slugRule);

        expect($condition->matchElement($entry))->toBeFalse();
    });

    it('returns false when all rules fail', function () {
        $entry = createEntryForTest(title: 'Alpha', slug: 'alpha');

        $condition = createCondition();

        $titleRule = $condition->createConditionRule(TitleConditionRule::class);
        $titleRule->operator = '=';
        $titleRule->value = 'Wrong';
        $condition->addConditionRule($titleRule);

        $slugRule = $condition->createConditionRule(SlugConditionRule::class);
        $slugRule->operator = '=';
        $slugRule->value = 'wrong-slug';
        $condition->addConditionRule($slugRule);

        expect($condition->matchElement($entry))->toBeFalse();
    });
});

describe('empty condition (no rules)', function () {
    it('modifyQuery does not filter anything', function () {
        createEntryForTest(title: 'Alpha');
        createEntryForTest(title: 'Beta');

        $condition = createCondition();

        $queryWithout = Entry::find();
        $totalCount = $queryWithout->count();

        $queryWith = Entry::find();
        $condition->modifyQuery($queryWith);

        expect($queryWith->count())->toBe($totalCount);
    });

    it('matchElement matches any element', function () {
        $entry = createEntryForTest(title: 'Alpha');

        $condition = createCondition();

        expect($condition->matchElement($entry))->toBeTrue();
    });
});

describe('exclusive query params', function () {
    it('prevents selecting a rule whose exclusive param is already taken', function () {
        $condition = createCondition();

        $titleRule = $condition->createConditionRule(TitleConditionRule::class);
        $titleRule->operator = '=';
        $titleRule->value = 'Alpha';
        $condition->addConditionRule($titleRule);

        $selectable = $condition->getSelectableConditionRules();
        $selectableClasses = array_map(fn ($rule) => $rule::class, $selectable);

        expect($selectableClasses)->not->toContain(TitleConditionRule::class);
    });

    it('allows selecting rules with different exclusive params', function () {
        $condition = createCondition();

        $titleRule = $condition->createConditionRule(TitleConditionRule::class);
        $titleRule->operator = '=';
        $titleRule->value = 'Alpha';
        $condition->addConditionRule($titleRule);

        $selectable = $condition->getSelectableConditionRules();
        $selectableClasses = array_map(fn ($rule) => $rule::class, $selectable);

        expect($selectableClasses)->toContain(IdConditionRule::class)
            ->and($selectableClasses)->toContain(SlugConditionRule::class);
    });
});

describe('modifyQuery with status + title rules', function () {
    it('combines different rule types to narrow results', function () {
        EntryModel::factory()->count(3)->create();

        $condition = createCondition();

        $statusRule = $condition->createConditionRule(StatusConditionRule::class);
        $statusRule->operator = 'in';
        $statusRule->values = ['live'];
        $condition->addConditionRule($statusRule);

        $titleRule = $condition->createConditionRule(TitleConditionRule::class);
        $titleRule->operator = 'not empty';
        $condition->addConditionRule($titleRule);

        $query = Entry::find()->status(null);
        $condition->modifyQuery($query);

        foreach ($query->all() as $entry) {
            expect($entry->getStatus())->toBe('live')
                ->and($entry->title)->not->toBeNull()
                ->and($entry->title)->not->toBe('');
        }
    });
});

describe('getConfig', function () {
    it('includes class and elementType in config', function () {
        $condition = createCondition();

        $config = $condition->getConfig();

        expect($config)
            ->toHaveKey('class', ElementCondition::class)
            ->toHaveKey('elementType', Entry::class)
            ->toHaveKey('conditionRules')
            ->and($config['conditionRules'])->toBeArray()->toBeEmpty();
    });

    it('includes configured rules in config output', function () {
        $condition = createCondition();

        $titleRule = $condition->createConditionRule(TitleConditionRule::class);
        $titleRule->operator = '=';
        $titleRule->value = 'Test Title';
        $condition->addConditionRule($titleRule);

        $slugRule = $condition->createConditionRule(SlugConditionRule::class);
        $slugRule->operator = '=';
        $slugRule->value = 'test-slug';
        $condition->addConditionRule($slugRule);

        $config = $condition->getConfig();

        expect($config['conditionRules'])->toHaveCount(2)
            ->and($config['conditionRules'][0])->toHaveKey('class', TitleConditionRule::class)
            ->and($config['conditionRules'][0])->toHaveKey('value', 'Test Title')
            ->and($config['conditionRules'][0])->toHaveKey('operator', '=')
            ->and($config['conditionRules'][1])->toHaveKey('class', SlugConditionRule::class)
            ->and($config['conditionRules'][1])->toHaveKey('value', 'test-slug');
    });

    it('preserves rule UIDs in config', function () {
        $condition = createCondition();

        $titleRule = $condition->createConditionRule(TitleConditionRule::class);
        $titleRule->operator = '=';
        $titleRule->value = 'Alpha';
        $condition->addConditionRule($titleRule);

        $config = $condition->getConfig();

        expect($config['conditionRules'][0])->toHaveKey('uid')
            ->and($config['conditionRules'][0]['uid'])->toBe($titleRule->uid);
    });
});
