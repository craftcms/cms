<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Translation\Locale;
use Illuminate\Contracts\Database\Query\Builder;

use function CraftCms\Cms\t;

class LanguageConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Language');
    }

    protected function options(): array
    {
        return I18N::getSiteLocales()
            ->keyBy('id')
            ->map(fn (Locale $locale) => $locale->getDisplayName(app()->getLocale()))
            ->all();
    }

    /**
     * @param  ElementQueryInterface  $query
     * @param  ElementQuery<ElementInterface>  $elementQuery  The element query
     */
    public function modifyQuery(Builder $query, ElementQuery $elementQuery): void
    {
        ElementQuery::applySiteId($query, ElementQuery::siteIdsFromLanguage($this->paramValue()));
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->getLanguage());
    }
}
