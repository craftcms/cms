<?php

declare(strict_types=1);

use craft\helpers\DateRange;
use CraftCms\Cms\Element\Conditions\DateCreatedConditionRule;
use CraftCms\Cms\Element\Conditions\DateUpdatedConditionRule;
use CraftCms\Cms\Element\Conditions\IdConditionRule;
use CraftCms\Cms\Element\Conditions\SlugConditionRule;
use CraftCms\Cms\Element\Conditions\StatusConditionRule;
use CraftCms\Cms\Element\Conditions\TitleConditionRule;
use CraftCms\Cms\Entry\Conditions\AuthorConditionRule;
use CraftCms\Cms\Entry\Conditions\EntryCondition;
use CraftCms\Cms\Entry\Conditions\ExpiryDateConditionRule;
use CraftCms\Cms\Entry\Conditions\PostDateConditionRule;
use CraftCms\Cms\Entry\Conditions\SavableConditionRule;
use CraftCms\Cms\Entry\Conditions\SectionConditionRule;
use CraftCms\Cms\Entry\Conditions\TypeConditionRule;
use CraftCms\Cms\Entry\Conditions\ViewableConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
});

describe('selectableConditionRules', function () {
    it('returns entry-specific condition rule types', function () {
        $condition = new EntryCondition(Entry::class);
        $selectableRules = $condition->getSelectableConditionRules();
        $ruleClasses = array_map(fn ($rule) => $rule::class, $selectableRules);

        expect($ruleClasses)
            ->toContain(AuthorConditionRule::class)
            ->toContain(ExpiryDateConditionRule::class)
            ->toContain(PostDateConditionRule::class)
            ->toContain(SavableConditionRule::class)
            ->toContain(SectionConditionRule::class)
            ->toContain(TypeConditionRule::class)
            ->toContain(ViewableConditionRule::class);
    });

    it('includes parent ElementCondition rules', function () {
        $condition = new EntryCondition(Entry::class);
        $selectableRules = $condition->getSelectableConditionRules();
        $ruleClasses = array_map(fn ($rule) => $rule::class, $selectableRules);

        expect($ruleClasses)
            ->toContain(TitleConditionRule::class)
            ->toContain(SlugConditionRule::class)
            ->toContain(IdConditionRule::class)
            ->toContain(DateCreatedConditionRule::class)
            ->toContain(DateUpdatedConditionRule::class)
            ->toContain(StatusConditionRule::class);
    });
});

describe('rule config', function () {
    it('rule getConfig includes class key', function () {
        $condition = new EntryCondition(Entry::class);

        $rule = $condition->createConditionRule(SectionConditionRule::class);
        $rule->operator = 'notempty';

        $config = $rule->getConfig();

        expect($config)
            ->toHaveKey('class', SectionConditionRule::class);
    });

    it('rule config preserves operator and values', function () {
        $section = Section::factory()->create(['type' => SectionType::Channel]);
        Sections::refreshSections();

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(SectionConditionRule::class);
        $rule->operator = 'in';
        $rule->values = [$section->uid];

        $config = $rule->getConfig();

        expect($config)
            ->toHaveKey('operator', 'in')
            ->toHaveKey('values', [$section->uid]);
    });
});

describe('config round-trip', function () {
    it('preserves rules when recreating via setConditionRules', function () {
        $condition = new EntryCondition(Entry::class);

        $sectionRule = $condition->createConditionRule(SectionConditionRule::class);
        $sectionRule->operator = 'notempty';
        $condition->addConditionRule($sectionRule);

        $postDateRule = $condition->createConditionRule(PostDateConditionRule::class);
        $postDateRule->rangeType = DateRange::TYPE_RANGE;
        $postDateRule->startDate = '2025-01-01';
        $postDateRule->endDate = '2025-12-31';
        $condition->addConditionRule($postDateRule);

        $ruleConfigs = array_map(fn ($rule) => $rule->getConfig(), $condition->getConditionRules());

        $restored = new EntryCondition(Entry::class);
        $restored->setConditionRules($ruleConfigs);

        $restoredRules = $restored->getConditionRules();
        expect($restoredRules)->toHaveCount(2);

        expect($restoredRules[0])->toBeInstanceOf(SectionConditionRule::class);
        expect($restoredRules[0]->operator)->toBe('notempty');

        expect($restoredRules[1])->toBeInstanceOf(PostDateConditionRule::class);
        expect($restoredRules[1]->rangeType)->toBe(DateRange::TYPE_RANGE);
        expect($restoredRules[1]->startDate)->not->toBeNull();
        expect($restoredRules[1]->endDate)->not->toBeNull();
    });
});

describe('modifyQuery', function () {
    it('filters by section rule with notempty operator', function () {
        $section = Section::factory()->create(['type' => SectionType::Channel]);
        Sections::refreshSections();

        EntryModel::factory()->create(['sectionId' => $section->id]);

        $condition = new EntryCondition(Entry::class);
        $rule = $condition->createConditionRule(SectionConditionRule::class);
        $rule->operator = 'notempty';
        $condition->addConditionRule($rule);

        $query = Entry::find();
        $condition->modifyQuery($query);

        expect($query->count())->toBeGreaterThanOrEqual(1);
    });

    it('combines post date rule with section notempty rule', function () {
        $section = Section::factory()->create(['type' => SectionType::Channel]);
        Sections::refreshSections();

        EntryModel::factory()->create([
            'sectionId' => $section->id,
            'postDate' => new DateTime('2025-06-15'),
        ]);
        EntryModel::factory()->create([
            'sectionId' => $section->id,
            'postDate' => new DateTime('2025-01-15'),
        ]);

        $condition = new EntryCondition(Entry::class);

        $sectionRule = $condition->createConditionRule(SectionConditionRule::class);
        $sectionRule->operator = 'notempty';
        $condition->addConditionRule($sectionRule);

        $postDateRule = $condition->createConditionRule(PostDateConditionRule::class);
        $postDateRule->rangeType = DateRange::TYPE_RANGE;
        $postDateRule->startDate = '2025-06-01';
        $postDateRule->endDate = '2025-06-30';
        $condition->addConditionRule($postDateRule);

        $query = Entry::find()->status(null);
        $condition->modifyQuery($query);

        expect($query->count())->toBe(1);
    });
});

describe('matchElement', function () {
    it('returns true when all rules match', function () {
        $section = Section::factory()->create(['type' => SectionType::Channel]);
        Sections::refreshSections();

        $entry = EntryModel::factory()->create([
            'sectionId' => $section->id,
            'postDate' => new DateTime('2025-06-15'),
        ]);

        $condition = new EntryCondition(Entry::class);

        $sectionRule = $condition->createConditionRule(SectionConditionRule::class);
        $sectionRule->operator = 'in';
        $sectionRule->values = [$section->uid];
        $condition->addConditionRule($sectionRule);

        $postDateRule = $condition->createConditionRule(PostDateConditionRule::class);
        $postDateRule->rangeType = DateRange::TYPE_RANGE;
        $postDateRule->startDate = '2025-06-01';
        $postDateRule->endDate = '2025-06-30';
        $condition->addConditionRule($postDateRule);

        $element = Entry::find()->id($entry->id)->status(null)->one();

        expect($condition->matchElement($element))->toBeTrue();
    });

    it('returns false when any rule does not match (AND logic)', function () {
        $section1 = Section::factory()->create(['type' => SectionType::Channel]);
        $section2 = Section::factory()->create(['type' => SectionType::Channel]);
        Sections::refreshSections();

        $entry = EntryModel::factory()->create([
            'sectionId' => $section2->id,
            'postDate' => new DateTime('2025-06-15'),
        ]);

        $condition = new EntryCondition(Entry::class);

        // Rule requires section1, but entry is in section2
        $sectionRule = $condition->createConditionRule(SectionConditionRule::class);
        $sectionRule->operator = 'in';
        $sectionRule->values = [$section1->uid];
        $condition->addConditionRule($sectionRule);

        $postDateRule = $condition->createConditionRule(PostDateConditionRule::class);
        $postDateRule->rangeType = DateRange::TYPE_RANGE;
        $postDateRule->startDate = '2025-06-01';
        $postDateRule->endDate = '2025-06-30';
        $condition->addConditionRule($postDateRule);

        $element = Entry::find()->id($entry->id)->status(null)->one();

        expect($condition->matchElement($element))->toBeFalse();
    });

    it('matches with author rule combined with section rule', function () {
        $author = UserModel::factory()->create();
        $section = Section::factory()->create(['type' => SectionType::Channel]);
        Sections::refreshSections();

        $entry = EntryModel::factory()->create(['sectionId' => $section->id]);
        $element = Entry::find()->id($entry->id)->one();
        $element->setAuthorId($author->id);
        Craft::$app->getElements()->saveElement($element);

        $condition = new EntryCondition(Entry::class);

        $sectionRule = $condition->createConditionRule(SectionConditionRule::class);
        $sectionRule->operator = 'in';
        $sectionRule->values = [$section->uid];
        $condition->addConditionRule($sectionRule);

        $authorRule = $condition->createConditionRule(AuthorConditionRule::class);
        $authorRule->setElementIds([$author->id]);
        $condition->addConditionRule($authorRule);

        $element = Entry::find()->id($entry->id)->one();

        expect($condition->matchElement($element))->toBeTrue();
    });
});
