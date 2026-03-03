<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Conditions\SlugConditionRule;
use CraftCms\Cms\Element\Conditions\TitleConditionRule;
use CraftCms\Cms\Entry\Conditions\EntryCondition;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\User\Conditions\UserCondition;
use CraftCms\Cms\User\Elements\User;

describe('BaseCondition::getBuilderHtml()', function () {
    it('wraps content in a container tag with condition-container class', function () {
        $condition = new ElementCondition(Entry::class);
        $condition->id = 'test-builder';

        $html = $condition->getBuilderHtml();

        expect($html)->toContain('<form');
        expect($html)->toContain('id="test-builder"');
        expect($html)->toContain('class="condition-container"');
        expect($html)->toContain('</form>');
    });

    it('uses a custom mainTag when specified', function () {
        $condition = new ElementCondition(Entry::class);
        $condition->mainTag = 'div';
        $condition->id = 'test-div-builder';

        $html = $condition->getBuilderHtml();

        expect($html)->toContain('<div');
        expect($html)->not->toContain('<form');
        expect($html)->toContain('</div>');
    });

    it('contains the inner builder html', function () {
        $condition = new ElementCondition(Entry::class);

        $html = $condition->getBuilderHtml();

        expect($html)->toContain('condition-main');
        expect($html)->toContain('condition-footer');
    });

    it('registers Craft.initUiElements JavaScript', function () {
        $condition = new ElementCondition(Entry::class);
        $condition->id = 'test-ui-elements';

        $condition->getBuilderHtml();

        $jsBuffer = HtmlStack::bodyHtml();

        expect($jsBuffer)->toContain('Craft.initUiElements');
    });
});

describe('BaseCondition::getBuilderInnerHtml()', function () {
    it('renders the condition-main div with htmx attributes', function () {
        $condition = new ElementCondition(Entry::class);
        $condition->id = 'test-inner';

        $html = $condition->getBuilderInnerHtml();

        expect($html)->toContain('condition-main');
        expect($html)->toContain('hx-ext="craft-cp, craft-condition"');
    });

    it('renders hidden inputs for class and config', function () {
        $condition = new ElementCondition(Entry::class);

        $html = $condition->getBuilderInnerHtml();

        expect($html)->toContain('name="condition[class]"');
        expect($html)->toContain('value="'.ElementCondition::class.'"');
        expect($html)->toContain('name="condition[config]"');
    });

    it('renders a sortable container div when sortable is true', function () {
        $condition = new ElementCondition(Entry::class);
        $condition->sortable = true;

        $html = $condition->getBuilderInnerHtml();

        expect($html)->toContain('class="condition sortable"');
    });

    it('renders a non-sortable container div when sortable is false', function () {
        $condition = new ElementCondition(Entry::class);
        $condition->sortable = false;

        $html = $condition->getBuilderInnerHtml();

        expect($html)->toContain('class="condition"');
        expect($html)->not->toContain('class="condition sortable"');
    });

    it('renders the add-a-rule footer button', function () {
        $condition = new ElementCondition(Entry::class);

        $html = $condition->getBuilderInnerHtml();

        expect($html)->toContain('condition-footer');
        expect($html)->toContain('Add a rule');
    });

    it('renders a custom addRuleLabel', function () {
        $condition = new ElementCondition(Entry::class);
        $condition->addRuleLabel = 'Add filter';

        $html = $condition->getBuilderInnerHtml();

        expect($html)->toContain('Add filter');
    });

    it('renders a spinner element', function () {
        $condition = new ElementCondition(Entry::class);
        $condition->id = 'test-spinner';

        $html = $condition->getBuilderInnerHtml();

        expect($html)->toContain('spinner');
        expect($html)->toContain('test-spinner-spinner');
    });

    it('disables the add button when no selectable rules are available', function () {
        $condition = new ElementCondition(Entry::class);

        $selectableRules = $condition->getSelectableConditionRules();

        foreach ($selectableRules as $selectableRule) {
            try {
                $rule = $condition->createConditionRule($selectableRule::class);
                $condition->addConditionRule($rule);
            } catch (InvalidArgumentException) {
                // Rule may no longer be selectable due to exclusive query param overlap
            }
        }

        $html = $condition->getBuilderInnerHtml();

        expect($html)->toContain('disabled');
    });
});

describe('BaseCondition::getBuilderInnerHtml() with rules', function () {
    it('renders a fieldset for each configured rule', function () {
        $condition = new ElementCondition(Entry::class);
        $rule = $condition->createConditionRule(TitleConditionRule::class);
        $rule->operator = '=';
        $rule->value = 'Test';
        $condition->addConditionRule($rule);

        $html = $condition->getBuilderInnerHtml();

        expect($html)->toContain('<fieldset');
        expect($html)->toContain('condition-rule');
    });

    it('renders each rule body inside the builder', function () {
        $condition = new ElementCondition(Entry::class);
        $rule = $condition->createConditionRule(TitleConditionRule::class);
        $rule->operator = '=';
        $rule->value = 'Hello World';
        $condition->addConditionRule($rule);

        $html = $condition->getBuilderInnerHtml();

        expect($html)->toContain('rule-body');
        expect($html)->toContain('value="Hello World"');
    });

    it('renders a hidden uid input for each rule', function () {
        $condition = new ElementCondition(Entry::class);
        $rule = $condition->createConditionRule(TitleConditionRule::class);
        $rule->operator = '=';
        $rule->value = 'Test';
        $condition->addConditionRule($rule);

        $html = $condition->getBuilderInnerHtml();

        expect($html)->toContain('name="condition[conditionRules][1][uid]"');
        expect($html)->toContain("value=\"{$rule->uid}\"");
    });

    it('renders a hidden class input for each rule', function () {
        $condition = new ElementCondition(Entry::class);
        $rule = $condition->createConditionRule(TitleConditionRule::class);
        $rule->operator = '=';
        $rule->value = 'Test';
        $condition->addConditionRule($rule);

        $html = $condition->getBuilderInnerHtml();

        expect($html)->toContain('name="condition[conditionRules][1][class]"');
        expect($html)->toContain('value="'.TitleConditionRule::class.'"');
    });

    it('renders a move handle when sortable', function () {
        $condition = new ElementCondition(Entry::class);
        $condition->sortable = true;
        $rule = $condition->createConditionRule(TitleConditionRule::class);
        $rule->operator = '=';
        $rule->value = 'Test';
        $condition->addConditionRule($rule);

        $html = $condition->getBuilderInnerHtml();

        expect($html)->toContain('draggable-handle');
        expect($html)->toContain('rule-move');
    });

    it('does not render a move handle when not sortable', function () {
        $condition = new ElementCondition(Entry::class);
        $condition->sortable = false;
        $rule = $condition->createConditionRule(TitleConditionRule::class);
        $rule->operator = '=';
        $rule->value = 'Test';
        $condition->addConditionRule($rule);

        $html = $condition->getBuilderInnerHtml();

        expect($html)->not->toContain('draggable-handle');
        expect($html)->not->toContain('rule-move');
    });

    it('renders a remove button for each rule', function () {
        $condition = new ElementCondition(Entry::class);
        $rule = $condition->createConditionRule(TitleConditionRule::class);
        $rule->operator = '=';
        $rule->value = 'Test';
        $condition->addConditionRule($rule);

        $html = $condition->getBuilderInnerHtml();

        expect($html)->toContain('rule-actions');
        expect($html)->toContain('Remove');
        expect($html)->toContain('conditions/remove-rule');
    });

    it('renders a rule type switcher menu for each rule', function () {
        $condition = new ElementCondition(Entry::class);
        $rule = $condition->createConditionRule(TitleConditionRule::class);
        $rule->operator = '=';
        $rule->value = 'Test';
        $condition->addConditionRule($rule);

        $html = $condition->getBuilderInnerHtml();

        expect($html)->toContain('rule-switcher');
        expect($html)->toContain('menubtn');
        expect($html)->toContain('Title');
    });

    it('renders multiple rules with sequential numbering', function () {
        $condition = new ElementCondition(Entry::class);

        $titleRule = $condition->createConditionRule(TitleConditionRule::class);
        $titleRule->operator = '=';
        $titleRule->value = 'A';
        $condition->addConditionRule($titleRule);

        $slugRule = $condition->createConditionRule(SlugConditionRule::class);
        $slugRule->operator = '=';
        $slugRule->value = 'b';
        $condition->addConditionRule($slugRule);

        $html = $condition->getBuilderInnerHtml();

        // Names are namespaced as condition[conditionRules][N]
        expect($html)->toContain('condition[conditionRules][1]');
        expect($html)->toContain('condition[conditionRules][2]');
    });

    it('renders an accessible legend for each rule', function () {
        $condition = new ElementCondition(Entry::class);
        $rule = $condition->createConditionRule(TitleConditionRule::class);
        $rule->operator = '=';
        $rule->value = 'Test';
        $condition->addConditionRule($rule);

        $html = $condition->getBuilderInnerHtml();

        expect($html)->toContain('<legend');
        expect($html)->toContain('visually-hidden');
    });
});

describe('Rule type menu', function () {
    it('lists all selectable rule types in the add-a-rule menu', function () {
        $condition = new ElementCondition(Entry::class);

        $html = $condition->getBuilderInnerHtml();

        expect($html)->toContain('Title');
        expect($html)->toContain('Slug');
        expect($html)->toContain('ID');
        expect($html)->toContain('Date Created');
    });

    it('excludes already-configured rule types from the add-a-rule menu by exclusive query params', function () {
        $condition = new ElementCondition(Entry::class);
        $rule = $condition->createConditionRule(TitleConditionRule::class);
        $rule->operator = '=';
        $rule->value = 'Test';
        $condition->addConditionRule($rule);

        $html = $condition->getBuilderInnerHtml();

        $footerMatch = preg_match('/condition-footer.*$/s', $html, $matches);
        expect($footerMatch)->toBe(1);

        $footerHtml = $matches[0];

        expect($footerHtml)->not->toContain('>Title<');
        expect($footerHtml)->toContain('Slug');
    });

    it('renders a menu div with class menu', function () {
        $condition = new ElementCondition(Entry::class);

        $html = $condition->getBuilderInnerHtml();

        expect($html)->toContain('class="menu"');
    });
});

describe('ElementCondition builder config', function () {
    it('includes condition class in hidden config input', function () {
        $condition = new ElementCondition(Entry::class);

        $html = $condition->getBuilderInnerHtml();

        expect($html)->toContain(ElementCondition::class);
    });
});

describe('EntryCondition::getBuilderInnerHtml()', function () {
    it('includes entry-specific rule types in the add menu', function () {
        $condition = new EntryCondition(Entry::class);

        $html = $condition->getBuilderInnerHtml();

        expect($html)->toContain('Post Date');
        expect($html)->toContain('Expiry Date');
    });

    it('includes parent element rule types in the add menu', function () {
        $condition = new EntryCondition(Entry::class);

        $html = $condition->getBuilderInnerHtml();

        expect($html)->toContain('Title');
        expect($html)->toContain('Slug');
    });
});

describe('UserCondition::getBuilderInnerHtml()', function () {
    it('includes user-specific rule types in the add menu', function () {
        $condition = new UserCondition(User::class);

        $html = $condition->getBuilderInnerHtml();

        expect($html)->toContain('Email');
        expect($html)->toContain('Last Login Date');
    });
});
