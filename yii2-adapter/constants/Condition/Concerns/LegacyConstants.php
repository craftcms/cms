<?php

declare(strict_types=1);
namespace CraftCms\Cms\Condition\Concerns;

use craft\base\Event as YiiEvent;
use craft\elements\conditions\users\AffiliatedSiteConditionRule;
use craft\elements\conditions\users\CredentialedConditionRule;
use craft\elements\conditions\users\EmailConditionRule;
use craft\elements\conditions\users\FirstNameConditionRule;
use craft\elements\conditions\users\LastNameConditionRule;
use craft\events\RegisterConditionRulesEvent;
use CraftCms\Cms\Condition\Events\ConditionRulesResolving;
use CraftCms\Cms\User\Conditions\AdminConditionRule;
use CraftCms\Cms\User\Conditions\UserCondition;
use CraftCms\Cms\User\Conditions\UsernameConditionRule;
use CraftCms\Yii2Adapter\ModelWrapper;
use CraftCms\Yii2Adapter\Validation\LegacyYiiRules;
use Illuminate\Support\Facades\Event;

/**
 * @internal
 * @deprecated 6.0.0
 * @phpstan-ignore trait.unused
 */
trait LegacyConstants
{
    /**
     * @event RegisterConditionRulesEvent The event that is triggered when defining the selectable condition rules.
     * @see getSelectableConditionRules()
     */
    public const EVENT_REGISTER_CONDITION_RULES = 'registerConditionRules';

    public function getRules(): array
    {
        return LegacyYiiRules::mergeWildcardRules(
            rules: parent::getRules(),
            target: $this,
            yiiRules: $this->defineRules(),
            validatorTarget: fn() => new ModelWrapper($this),
        );
    }

    public function defineRules(): array
    {
        return [];
    }

    public static function registerEvents(): void
    {
        Event::listen(function(ConditionRulesResolving $event) {
            $map = [
                AdminConditionRule::class => \craft\elements\conditions\users\AdminConditionRule::class,
                \CraftCms\Cms\User\Conditions\AffiliatedSiteConditionRule::class => AffiliatedSiteConditionRule::class,
                \CraftCms\Cms\User\Conditions\CredentialedConditionRule::class => CredentialedConditionRule::class,
                \CraftCms\Cms\User\Conditions\EmailConditionRule::class => EmailConditionRule::class,
                \CraftCms\Cms\User\Conditions\FirstNameConditionRule::class => FirstNameConditionRule::class,
                \CraftCms\Cms\User\Conditions\LastNameConditionRule::class => LastNameConditionRule::class,
                UserCondition::class => \craft\elements\conditions\users\UserCondition::class,
                UsernameConditionRule::class => \craft\elements\conditions\users\UsernameConditionRule::class,
            ];

            $oldClass = $map[$event->condition::class] ?? null;

            if (!$oldClass) {
                return;
            }

            // Fire a 'registerConditionRules' event
            if (YiiEvent::hasHandlers($oldClass, $oldClass::EVENT_REGISTER_CONDITION_RULES)) {
                $yiiEvent = new RegisterConditionRulesEvent([
                    'conditionRules' => $event->conditionRules,
                ]);
                YiiEvent::trigger($oldClass, $oldClass::EVENT_REGISTER_CONDITION_RULES, $yiiEvent);
                $event->conditionRules = $yiiEvent->conditionRules;
            }
        });
    }
}
