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
    public function formHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof Address) {
            throw new InvalidArgumentException('AddressField can only be used in address field layouts.');
        }

        $view = Craft::$app->getView();

        $view->registerJsWithVars(fn($namespace) => <<<JS
(() => {
    const initFields = (values) => {
        const fields = {};
        const fieldNames = [
            'countryCode',
            'addressLine1',
            'addressLine2',
            'administrativeArea',
            'locality',
            'dependentLocality',
            'postalCode',
            'sortingCode',
        ];
        const hotFieldNames = [
            'countryCode',
            'administrativeArea',
            'locality',
        ];
        for (let name of fieldNames) {
            fields[name] = $('#' + Craft.namespaceId(name, $namespace));
            if (values) {
                fields[name].val(values[name]);
            }
        }
        for (let name of hotFieldNames) {
            const field = fields[name];
            if (field.prop('nodeName') !== 'SELECT') {
                break;
            }
            const spinner = $('#' + Craft.namespaceId(name + '-spinner', $namespace));
            field.off().on('change', () => {
                if (!field.val()) {
                    return;
                }
                spinner.removeClass('hidden');
                const hotValues = {};
                for (let hotName of hotFieldNames) {
                    hotValues[hotName] = fields[hotName].val();
                    if (hotName === name) {
                        break;
                    }
                }
                Craft.sendActionRequest('POST', 'addresses/fields', {
                    params: Object.assign({}, hotValues, {
                        namespace: $namespace,
                    }),
                }).then(response => {
                    const values = Object.assign(
                        Object.fromEntries(fieldNames.map(name => [name, fields[name].val()])),
                        Object.fromEntries(hotFieldNames.map(name => [name, hotValues[name] || null]))
                    );
                    const activeElementId = document.activeElement ? document.activeElement.id : null;
                    const \$addressFields = $(
                        Object.entries(fields)
                            .filter(([name]) => name !== 'countryCode')
                            .map(([, \$field]) => \$field.closest('.field')[0])
                    );
                    \$addressFields.eq(0).replaceWith(response.data.fieldsHtml);
                    \$addressFields.remove();
                    initFields(values);
                    Craft.appendHeadHtml(response.data.headHtml);
                    Craft.appendBodyHtml(response.data.bodyHtml);
                    if (activeElementId) {
                        $('#' + activeElementId).focus();                        
                    }
                }).catch(e => {
                    Craft.cp.displayError();
                    throw e;
                }).finally(() => {
                    spinner.addClass('hidden');
                });
            })
        }
    };

    initFields();
})();
JS, [
            $view->getNamespace(),
        ]);

        return Cp::addressFieldsHtml($element);
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        // Not actually needed since we're overriding formHtml()
        return null;
    }
}
