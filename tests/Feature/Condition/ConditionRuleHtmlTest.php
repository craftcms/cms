<?php

declare(strict_types=1);

use craft\helpers\DateRange;
use CraftCms\Cms\Element\Conditions\DateCreatedConditionRule;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Conditions\HasUrlConditionRule;
use CraftCms\Cms\Element\Conditions\IdConditionRule;
use CraftCms\Cms\Element\Conditions\StatusConditionRule;
use CraftCms\Cms\Element\Conditions\TitleConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;

function createHtmlRule(string $ruleClass): mixed
{
    $condition = new ElementCondition(Entry::class);

    return $condition->createConditionRule($ruleClass);
}

describe('BaseTextConditionRule::getHtml()', function () {
    it('renders an operator select with all text operators', function () {
        $rule = createHtmlRule(TitleConditionRule::class);
        $rule->operator = '=';
        $rule->value = 'Hello';

        $html = $rule->getHtml();

        expect($html)->toContain('<div class="flex flex-start">');
        expect($html)->toContain('name="operator"');
        expect($html)->toContain('equals');
        expect($html)->toContain('begins with');
        expect($html)->toContain('ends with');
        expect($html)->toContain('contains');
        expect($html)->toContain('has a value');
        expect($html)->toContain('is empty');
    });

    it('renders a text input with the current value', function () {
        $rule = createHtmlRule(TitleConditionRule::class);
        $rule->operator = '=';
        $rule->value = 'My Title';

        $html = $rule->getHtml();

        expect($html)->toContain('name="value"');
        expect($html)->toContain('value="My Title"');
        expect($html)->toContain('type="text"');
    });

    it('renders a hidden label for accessibility', function () {
        $rule = createHtmlRule(TitleConditionRule::class);
        $rule->operator = '=';

        $html = $rule->getHtml();

        expect($html)->toContain('visually-hidden');
        expect($html)->toContain('Operator');
    });

    it('hides the value input when operator is empty', function () {
        $rule = createHtmlRule(TitleConditionRule::class);
        $rule->operator = 'empty';

        $html = $rule->getHtml();

        expect($html)->not->toContain('name="value"');
    });

    it('hides the value input when operator is not empty', function () {
        $rule = createHtmlRule(TitleConditionRule::class);
        $rule->operator = 'notempty';

        $html = $rule->getHtml();

        expect($html)->not->toContain('name="value"');
    });

    it('marks the current operator as selected', function () {
        $rule = createHtmlRule(TitleConditionRule::class);
        $rule->operator = 'bw';

        $html = $rule->getHtml();

        expect($html)->toMatch('/value="bw"[^>]*selected/');
    });
});

describe('BaseNumberConditionRule::getHtml()', function () {
    it('renders a number input', function () {
        $rule = createHtmlRule(IdConditionRule::class);
        $rule->operator = '=';
        $rule->value = '42';

        $html = $rule->getHtml();

        expect($html)->toContain('type="number"');
        expect($html)->toContain('value="42"');
    });

    it('renders numeric operators', function () {
        $rule = createHtmlRule(IdConditionRule::class);
        $rule->operator = '=';

        $html = $rule->getHtml();

        expect($html)->toContain('equals');
        expect($html)->toContain('does not equal');
        expect($html)->toContain('is less than');
        expect($html)->toContain('is greater than');
    });

    it('includes the between operator', function () {
        $rule = createHtmlRule(IdConditionRule::class);
        $rule->operator = '=';

        $html = $rule->getHtml();

        expect($html)->toContain('value="between"');
    });

    it('renders two inputs for between operator', function () {
        $rule = createHtmlRule(IdConditionRule::class);
        $rule->operator = 'between';
        $rule->value = '10';
        $rule->maxValue = '20';

        $html = $rule->getHtml();

        expect($html)->toContain('name="value"');
        expect($html)->toContain('name="maxValue"');
        expect($html)->toContain('value="10"');
        expect($html)->toContain('value="20"');
    });

    it('renders min/max accessibility labels for between operator', function () {
        $rule = createHtmlRule(IdConditionRule::class);
        $rule->operator = 'between';

        $html = $rule->getHtml();

        expect($html)->toContain('Min Value');
        expect($html)->toContain('Max Value');
    });

    it('renders step attribute', function () {
        $rule = createHtmlRule(IdConditionRule::class);
        $rule->operator = '=';
        $rule->step = 1;

        $html = $rule->getHtml();

        expect($html)->toContain('step="1"');
    });

    it('hides the value input when operator is empty', function () {
        $rule = createHtmlRule(IdConditionRule::class);
        $rule->operator = 'empty';

        $html = $rule->getHtml();

        expect($html)->not->toContain('name="value"');
    });
});

describe('BaseLightswitchConditionRule::getHtml()', function () {
    it('renders a lightswitch toggle', function () {
        $rule = createHtmlRule(HasUrlConditionRule::class);

        $html = $rule->getHtml();

        expect($html)->toContain('lightswitch');
        expect($html)->toContain('name="value"');
    });

    it('renders a hidden operator input since there are no operator choices', function () {
        $rule = createHtmlRule(HasUrlConditionRule::class);

        $html = $rule->getHtml();

        expect($html)->toContain('type="hidden"');
        expect($html)->toContain('name="operator"');
    });

    it('renders an accessibility label', function () {
        $rule = createHtmlRule(HasUrlConditionRule::class);

        $html = $rule->getHtml();

        expect($html)->toContain('visually-hidden');
        expect($html)->toContain('Has URL');
    });
});

describe('BaseMultiSelectConditionRule::getHtml()', function () {
    it('renders an operator select with in/not-in options', function () {
        $rule = createHtmlRule(StatusConditionRule::class);
        $rule->operator = 'in';

        $html = $rule->getHtml();

        expect($html)->toContain('is one of');
        expect($html)->toContain('is not one of');
    });

    it('renders a multi-select input', function () {
        $rule = createHtmlRule(StatusConditionRule::class);
        $rule->operator = 'in';

        $html = $rule->getHtml();

        expect($html)->toContain('name="values');
    });

    it('renders an accessibility label', function () {
        $rule = createHtmlRule(StatusConditionRule::class);
        $rule->operator = 'in';

        $html = $rule->getHtml();

        expect($html)->toContain('visually-hidden');
        expect($html)->toContain('Status');
    });
});

describe('BaseDateRangeConditionRule::getHtml()', function () {
    it('renders a date range menu button', function () {
        $rule = createHtmlRule(DateCreatedConditionRule::class);
        $rule->rangeType = DateRange::TYPE_TODAY;

        $html = $rule->getHtml();

        expect($html)->toContain('menubtn');
        expect($html)->toContain('Today');
    });

    it('renders all preset range options in the menu', function () {
        $rule = createHtmlRule(DateCreatedConditionRule::class);
        $rule->rangeType = DateRange::TYPE_TODAY;

        $html = $rule->getHtml();

        expect($html)->toContain('Today');
        expect($html)->toContain('This week');
        expect($html)->toContain('This month');
        expect($html)->toContain('This year');
        expect($html)->toContain('Past year');
    });

    it('renders before/after/range custom options', function () {
        $rule = createHtmlRule(DateCreatedConditionRule::class);
        $rule->rangeType = DateRange::TYPE_TODAY;

        $html = $rule->getHtml();

        expect($html)->toContain('Before');
        expect($html)->toContain('After');
        expect($html)->toContain('Range');
    });

    it('renders empty/not-empty options', function () {
        $rule = createHtmlRule(DateCreatedConditionRule::class);
        $rule->rangeType = DateRange::TYPE_TODAY;

        $html = $rule->getHtml();

        expect($html)->toContain('has a value');
        expect($html)->toContain('is empty');
    });

    it('renders a hidden rangeType input', function () {
        $rule = createHtmlRule(DateCreatedConditionRule::class);
        $rule->rangeType = DateRange::TYPE_TODAY;

        $html = $rule->getHtml();

        expect($html)->toContain('name="rangeType"');
    });

    it('renders date pickers for range type', function () {
        $rule = createHtmlRule(DateCreatedConditionRule::class);
        $rule->rangeType = DateRange::TYPE_RANGE;

        $html = $rule->getHtml();

        expect($html)->toContain('name="startDate');
        expect($html)->toContain('name="endDate');
        expect($html)->toContain('From');
        expect($html)->toContain('To');
    });

    it('renders period inputs for before type', function () {
        $rule = createHtmlRule(DateCreatedConditionRule::class);
        $rule->rangeType = DateRange::TYPE_BEFORE;

        $html = $rule->getHtml();

        expect($html)->toContain('name="periodValue"');
        expect($html)->toContain('name="periodType"');
        expect($html)->toContain('minutes ago');
        expect($html)->toContain('hours ago');
        expect($html)->toContain('days ago');
    });

    it('renders period inputs for after type', function () {
        $rule = createHtmlRule(DateCreatedConditionRule::class);
        $rule->rangeType = DateRange::TYPE_AFTER;

        $html = $rule->getHtml();

        expect($html)->toContain('name="periodValue"');
        expect($html)->toContain('name="periodType"');
        expect($html)->toContain('minutes from now');
        expect($html)->toContain('hours from now');
        expect($html)->toContain('days from now');
    });

    it('does not render date pickers for preset range types', function () {
        $rule = createHtmlRule(DateCreatedConditionRule::class);
        $rule->rangeType = DateRange::TYPE_TODAY;

        $html = $rule->getHtml();

        expect($html)->not->toContain('name="startDate');
        expect($html)->not->toContain('name="endDate');
        expect($html)->not->toContain('name="periodValue"');
    });

    it('marks the current range type as selected in the menu', function () {
        $rule = createHtmlRule(DateCreatedConditionRule::class);
        $rule->rangeType = DateRange::TYPE_THIS_WEEK;

        $html = $rule->getHtml();

        expect($html)->toMatch('/class="sel"[^>]*data-value="thisWeek"/');
    });

    it('renders a hidden operator input since date range has no operator select', function () {
        $rule = createHtmlRule(DateCreatedConditionRule::class);

        $html = $rule->getHtml();

        expect($html)->toContain('<input type="hidden" name="operator"');
    });
});

describe('BaseSelectConditionRule::getHtml()', function () {
    it('renders a select input with no operator dropdown', function () {
        $condition = new ElementCondition(Entry::class);

        $testRule = new class extends \CraftCms\Cms\Condition\BaseSelectConditionRule implements \CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface
        {
            public function getLabel(): string
            {
                return 'Test Select';
            }

            protected function options(): array
            {
                return [
                    'a' => 'Alpha',
                    'b' => 'Bravo',
                    'c' => 'Charlie',
                ];
            }

            public function getExclusiveQueryParams(): array
            {
                return [];
            }

            public function modifyQuery(\CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface $query): void {}

            public function matchElement(\craft\base\ElementInterface $element): bool
            {
                return true;
            }
        };

        $testRule->setCondition($condition);
        $testRule->value = 'b';

        $html = $testRule->getHtml();

        expect($html)->toContain('name="value"');
        expect($html)->toContain('Alpha');
        expect($html)->toContain('Bravo');
        expect($html)->toContain('Charlie');
        expect($html)->toContain('visually-hidden');
        expect($html)->toContain('Test Select');
    });
});
