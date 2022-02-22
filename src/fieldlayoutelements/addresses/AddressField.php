<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements\addresses;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Address;
use craft\fieldlayoutelements\BaseField;
use craft\helpers\Cp;
use yii\base\InvalidArgumentException;

/**
 * AddressField represents an Address field that can be included within an Address field layout designer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AddressField extends BaseField
{
    /**
     * @inheritdoc
     */
    public function attribute(): string
    {
        return 'address';
    }

    /**
     * @inheritdoc
     */
    public function mandatory(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function useFieldset(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function hasCustomWidth(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    protected function showLabel(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    protected function selectorLabel(): ?string
    {
        return Craft::t('app', 'Address');
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof Address) {
            throw new InvalidArgumentException('AddressField can only be used in address field layouts.');
        }

        $addressesService = Craft::$app->getAddresses();

        $formatRepo = $addressesService->getAddressFormatRepository()->get($element->countryCode);
        $requiredFields = array_flip($formatRepo->getRequiredFields());
        $visibleFields = array_flip(array_merge(
                $formatRepo->getUsedFields(),
                $formatRepo->getUsedSubdivisionFields(),
            )) + $requiredFields;

        $view = Craft::$app->getView();

        $view->registerJsWithVars(fn($namespace) => <<<JS
(() => {
    const \$countryCode = $('#' + Craft.namespaceId('country-code', $namespace));
    const \$spinner = $('#' + Craft.namespaceId('country-code-spinner', $namespace));
    \$countryCode.on('change', () => {
        if (!\$countryCode.val()) {
            return;
        }
        \$spinner.removeClass('hidden');
        Craft.sendActionRequest('POST', 'addresses/field-info', {
            params: {
                countryCode: \$countryCode.val(),
            },
        }).then(response => {
            for (const [id, info] of Object.entries(response.data.fields)) {
                const \$field = $('#' + Craft.namespaceId(id ,$namespace) + '-field');
                if (info.visible) {
                    \$field.removeClass('hidden');
                    const \$label = \$field.find('> .heading > label').empty().text(info.label);
                    
                    if (info.required) {
                        $('<span/>', {
                            class: 'visually-hidden',
                            text: Craft.t('app', 'Required'),
                        }).appendTo(\$label);
                        $('<span/>', {
                            class: 'required',
                            'aria-hidden': 'true',
                        }).appendTo(\$label);
                    }

                    if (id === 'administrativeArea') {
                        const \$select = \$field.find('select');
                        const selectize = \$select.data('selectize');
                        selectize.clear(true);
                        selectize.clearOptions(true);
                        selectize.addOption(response.data.administrativeAreaOptions);
                    }
                } else {
                    \$field.addClass('hidden');
                }
            }
        }).finally(() => {
            \$spinner.addClass('hidden');
        });
    });
})();
JS, [
            $view->getNamespace(),
        ]);

        return
            Cp::textFieldHtml([
                'label' => $element->getAttributeLabel('addressLine1'),
                'id' => 'addressLine1',
                'name' => 'addressLine1',
                'value' => $element->addressLine1,
                'required' => isset($requiredFields['addressLine1']),
                'errors' => $element->getErrors('addressLine1'),
            ]) .
            Cp::textFieldHtml([
                'label' => $element->getAttributeLabel('addressLine2'),
                'id' => 'addressLine2',
                'name' => 'addressLine2',
                'value' => $element->addressLine2,
                'required' => isset($requiredFields['addressLine2']),
                'errors' => $element->getErrors('addressLine2'),
            ]) .
            Cp::textFieldHtml([
                'fieldClass' => !isset($visibleFields['postalCode']) ? 'hidden' : null,
                'label' => $element->getAttributeLabel('postalCode'),
                'id' => 'postalCode',
                'name' => 'postalCode',
                'value' => $element->postalCode,
                'required' => isset($requiredFields['postalCode']),
                'errors' => $element->getErrors('postalCode'),
            ]) .
            Cp::textFieldHtml([
                'fieldClass' => !isset($visibleFields['sortingCode']) ? 'hidden' : null,
                'label' => $element->getAttributeLabel('sortingCode'),
                'id' => 'sortingCode',
                'name' => 'sortingCode',
                'value' => $element->sortingCode,
                'required' => isset($requiredFields['sortingCode']),
                'errors' => $element->getErrors('sortingCode'),
            ]) .
            Cp::selectizeFieldHtml([
                'fieldClass' => !isset($visibleFields['administrativeArea']) ? 'hidden' : null,
                'label' => $element->getAttributeLabel('administrativeArea'),
                'id' => 'administrativeArea',
                'name' => 'administrativeArea',
                'value' => $element->administrativeArea,
                'options' => $addressesService->getSubdivisionRepository()->getList([$element->countryCode], Craft::$app->language),
                'required' => isset($requiredFields['administrativeArea']),
                'errors' => $element->getErrors('administrativeArea'),
            ]) .
            Cp::textFieldHtml([
                'fieldClass' => !isset($visibleFields['locality']) ? 'hidden' : null,
                'label' => $element->getAttributeLabel('locality'),
                'id' => 'locality',
                'name' => 'locality',
                'value' => $element->locality,
                'required' => isset($requiredFields['locality']),
                'errors' => $element->getErrors('locality'),
            ]) .
            Cp::textFieldHtml([
                'fieldClass' => !isset($visibleFields['dependentLocality']) ? 'hidden' : null,
                'label' => $element->getAttributeLabel('dependentLocality'),
                'id' => 'dependentLocality',
                'name' => 'dependentLocality',
                'value' => $element->dependentLocality,
                'required' => isset($requiredFields['dependentLocality']),
                'errors' => $element->getErrors('dependentLocality'),
            ]);
    }
}
