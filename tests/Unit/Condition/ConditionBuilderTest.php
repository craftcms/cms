<?php

declare(strict_types=1);

use CraftCms\Cms\Condition\BaseCondition;
use CraftCms\Cms\Condition\BaseConditionGroup;
use CraftCms\Cms\Condition\BaseConditionRule;
use CraftCms\Cms\Condition\ConditionBuilder;
use CraftCms\Cms\Condition\Contracts\ConditionGroupInterface;

class TestBuilderConditionGroup extends BaseConditionGroup {}

class TestBuilderConditionRule extends BaseConditionRule
{
    public string $ruleLabel = 'Rule';

    public ?string $ruleHint = null;

    public ?string $ruleGroup = null;

    /** @var string Distinguishes otherwise-identical config arrays, e.g. a field UID, without affecting the visible label. */
    public string $discriminator = '';

    public function getLabel(): string
    {
        return $this->ruleLabel;
    }

    public function getLabelHint(): ?string
    {
        return $this->ruleHint;
    }

    public function getGroupLabel(): ?string
    {
        return $this->ruleGroup;
    }
}

class TestBuilderCondition extends BaseCondition
{
    /** @var array<int, array<string, mixed>> */
    public array $types = [];

    public static function createGroup(): ConditionGroupInterface
    {
        return new TestBuilderConditionGroup;
    }

    protected function selectableConditionRules(): array
    {
        return $this->types;
    }
}

/**
 * @param  array<int, array<string, mixed>>  $types
 * @return list<array{value: string, label: string, hint: ?string, showHint: bool, group: ?string}>
 */
function resolveRuleTypes(array $types): array
{
    $condition = new TestBuilderCondition(['types' => $types]);

    return app(ConditionBuilder::class)->resolve($condition)->ruleTypes;
}

it('collapses rule types that share the same label and hint within a group', function () {
    // Two distinct fields (different discriminators/config) can still resolve to the same visible label,
    // e.g. same-named custom fields across multiple field layouts.
    $ruleTypes = resolveRuleTypes([
        ['class' => TestBuilderConditionRule::class, 'discriminator' => 'a', 'ruleLabel' => 'Title', 'ruleGroup' => 'Fields'],
        ['class' => TestBuilderConditionRule::class, 'discriminator' => 'b', 'ruleLabel' => 'Title', 'ruleGroup' => 'Fields'],
    ]);

    expect($ruleTypes)->toHaveCount(1);
});

it('keeps rule types with the same label but different hints', function () {
    $ruleTypes = resolveRuleTypes([
        ['class' => TestBuilderConditionRule::class, 'discriminator' => 'a', 'ruleLabel' => 'Title', 'ruleHint' => 'Section A', 'ruleGroup' => 'Fields'],
        ['class' => TestBuilderConditionRule::class, 'discriminator' => 'b', 'ruleLabel' => 'Title', 'ruleHint' => 'Section B', 'ruleGroup' => 'Fields'],
    ]);

    expect($ruleTypes)->toHaveCount(2);
});

it('keeps rule types with the same label and hint when they belong to different groups', function () {
    $ruleTypes = resolveRuleTypes([
        ['class' => TestBuilderConditionRule::class, 'discriminator' => 'a', 'ruleLabel' => 'Title', 'ruleGroup' => 'Fields'],
        ['class' => TestBuilderConditionRule::class, 'discriminator' => 'b', 'ruleLabel' => 'Title', 'ruleGroup' => null],
    ]);

    expect($ruleTypes)->toHaveCount(2);
});
