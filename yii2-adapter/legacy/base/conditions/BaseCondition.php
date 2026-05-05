<?php

namespace craft\base\conditions;

use craft\base\LegacyConditionEvents;
use craft\base\LegacyEventConstants;
use craft\elements\conditions\users\AffiliatedSiteConditionRule;
use craft\elements\conditions\users\CredentialedConditionRule;
use craft\elements\conditions\users\EmailConditionRule;
use craft\elements\conditions\users\FirstNameConditionRule;
use craft\elements\conditions\users\LastNameConditionRule;
use craft\events\RegisterConditionRulesEvent;
use craft\helpers\Html;
use CraftCms\Cms\Condition\Events\ConditionRulesResolving;
use CraftCms\Cms\User\Conditions\AdminConditionRule;
use CraftCms\Cms\User\Conditions\UserCondition;
use CraftCms\Cms\User\Conditions\UsernameConditionRule;
use CraftCms\Yii2Adapter\ModelWrapper;
use CraftCms\Yii2Adapter\Validation\LegacyYiiRules;
use Illuminate\Support\Facades\Event;

/**
 * BaseCondition provides a base implementation for conditions.
 *
 * @property ConditionRuleInterface[] $conditionRules The rules this condition is configured with
 * @property-read array $config The condition’s portable config
 * @property-read string $builderHtml The HTML for the condition builder, including its outer container element
 * @property-read string $builderInnerHtml The inner HTML for the condition builder, excluding its outer container element
 * @property-read string[]|array{class: string}[] $conditionRuleTypes The available rule types for this condition
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Condition\BaseCondition} instead.
 */
abstract class BaseCondition extends \CraftCms\Cms\Condition\BaseCondition
{
    use LegacyEventConstants;
    use LegacyConditionEvents;

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
            if (\craft\base\Event::hasHandlers($oldClass, self::EVENT_REGISTER_CONDITION_RULES)) {
                $yiiEvent = new RegisterConditionRulesEvent([
                    'conditionRules' => $event->conditionRules,
                ]);
                \craft\base\Event::trigger($oldClass, self::EVENT_REGISTER_CONDITION_RULES, $yiiEvent);
                $event->conditionRules = $yiiEvent->conditionRules;
            }
        });
    }
}
