<?php

declare(strict_types=1);

use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\DateCreatedConditionRule;
use CraftCms\Cms\Element\Conditions\DateUpdatedConditionRule;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Conditions\IdConditionRule;
use CraftCms\Cms\Element\Conditions\SlugConditionRule;
use CraftCms\Cms\Element\Conditions\TitleConditionRule;
use CraftCms\Cms\Entry\Conditions\EntryCondition;
use CraftCms\Cms\Entry\Conditions\ExpiryDateConditionRule;
use CraftCms\Cms\Entry\Conditions\PostDateConditionRule;
use CraftCms\Cms\Entry\Conditions\SectionConditionRule;
use CraftCms\Cms\Entry\Conditions\TypeConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;

describe('setConditionRules() from config arrays', function () {
    it('creates rules from array configs', function () {
        $condition = new ElementCondition(Entry::class);
        $condition->setConditionRules([
            ['class' => TitleConditionRule::class],
        ]);

        $rules = $condition->getConditionRules();

        expect($rules)->toHaveCount(1);
        expect($rules[0])->toBeInstanceOf(TitleConditionRule::class);
    });

    it('creates multiple rules from array configs', function () {
        $condition = new ElementCondition(Entry::class);
        $condition->setConditionRules([
            ['class' => TitleConditionRule::class],
            ['class' => SlugConditionRule::class],
        ]);

        $rules = $condition->getConditionRules();

        expect($rules)->toHaveCount(2);
        expect($rules[0])->toBeInstanceOf(TitleConditionRule::class);
        expect($rules[1])->toBeInstanceOf(SlugConditionRule::class);
    });

    it('sets the condition reference on each rule', function () {
        $condition = new ElementCondition(Entry::class);
        $condition->setConditionRules([
            ['class' => TitleConditionRule::class],
        ]);

        $rules = $condition->getConditionRules();

        expect($rules[0]->getCondition())->toBe($condition);
    });

    it('skips invalid rule classes gracefully', function () {
        $condition = new ElementCondition(Entry::class);
        $condition->setConditionRules([
            ['class' => 'NonExistent\\ConditionRule'],
            ['class' => TitleConditionRule::class],
        ]);

        $rules = $condition->getConditionRules();

        expect($rules)->toHaveCount(1);
        expect($rules[0])->toBeInstanceOf(TitleConditionRule::class);
    });

    it('replaces existing rules when called again', function () {
        $condition = new ElementCondition(Entry::class);
        $condition->setConditionRules([
            ['class' => TitleConditionRule::class],
            ['class' => SlugConditionRule::class],
        ]);

        $condition->setConditionRules([
            ['class' => IdConditionRule::class],
        ]);

        $rules = $condition->getConditionRules();

        expect($rules)->toHaveCount(1);
        expect($rules[0])->toBeInstanceOf(IdConditionRule::class);
    });

    it('accepts rule instances directly', function () {
        $condition = new ElementCondition(Entry::class);
        $rule = $condition->createConditionRule(TitleConditionRule::class);
        $rule->operator = '=';
        $rule->value = 'Direct';

        $condition->setConditionRules([$rule]);

        $rules = $condition->getConditionRules();

        expect($rules)->toHaveCount(1);
        expect($rules[0])->toBeInstanceOf(TitleConditionRule::class);
        expect($rules[0]->value)->toBe('Direct');
    });
});

describe('addConditionRule()', function () {
    it('adds a rule instance to the condition', function () {
        $condition = new ElementCondition(Entry::class);
        $rule = $condition->createConditionRule(TitleConditionRule::class);
        $rule->operator = '=';
        $rule->value = 'Test';

        $condition->addConditionRule($rule);

        $rules = $condition->getConditionRules();

        expect($rules)->toHaveCount(1);
        expect($rules[0])->toBe($rule);
        expect($rules[0]->getCondition())->toBe($condition);
    });

    it('allows adding multiple different rules', function () {
        $condition = new ElementCondition(Entry::class);

        $titleRule = $condition->createConditionRule(TitleConditionRule::class);
        $titleRule->operator = '=';
        $titleRule->value = 'Test';
        $condition->addConditionRule($titleRule);

        $slugRule = $condition->createConditionRule(SlugConditionRule::class);
        $slugRule->operator = '=';
        $slugRule->value = 'test';
        $condition->addConditionRule($slugRule);

        expect($condition->getConditionRules())->toHaveCount(2);
    });

    it('throws InvalidArgumentException for a rule not in selectable rules', function () {
        $condition = new ElementCondition(Entry::class);

        // PostDateConditionRule is only selectable on EntryCondition, not ElementCondition
        $entryCondition = new EntryCondition(Entry::class);
        $rule = $entryCondition->createConditionRule(PostDateConditionRule::class);

        $condition->addConditionRule($rule);
    })->throws(InvalidArgumentException::class);
});

describe('getConfig() round-trip', function () {
    it('produces a config array with class and conditionRules for a rule', function () {
        $condition = new ElementCondition(Entry::class);
        $rule = $condition->createConditionRule(TitleConditionRule::class);
        $rule->operator = '=';
        $rule->value = 'Config Test';

        $config = $rule->getConfig();

        expect($config)->toHaveKey('class', TitleConditionRule::class);
        expect($config)->toHaveKey('operator', '=');
        expect($config)->toHaveKey('value', 'Config Test');
        expect($config)->toHaveKey('uid');
    });

    it('preserves rule UID through getConfig', function () {
        $condition = new ElementCondition(Entry::class);
        $rule = $condition->createConditionRule(TitleConditionRule::class);
        $rule->operator = '=';
        $rule->value = 'UID Test';

        $originalUid = $rule->uid;
        $config = $rule->getConfig();

        expect($config['uid'])->toBe($originalUid);
    });

    it('can recreate a rule from its config', function () {
        $condition = new ElementCondition(Entry::class);
        $rule = $condition->createConditionRule(TitleConditionRule::class);
        $rule->operator = 'bw';
        $rule->value = 'prefix';

        $config = $rule->getConfig();
        $restored = $condition->createConditionRule($config);

        expect($restored)->toBeInstanceOf(TitleConditionRule::class);
    });
});

describe('getSelectableConditionRules()', function () {
    it('returns expected rule types for ElementCondition', function () {
        $condition = new ElementCondition(Entry::class);
        $selectableRules = $condition->getSelectableConditionRules();

        $ruleClasses = array_map(fn ($rule) => $rule::class, $selectableRules);

        expect($ruleClasses)->toContain(TitleConditionRule::class);
        expect($ruleClasses)->toContain(SlugConditionRule::class);
        expect($ruleClasses)->toContain(IdConditionRule::class);
        expect($ruleClasses)->toContain(DateCreatedConditionRule::class);
        expect($ruleClasses)->toContain(DateUpdatedConditionRule::class);
    });

    it('returns entry-specific rules for EntryCondition', function () {
        $condition = new EntryCondition(Entry::class);
        $selectableRules = $condition->getSelectableConditionRules();

        $ruleClasses = array_map(fn ($rule) => $rule::class, $selectableRules);

        // EntryCondition should include parent rules
        expect($ruleClasses)->toContain(TitleConditionRule::class);
        expect($ruleClasses)->toContain(SlugConditionRule::class);

        // Plus entry-specific rules
        expect($ruleClasses)->toContain(PostDateConditionRule::class);
        expect($ruleClasses)->toContain(ExpiryDateConditionRule::class);
        expect($ruleClasses)->toContain(SectionConditionRule::class);
        expect($ruleClasses)->toContain(TypeConditionRule::class);
    });

    it('returns instances of ConditionRuleInterface', function () {
        $condition = new ElementCondition(Entry::class);
        $selectableRules = $condition->getSelectableConditionRules();

        foreach ($selectableRules as $rule) {
            expect($rule)->toBeInstanceOf(ConditionRuleInterface::class);
        }
    });
});

describe('forProjectConfig filtering', function () {
    it('excludes rules that do not support project config when forProjectConfig is true', function () {
        $condition = new ElementCondition(Entry::class);
        $condition->forProjectConfig = true;

        $selectableRules = $condition->getSelectableConditionRules();
        $ruleClasses = array_map(fn ($rule) => $rule::class, $selectableRules);

        // IdConditionRule returns false from supportsProjectConfig()
        expect($ruleClasses)->not->toContain(IdConditionRule::class);
    });

    it('includes rules that do not support project config when forProjectConfig is false', function () {
        $condition = new ElementCondition(Entry::class);
        $condition->forProjectConfig = false;

        $selectableRules = $condition->getSelectableConditionRules();
        $ruleClasses = array_map(fn ($rule) => $rule::class, $selectableRules);

        expect($ruleClasses)->toContain(IdConditionRule::class);
    });

    it('includes rules that support project config regardless of forProjectConfig', function () {
        $condition = new ElementCondition(Entry::class);
        $condition->forProjectConfig = true;

        $selectableRules = $condition->getSelectableConditionRules();
        $ruleClasses = array_map(fn ($rule) => $rule::class, $selectableRules);

        // TitleConditionRule supports project config (default true)
        expect($ruleClasses)->toContain(TitleConditionRule::class);
        expect($ruleClasses)->toContain(SlugConditionRule::class);
    });
});

describe('exclusive query params', function () {
    it('excludes a rule when its exclusive query params are already claimed', function () {
        $condition = new ElementCondition(Entry::class);

        // Add a TitleConditionRule which claims 'title' as exclusive
        $titleRule = $condition->createConditionRule(TitleConditionRule::class);
        $titleRule->operator = '=';
        $titleRule->value = 'Test';
        $condition->addConditionRule($titleRule);

        $selectableRules = $condition->getSelectableConditionRules();
        $ruleClasses = array_map(fn ($rule) => $rule::class, $selectableRules);

        // Another TitleConditionRule should not be selectable
        expect($ruleClasses)->not->toContain(TitleConditionRule::class);
    });

    it('excludes a rule when its exclusive query params overlap with queryParams property', function () {
        $condition = new ElementCondition(Entry::class);
        $condition->queryParams = ['title'];

        $selectableRules = $condition->getSelectableConditionRules();
        $ruleClasses = array_map(fn ($rule) => $rule::class, $selectableRules);

        expect($ruleClasses)->not->toContain(TitleConditionRule::class);
    });

    it('allows rules with different exclusive query params', function () {
        $condition = new ElementCondition(Entry::class);

        // Add a TitleConditionRule
        $titleRule = $condition->createConditionRule(TitleConditionRule::class);
        $titleRule->operator = '=';
        $titleRule->value = 'Test';
        $condition->addConditionRule($titleRule);

        $selectableRules = $condition->getSelectableConditionRules();
        $ruleClasses = array_map(fn ($rule) => $rule::class, $selectableRules);

        // SlugConditionRule claims 'slug', not 'title', so should still be selectable
        expect($ruleClasses)->toContain(SlugConditionRule::class);
        expect($ruleClasses)->toContain(IdConditionRule::class);
    });

    it('clears selectable rules cache when rules are added', function () {
        $condition = new ElementCondition(Entry::class);

        // Before adding any rule, title should be selectable
        $selectableBefore = $condition->getSelectableConditionRules();
        $classesBefore = array_map(fn ($rule) => $rule::class, $selectableBefore);
        expect($classesBefore)->toContain(TitleConditionRule::class);

        // Add title rule
        $titleRule = $condition->createConditionRule(TitleConditionRule::class);
        $titleRule->operator = '=';
        $titleRule->value = 'Test';
        $condition->addConditionRule($titleRule);

        // After adding, title should no longer be selectable
        $selectableAfter = $condition->getSelectableConditionRules();
        $classesAfter = array_map(fn ($rule) => $rule::class, $selectableAfter);
        expect($classesAfter)->not->toContain(TitleConditionRule::class);
    });
});
