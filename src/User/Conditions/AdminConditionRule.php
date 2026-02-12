<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Conditions;

use craft\base\ElementInterface;
use craft\elements\db\UserQuery;
use CraftCms\Cms\Condition\BaseLightswitchConditionRule;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\t;

class AdminConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return t('Admin');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function isSelectable(): bool
    {
        return Edition::isAtLeast(Edition::Pro);
    }

    /**
     * {@inheritdoc}
     */
    public function getExclusiveQueryParams(): array
    {
        return ['admin'];
    }

    /**
     * {@inheritdoc}
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var UserQuery $query */
        $query->admin($this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var User $element */
        return $this->matchValue($element->admin);
    }
}
