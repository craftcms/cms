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
use craft\helpers\Html;
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
    public function previewable(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function previewHtml(ElementInterface $element): string
    {
        /** @var Address $element */
        return Html::tag('div', Craft::$app->getAddresses()->formatAddress($element), [
            'class' => 'no-truncate',
        ]);
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
    protected function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        // we need it for the card view designer
        return Craft::t('app', 'Address');
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
            throw new InvalidArgumentException(sprintf('%s can only be used in address field layouts.', self::class));
        }

        $view = Craft::$app->getView();

        if (!$static) {
            $view->registerJsWithVars(fn($namespace) => <<<JS
    (() => {
        const initFields = (values) => {
            const fields = {};
            const fieldNames = [
                'countryCode',
                'addressLine1',
                'addressLine2',
                'addressLine3',
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
                if (values && values[name] !== null) {
                    fields[name].val(values[name]);
                }
            }
            for (let name of hotFieldNames) {
                const field = fields[name];
                if (field.prop('nodeName') !== 'SELECT') {
                    break;
                }
    
                let oldFieldVal = field.val();
                const spinner = $('#' + Craft.namespaceId(name + '-spinner', $namespace));
                field.off().on('change', () => {
                    if (!field.val() || oldFieldVal === field.val()) {
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
                    }).then(async (response) => {
                        const values = Object.assign(
                            Object.fromEntries(fieldNames.map(name => [name, fields[name].val()])),
                            Object.fromEntries(hotFieldNames.map(name => [name, hotValues[name] || null]))
                        );
                        let newField = null;
                        hotFieldNames.forEach((name) => {
                          // if value for any hotFieldNames is null, but we have one in fields
                          if (values[name] == null && fields[name]?.val().trim() !== '') {
                            // and the old and new field for that name is not a select - use the fields value
                            newField = $(response.data.fieldsHtml).find('#' + Craft.namespaceId(name, $namespace));
                            if (
                              newField.length > 0 && 
                              fields[name].prop('nodeName') !== 'SELECT' && 
                              newField.prop('nodeName') !== 'SELECT'
                            ) {
                              values[name] = fields[name].val();
                            }
                          }
                        });
                        const \$addressFields = $(
                            Object.entries(fields)
                                .filter(([name]) => name !== 'countryCode')
                                .map(([, \$field]) => \$field.closest('.field')[0])
                        );
                        \$addressFields.eq(0).replaceWith(response.data.fieldsHtml);
                        \$addressFields.remove();
                        await Craft.appendHeadHtml(response.data.headHtml);
                        await Craft.appendBodyHtml(response.data.bodyHtml);
                        initFields(values);
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
        }

        return Cp::addressFieldsHtml($element, $static);
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        // Not actually needed since we're overriding formHtml()
        return null;
    }

    /**
     * @inheritdoc
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if ($element instanceof Address) {
            return $this->previewHtml($element);
        } else {
            $address = new Address([
                'countryCode' => 'US',
                'administrativeArea' => 'AK',
                'addressLine1' => 'Address Line 1',
                'locality' => 'Some City',
                'postalCode' => '12345',
            ]);
            return Html::tag('div', Craft::$app->getAddresses()->formatAddress($address), [
                'class' => 'no-truncate',
            ]);
        }
    }
}
