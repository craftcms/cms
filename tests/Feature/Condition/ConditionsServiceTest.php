<?php

declare(strict_types=1);

use CraftCms\Cms\Condition\Conditions;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Conditions\HasUrlConditionRule;
use CraftCms\Cms\Element\Conditions\SlugConditionRule;
use CraftCms\Cms\Element\Conditions\TitleConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Conditions\TextFieldConditionRule;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField as CustomFieldElement;
use Illuminate\Support\Str;

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

        expect($condition->getConditionRules()->getRules())->toBeEmpty();
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
        // The lightswitch has no operators, so the text operator is discarded too.
        $config = [
            'class' => TitleConditionRule::class,
            'operator' => 'bw',
            'value' => 'SomeText',
            'type' => HasUrlConditionRule::class,
        ];

        // After type switching, the config filtering runs via ReflectionProperty.
        // `value` has different declaring classes, so it's removed from config.
        $rule = $this->service->createConditionRule($config);

        expect($rule)->toBeInstanceOf(HasUrlConditionRule::class);
        expect($rule->getConfig()['value'])->toBeTrue();
        expect($rule->getConfig())->not->toHaveKey('operator');
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
        expect($rule->getConfig()['value'])->toBe('Keep');
        expect($rule->operator)->toBe('bw');
    });

    it('assigns a uid to the created rule', function () {
        $rule = $this->service->createConditionRule(TitleConditionRule::class);

        expect($rule->uid)->toBeString()->not->toBeEmpty();
    });
});

it('round trips submitted text membership values through the saved JSON config', function () {
    $rule = $this->service->createConditionRule([
        'class' => TitleConditionRule::class,
        'operator' => 'in',
        'value' => ['Alpha', 'Beta'],
    ]);

    expect($rule->getConfig()['value'])->toBe('["Alpha","Beta"]');
    $restored = $this->service->createConditionRule($rule->getConfig());
    expect($restored->getConfig())->toBe($rule->getConfig());
});

it('accepts clearing a membership control', function () {
    $rule = $this->service->createConditionRule([
        'class' => TitleConditionRule::class,
        'operator' => 'in',
        'value' => '',
    ]);

    expect($rule->getConfig()['value'])->toBe('');
});

it('switches custom field identity when both fields use the same rule class', function () {
    $oldField = (string) Str::uuid();
    $newField = (string) Str::uuid();
    $newLayoutElement = (string) Str::uuid();
    $rule = $this->service->createConditionRule([
        'class' => TextFieldConditionRule::class,
        'fieldUid' => $oldField,
        'layoutElementUid' => (string) Str::uuid(),
        'operator' => 'bw',
        'value' => 'Keep',
        'type' => json_encode([
            'class' => TextFieldConditionRule::class,
            'fieldUid' => $newField,
            'layoutElementUid' => $newLayoutElement,
        ]),
    ]);

    expect($rule->getConfig())
        ->toMatchArray(['fieldUid' => $newField, 'layoutElementUid' => $newLayoutElement, 'operator' => 'bw', 'value' => 'Keep']);
});

it('rejects invalid layout conditions before saving', function () {
    $condition = new ElementCondition(Entry::class);
    $rule = $condition->createConditionRule(['class' => TitleConditionRule::class, 'operator' => 'invalid']);
    $condition->addConditionRule($rule);
    $layout = new FieldLayout(['type' => Entry::class]);
    $layout->setTabs([new FieldLayoutTab(['name' => 'Main', 'elementCondition' => $condition])]);

    expect(app(Fields::class)->saveLayout($layout))->toBeFalse()
        ->and($layout->errors()->has("tabs.0.elementCondition.{$rule->uid}.operator"))->toBeTrue()
        ->and($layout->id)->toBeNull();
});

it('resolves field rules that still reference a swapped-out field', function () {
    $fields = app(Fields::class);
    $newField = $fields->createField(['type' => PlainText::class, 'name' => 'Headline', 'handle' => 'headline']);
    $fields->saveField($newField);
    $oldFieldUid = (string) Str::uuid();

    $swappedElementUid = (string) Str::uuid();
    $layout = $fields->createLayout(['type' => Entry::class, 'tabs' => [[
        'name' => 'Main',
        'elements' => [
            ['type' => CustomFieldElement::class, 'fieldUid' => $newField->uid, 'label' => 'Other headline'],
            [
                'type' => CustomFieldElement::class,
                'uid' => $swappedElementUid,
                'fieldUid' => $newField->uid,
                'oldFieldUid' => $oldFieldUid,
                'label' => 'Swapped headline',
            ],
        ],
    ]]]);

    $condition = new ElementCondition(Entry::class);
    $condition->setFieldLayouts([$layout]);
    $rule = $condition->createConditionRule([
        'class' => TextFieldConditionRule::class,
        'fieldUid' => $oldFieldUid,
        'layoutElementUid' => $swappedElementUid,
    ]);

    expect($rule->getLabel())->toBe('Swapped headline');
});

it('offers one rule for each label a field has across layouts', function () {
    $fields = app(Fields::class);
    $field = $fields->createField(['type' => PlainText::class, 'name' => 'Headline', 'handle' => 'headline']);
    $fields->saveField($field);

    $layout = fn (?string $label) => $fields->createLayout(['type' => Entry::class, 'tabs' => [[
        'name' => 'Main',
        'elements' => [['type' => CustomFieldElement::class, 'uid' => (string) Str::uuid(), 'fieldUid' => $field->uid, 'label' => $label]],
    ]]]);

    $condition = new ElementCondition(Entry::class);
    $condition->setFieldLayouts([$layout(null), $layout(null), $layout('Teaser headline'), $layout(null)]);

    $labels = collect($condition->getSelectableConditionRules())
        ->filter(fn ($rule) => $rule instanceof TextFieldConditionRule)
        ->map(fn (TextFieldConditionRule $rule) => $rule->getLabel())
        ->values()
        ->all();

    expect($labels)->toBe(['Headline', 'Teaser headline']);
});
