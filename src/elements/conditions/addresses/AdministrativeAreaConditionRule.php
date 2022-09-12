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
use craft\helpers\Html;
use craft\helpers\UrlHelper;

/**
 * Address Administrative Area condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AdministrativeAreaConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * @var string
     */
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
        $administrativeAreas = Craft::$app->getAddresses()->getSubdivisionRepository()->getList([$this->countryCode], Craft::$app->language);
        // Allow custom states that are currently in the administrative areas list to remain in the list.
        foreach ($this->getValues() as $val) {
            if (!in_array($val, $administrativeAreas, false)) {
                $administrativeAreas[$val] = $val;
            }
        }

        return $administrativeAreas;
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
            'id' => 'country-code',
            'name' => 'countryCode',
            'options' => Craft::$app->getAddresses()->getCountryRepository()->getList(),
            'value' => $this->countryCode,
            'inputAttributes' => [
                'hx' => [
                    'post' => UrlHelper::actionUrl('conditions/render'),
                ],
            ],
        ]);

        $multiSelectId = 'multiselect';
        $namespacedId = Craft::$app->getView()->namespaceInputId($multiSelectId);

        $js = <<<JS
$('#$namespacedId').selectize({
    plugins: ['remove_button'],
    create: true, // Must allow creation since administrive area field on addresses could be free text input
    onDropdownClose: () => {
        htmx.trigger(htmx.find('#$namespacedId'), 'change');
    },
});
JS;
        Craft::$app->getView()->registerJs($js);

        $adminSelectize =
            Html::hiddenLabel(Html::encode($this->getLabel()), $multiSelectId) .
            Cp::multiSelectHtml([
                'id' => $multiSelectId,
                'class' => 'selectize fullwidth',
                'name' => 'values',
                'values' => $this->getValues(),
                'options' => $this->options(),
                'inputAttributes' => [
                    'style' => [
                        'display' => 'none', // Hide it before selectize does its thing
                    ],
                ],
            ]);

        return $countrySelect . $adminSelectize;
    }
}
