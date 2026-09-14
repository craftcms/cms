<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Legacy;

use craft\elements\conditions\TitleConditionRule;
use CraftCms\Cms\Condition\ConditionRuleRenderer;
use CraftCms\Cms\Condition\Conditions;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;

class LegacyTitleInputRule extends TitleConditionRule
{
    protected function inputHtml(): string
    {
        HtmlStack::js('window.legacyConditionInput = ' . json_encode(InputNamespace::namespaceInputName('value')) . ';');

        return '<input name="value" value="plugin">';
    }
}

it('captures legacy input overrides in a Form bridge', function() {
    $rule = new LegacyTitleInputRule();
    $rule->condition = new ElementCondition(Entry::class);

    expect(app(ConditionRuleRenderer::class)->render($rule))
        ->toContain('name="operator"')
        ->toContain('name="value" value="plugin"');
});

it('captures a complete legacy HTML override', function() {
    $rule = new class() extends TitleConditionRule {
        public function getHtml(): string
        {
            return '<input name="custom" value="full-override">';
        }
    };

    expect(app(ConditionRuleRenderer::class)->render($rule))->toContain('full-override')->not->toContain('name="operator"');
});

it('preserves inputOptions overrides', function() {
    $rule = new class() extends TitleConditionRule {
        protected function inputOptions(): array
        {
            return parent::inputOptions() + ['placeholder' => 'plugin-placeholder'];
        }
    };
    $rule->condition = new ElementCondition(Entry::class);

    expect($rule->getHtml())->toContain('plugin-placeholder');
    expect(app(ConditionRuleRenderer::class)->render($rule))->toContain('plugin-placeholder');
});

it('keeps names and registered scripts in the same builder namespace', function() {
    $rule = new LegacyTitleInputRule();
    $rule->condition = new ElementCondition(Entry::class);
    $html = InputNamespace::namespaceInputs(fn() => app(ConditionRuleRenderer::class)->render($rule), 'settings[conditionRules][1]');

    expect($html)->toContain('name="settings[conditionRules][1][value]"')
        ->not->toContain('settings[conditionRules][1][settings]');
    expect(HtmlStack::bodyHtml())->toContain('settings[conditionRules][1][value]');
});

it('supports direct legacy builders and restores canonical rule configs', function() {
    $condition = new \craft\elements\conditions\ElementCondition(Entry::class);
    $rule = new TitleConditionRule();
    $rule->value = 'example';
    $condition->addConditionRule($rule);

    expect($condition->getBuilderHtml())->toContain('example');
    expect($rule->getConfig()['class'])->toBe(\CraftCms\Cms\Element\Conditions\TitleConditionRule::class);
    expect(app(Conditions::class)->createConditionRule(TitleConditionRule::class)::class)->toBe(\CraftCms\Cms\Element\Conditions\TitleConditionRule::class);
});

it('bridges plugins extending the legacy base rule without invoking modern value fields', function() {
    $rule = new class() extends \craft\base\conditions\BaseSelectConditionRule {
        public function getLabel(): string
        {
            return 'Plugin rule';
        }

        protected function options(): array
        {
            throw new \LogicException('The plugin owns the input HTML.');
        }

        protected function inputHtml(): string
        {
            return '<input name="value" value="custom-base-input">';
        }
    };

    expect(app(ConditionRuleRenderer::class)->render($rule))->toContain('custom-base-input');
});

it('preserves the submitted input names for legacy rule families', function(string $class, array $config, array $names) {
    $rule = new $class($config);
    $rule->condition = new ElementCondition(Entry::class);

    $crawler = new \Symfony\Component\DomCrawler\Crawler(app(ConditionRuleRenderer::class)->render($rule));

    expect($crawler->filter('[name]')->extract(['name']))->toContain(...$names);
})->with([
    'text' => [TitleConditionRule::class, ['value' => 'example'], ['operator', 'value']],
    'number range' => [\craft\elements\conditions\IdConditionRule::class, ['operator' => 'between', 'value' => '10', 'maxValue' => '20'], ['value', 'maxValue']],
    'lightswitch' => [\craft\elements\conditions\HasUrlConditionRule::class, [], ['value']],
    'membership' => [\craft\elements\conditions\StatusConditionRule::class, [], ['values[]']],
    'date range' => [\craft\elements\conditions\DateCreatedConditionRule::class, ['rangeType' => 'range'], ['startDate[date]', 'endDate[date]']],
    'author' => [\craft\elements\conditions\entries\AuthorConditionRule::class, [], ['elementIds']],
    'related element' => [\craft\elements\conditions\RelatedToConditionRule::class, [], ['elementType', 'elementIds']],
    'file size' => [\craft\elements\conditions\assets\FileSizeConditionRule::class, [], ['value', 'unit']],
    'administrative area' => [\craft\elements\conditions\addresses\AdministrativeAreaConditionRule::class, ['countryCode' => 'US'], ['countryCode', 'values[]']],
]);

it('scopes legacy plugin inputs and refreshes through the condition Form', function() {
    $rule = new LegacyTitleInputRule();
    $rule->condition = new ElementCondition(Entry::class);
    $payload = app(\CraftCms\Cms\Condition\ConditionBuilder::class)->resolveRule($rule);
    $control = $payload->form->nodes[0]->control;

    expect($control->props['fragment']['html'])->toContain("name=\"_conditionRules[{$rule->uid}][value]\"")
        ->and($control->props['expandValues'])->toBeTrue()
        ->and($control->reactive)->toBeTrue()
        ->and($control->deltaGroup)->toBe(['_conditionRules', $rule->uid]);
    expect(HtmlStack::bodyHtml())->toBe('');
    expect($control->props['fragment']['bodyHtml'])->toContain("_conditionRules[{$rule->uid}][value]");
});
