<?php

declare(strict_types=1);
namespace craft\base;

trait LegacyConditionEvents
{
    /**
     * @event RegisterConditionRulesEvent The event that is triggered when defining the selectable condition rules.
     * @see getSelectableConditionRules()
     */
    public const EVENT_REGISTER_CONDITION_RULES = 'registerConditionRules';
}
