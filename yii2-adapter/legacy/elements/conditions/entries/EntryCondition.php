<?php

declare(strict_types=1);

namespace craft\elements\conditions\entries;

use CraftCms\Cms\Condition\ConditionBuilderRenderer;
use CraftCms\Cms\Condition\Conditions;
use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Yii2Adapter\Form\LegacyConditionClasses;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Entry\Conditions\EntryCondition instead. */
class EntryCondition extends \CraftCms\Cms\Entry\Conditions\EntryCondition
{
    public function getBuilderHtml(): string
    {
        return new ConditionBuilderRenderer($this)->render();
    }

    public function getBuilderInnerHtml(bool $autofocusAddButton = false): string
    {
        return new ConditionBuilderRenderer($this)->renderInner($autofocusAddButton);
    }

    protected function validateConditionRule(ConditionRuleInterface $rule): bool
    {
        if (isset(LegacyConditionClasses::CORE_CLASSES[$rule::class])) {
            $rule = app(Conditions::class)->createConditionRule($rule->getConfig() + ['condition' => $this]);
        }

        return parent::validateConditionRule($rule);
    }

    public function getConfig(): array
    {
        $config = parent::getConfig();

        if (static::class === self::class) {
            $config['class'] = \CraftCms\Cms\Entry\Conditions\EntryCondition::class;
        }

        return $config;
    }
}
