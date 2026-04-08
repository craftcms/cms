<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Conditions;

use craft\base\ElementInterface;
use craft\elements\db\EntryQuery;
use CraftCms\Cms\Condition\BaseLightswitchConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\t;

/**
 * Entry viewable condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.4.0
 */
class ViewableConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Viewable');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['editable'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var EntryQuery $query */
        $query->editable($this->value);
    }

    public function matchElement(ElementInterface $element): bool
    {
        return Gate::check('view', $element) === $this->value;
    }
}
