<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Conditions;

use CraftCms\Cms\Condition\BaseLightswitchConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\t;

/**
 * Entry savable condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.4.0
 */
class SavableConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Savable');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['savable'];
    }

    /** @param EntryQuery<Entry> $query */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->savable($this->value);
    }

    public function matchElement(ElementInterface $element): bool
    {
        return Gate::check('save', $element) === $this->value;
    }
}
