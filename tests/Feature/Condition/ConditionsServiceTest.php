<?php

declare(strict_types=1);

use CraftCms\Cms\Condition\Conditions;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Conditions\HasUrlConditionRule;
use CraftCms\Cms\Element\Conditions\SlugConditionRule;
use CraftCms\Cms\Element\Conditions\TitleConditionRule;

beforeEach(function () {
    $this->service = app(Conditions::class);
});

describe('createCondition', function () {
    it('creates a condition from a class string', function () {
        $condition = $this->service->createCondition(ElementCondition::class);

        expect($condition)->toBeInstanceOf(ElementCondition::class)
            ->and($condition)->toBeInstanceOf(ConditionInterface::class);
    });

    it('creates a condition from a config array', function () {
        $condition = $this->service->createCondition([
            'class' => ElementCondition::class,
        ]);

        expect($condition)->toBeInstanceOf(ElementCondition::class);
    });

    it('throws InvalidArgumentException for a non-existent class', function () {
        $this->service->createCondition('NotAValidClass');
    })->throws(InvalidArgumentException::class, 'Invalid condition class');

    it('throws InvalidArgumentException for a class that does not implement ConditionInterface', function () {
        $this->service->createCondition(stdClass::class);
    })->throws(InvalidArgumentException::class, 'Invalid condition class');

    it('returns a different instance each time', function () {
        $condition1 = $this->service->createCondition(ElementCondition::class);
        $condition2 = $this->service->createCondition(ElementCondition::class);

        expect($condition1)->not->toBe($condition2);
    });

    it('creates a condition with empty conditionRules by default', function () {
        $condition = $this->service->createCondition(ElementCondition::class);

        expect($condition->getConditionRules())->toBeEmpty();
    });
});

describe('createConditionRule', function () {
    it('creates a condition rule from a class string', function () {
        $rule = $this->service->createConditionRule(TitleConditionRule::class);

        expect($rule)->toBeInstanceOf(TitleConditionRule::class)
            ->and($rule)->toBeInstanceOf(ConditionRuleInterface::class);
    });

    it('creates a condition rule from a config array', function () {
        $rule = $this->service->createConditionRule([
            'class' => TitleConditionRule::class,
        ]);

        expect($rule)->toBeInstanceOf(TitleConditionRule::class);
    });

    it('throws InvalidArgumentException for a non-existent class', function () {
        $this->service->createConditionRule('NotAValidClass');
    })->throws(InvalidArgumentException::class, 'Invalid condition rule class');

    it('throws InvalidArgumentException for a class that does not implement ConditionRuleInterface', function () {
        $this->service->createConditionRule(stdClass::class);
    })->throws(InvalidArgumentException::class, 'Invalid condition rule class');

    it('handles a type key with a class string (condition builder)', function () {
        $rule = $this->service->createConditionRule([
            'type' => SlugConditionRule::class,
        ]);

        expect($rule)->toBeInstanceOf(SlugConditionRule::class);
    });

    it('handles a type key with JSON-encoded class (condition builder)', function () {
        $type = json_encode([
            'class' => TitleConditionRule::class,
        ]);

        $rule = $this->service->createConditionRule([
            'type' => $type,
        ]);

        expect($rule)->toBeInstanceOf(TitleConditionRule::class);
    });

    it('uses type class over original class when type changes', function () {
        $rule = $this->service->createConditionRule([
            'class' => TitleConditionRule::class,
            'type' => SlugConditionRule::class,
        ]);

        expect($rule)->toBeInstanceOf(SlugConditionRule::class);
    });

    it('keeps the same class when type matches class', function () {
        $rule = $this->service->createConditionRule([
            'class' => TitleConditionRule::class,
            'type' => TitleConditionRule::class,
        ]);

        expect($rule)->toBeInstanceOf(TitleConditionRule::class);
    });

    it('filters incompatible attributes when type changes to a different hierarchy', function () {
        // TitleConditionRule inherits `value` (string) from BaseTextConditionRule
        // HasUrlConditionRule inherits `value` (bool) from BaseLightswitchConditionRule
        // The declaring classes differ, so `value` should be filtered out from config
        // `operator` is from BaseConditionRule (shared) — would be kept
        $config = [
            'class' => TitleConditionRule::class,
            'operator' => 'bw',
            'value' => 'SomeText',
            'type' => HasUrlConditionRule::class,
        ];

        // After type switching, the config filtering runs via ReflectionProperty.
        // `value` has different declaring classes, so it's removed from config.
        // `operator` shares the same declaring class, so it stays in config.
        $rule = $this->service->createConditionRule($config);

        expect($rule)->toBeInstanceOf(HasUrlConditionRule::class);
    });

    it('does not filter attributes when switching between rules in the same hierarchy', function () {
        // Both TitleConditionRule and SlugConditionRule extend BaseTextConditionRule
        // All shared attributes (operator, value) have the same declaring class
        $config = [
            'class' => TitleConditionRule::class,
            'operator' => 'bw',
            'value' => 'Keep',
            'type' => SlugConditionRule::class,
        ];

        $rule = $this->service->createConditionRule($config);

        // The type changes to SlugConditionRule
        expect($rule)->toBeInstanceOf(SlugConditionRule::class);
        // operator and value remain in config (not filtered out) since they share declaring classes
    });

    it('assigns a uid to the created rule', function () {
        $rule = $this->service->createConditionRule(TitleConditionRule::class);

        expect($rule->uid)->toBeString()->not->toBeEmpty();
    });
});
