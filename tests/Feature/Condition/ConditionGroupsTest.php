<?php

declare(strict_types=1);

use CraftCms\Cms\Condition\Conditions;
use CraftCms\Cms\Condition\Enums\GroupOperator;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Conditions\TitleConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;

beforeEach(function () {
    $this->condition = new ElementCondition(Entry::class, [
        'conditionRules' => [
            'operator' => 'or',
            'rules' => [
                ['class' => TitleConditionRule::class, 'value' => 'Alpha'],
                ['operator' => 'and', 'rules' => [
                    ['class' => TitleConditionRule::class, 'operator' => '**', 'value' => 'Beta'],
                    ['class' => TitleConditionRule::class, 'operator' => '!=', 'value' => 'Beta draft'],
                ]],
            ],
        ],
    ]);
});

it('round trips nested groups and repeated rules supplied to the constructor', function () {
    $config = $this->condition->getConfig();
    $restored = app(Conditions::class)->createCondition($config);
    $nested = $restored->getConditionRules()->getRules()[1];

    expect($config['conditionRules']['operator'])->toBe('or');
    expect($restored->getConfig())->toBe($config);
    expect($restored->getConditionRules()->operator)->toBe(GroupOperator::Or);
    expect($nested->operator)->toBe(GroupOperator::And);
    expect($nested->getRules())->toHaveCount(2);
    expect($nested->getCondition())->toBe($restored);
    expect($nested->getRules()[0]->getCondition())->toBe($restored);
});

it('reattaches every descendant when assigning a group to another condition', function () {
    $second = new ElementCondition(Entry::class);
    $second->setConditionRules($this->condition->getConditionRules());

    $nested = $second->getConditionRules()->getRules()[1];

    expect($nested->getCondition())->toBe($second);
    expect($nested->getRules()[0]->getCondition())->toBe($second);
});

it('skips unavailable classes inside nested groups', function () {
    $config = $this->condition->getConfig()['conditionRules'];
    array_unshift($config['rules'][1]['rules'], ['class' => 'Missing\\ConditionRule']);
    $this->condition->setConditionRules($config);

    $rules = $this->condition->getConditionRules()->getRules()[1]->getRules();

    expect($rules)->toHaveCount(2);
    expect($rules[0]->value)->toBe('Beta');
});

it('evaluates nested AND and OR groups in queries and memory', function () {
    $entries = collect(['Alpha', 'Beta published', 'Beta draft', 'Gamma'])
        ->map(fn (string $title) => EntryModel::factory()->createElement(['title' => $title]));

    $this->condition->forQuery = true;
    $query = Entry::find()->id($entries->pluck('id')->all());
    $this->condition->modifyQuery($query);

    expect(collect($query->all())->pluck('title')->sort()->values()->all())->toBe(['Alpha', 'Beta published']);
    expect($entries->filter($this->condition->matchElement(...))->pluck('title')->values()->all())->toBe(['Alpha', 'Beta published']);
});

it('omits empty nested groups from the portable config', function () {
    $original = $this->condition->getConfig()['conditionRules'];
    $config = $original;
    $config['rules'][] = ['operator' => 'and', 'rules' => [['operator' => 'or', 'rules' => []]]];
    $this->condition->setConditionRules($config);

    expect($this->condition->getConfig()['conditionRules'])->toBe($original);
});

it('preserves an empty root operator submitted without rule inputs', function () {
    $condition = app(Conditions::class)->createCondition([
        'class' => ElementCondition::class,
        'elementType' => Entry::class,
        'conditionRules' => ['operator' => 'or'],
    ]);

    expect($condition->getConfig()['conditionRules'])->toBe(['operator' => 'or', 'rules' => []]);
});
