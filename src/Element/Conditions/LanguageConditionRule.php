<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use craft\base\ElementInterface;
use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Translation\Locale;

use function CraftCms\Cms\t;

class LanguageConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return t('Language');
    }

    /**
     * {@inheritdoc}
     */
    public function getExclusiveQueryParams(): array
    {
        return ['site', 'siteId'];
    }

    /**
     * {@inheritdoc}
     */
    protected function options(): array
    {
        return I18N::getSiteLocales()
            ->keyBy('id')
            ->map(fn (Locale $locale) => $locale->getDisplayName(app()->getLocale()))
            ->all();
    }

    /**
     * {@inheritdoc}
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->language($this->paramValue());
    }

    /**
     * {@inheritdoc}
     */
    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->getLanguage());
    }
}
