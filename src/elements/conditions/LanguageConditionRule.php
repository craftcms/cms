<?php

namespace craft\elements\conditions;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use craft\i18n\Locale;

/**
 * Language condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.5.0
 */
class LanguageConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Language');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['site', 'siteId'];
    }

    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        return ArrayHelper::map(
            Craft::$app->getI18n()->getSiteLocales(),
            fn(Locale $locale) => $locale->id,
            fn(Locale $locale) => $locale->getDisplayName(Craft::$app->language),
        );
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->language($this->paramValue());
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->getLanguage());
    }
}
