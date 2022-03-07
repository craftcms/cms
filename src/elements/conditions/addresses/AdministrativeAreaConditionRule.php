<?php

namespace craft\elements\conditions\addresses;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\Address;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\AddressQuery;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;

/**
 * Address Administrative Area condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AdministrativeAreaConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    public string $countryCode = 'US';

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'countryCode' => $this->countryCode,
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['countryCode'], 'safe'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Administrative Area');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        return Craft::$app->getAddresses()->getSubdivisionRepository()->getList([$this->countryCode], Craft::$app->language);
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var AddressQuery $query */
        $query->administrativeArea($this->paramValue());
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Address $element */
        return $this->matchValue($element->administrativeArea);
    }

    protected function inputHtml(): string
    {
        $countrySelect = Cp::selectFieldHtml([
            'name' => 'countryCode',
            'options' => Craft::$app->getAddresses()->getCountryRepository()->getList(),
            'value' => $this->countryCode,
            'inputAttributes' => [
                'hx' => [
                    'post' => UrlHelper::actionUrl('conditions/render'),
                ],
            ],
        ]);

        return $countrySelect . parent::inputHtml();
    }
}
