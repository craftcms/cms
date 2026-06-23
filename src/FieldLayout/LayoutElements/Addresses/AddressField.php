<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements\Addresses;

use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\FieldLayout\Contracts\ImportableFieldLayoutElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\ImportHelper;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\t;

/**
 * AddressField represents an Address field that can be included within an Address field layout designer.
 */
class AddressField extends BaseField implements ImportableFieldLayoutElementInterface
{
    public function attribute(): string
    {
        return 'address';
    }

    #[Override]
    public function mandatory(): bool
    {
        return true;
    }

    #[Override]
    public function hasCustomWidth(): bool
    {
        return false;
    }

    #[Override]
    public function previewable(): bool
    {
        return true;
    }

    #[Override]
    public function previewHtml(ElementInterface $element): string
    {
        /** @var Address $element */
        return Html::tag('div', app(Addresses::class)->formatAddress($element), [
            'class' => 'no-truncate',
        ]);
    }

    #[Override]
    protected function showLabel(): bool
    {
        return false;
    }

    protected function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        // we need it for the card view designer
        return t('Address');
    }

    #[Override]
    protected function selectorLabel(): ?string
    {
        return t('Address');
    }

    #[Override]
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (! $element instanceof Address) {
            throw new InvalidArgumentException(sprintf('%s can only be used in address field layouts.', self::class));
        }

        if (! $static) {
            HtmlStack::jsWithVars(fn ($namespace) => <<<JS
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
                fields[name] = $('#' + Craft.namespaceId(name, $namespace))
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
                const spinner = $('#' + Craft.namespaceId(name + '-spinner', $namespace))
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
                            newField = $(response.data.fieldsHtml).find('#' + Craft.namespaceId(name, $namespace))
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
                InputNamespace::get(),
            ]);
        }

        return FormFields::addressFieldsHtml($element, $static);
    }

    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        // Not actually needed since we're overriding formHtml()
        return null;
    }

    #[Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if ($element instanceof Address) {
            return $this->previewHtml($element);
        }
        $address = new Address([
            'countryCode' => 'US',
            'administrativeArea' => 'AK',
            'addressLine1' => 'Address Line 1',
            'locality' => 'Some City',
            'postalCode' => '12345',
        ]);

        return Html::tag('div', app(Addresses::class)->formatAddress($address), [
            'class' => 'no-truncate',
        ]);
    }

    #[Override]
    public function getFieldsForMapping(FieldLayout $fieldLayout, ?FieldInterface $ownerField, mixed $provider, ?string $prefix = null): array
    {
        $cols = [
            'multiple' => true,
            'heading' => $this->label(),
        ];

        $subfields = [];

        // we have to show all the possible address fields as at this stage we have no idea which country we're importing for
        // and each row could be a different country anyway
        $parts = [
            ['attribute' => 'addressLine1', 'label' => t('Address Line 1')],
            ['attribute' => 'addressLine2', 'label' => t('Address Line 2')],
            ['attribute' => 'addressLine3', 'label' => t('Address Line 3')],
            ['attribute' => 'administrativeArea', 'label' => t('Administrative Area')],
            ['attribute' => 'locality', 'label' => t('Locality')],
            ['attribute' => 'dependentLocality', 'label' => t('Dependent Locality')],
            ['attribute' => 'postalCode', 'label' => t('Postal Code')],
            ['attribute' => 'sortingCode', 'label' => t('Sorting Code')],
        ];

        foreach ($parts as $part) {
            [$prefixedHandleForMap, $prefixedHandleForMatchCriteria, $prefixedHandle, $prefixedHandleAsArray] = ImportHelper::getPrefixedHandlesForMapping($part['attribute'], $ownerField, null, $fieldLayout, $provider, $prefix);

            $subfields[] = [
                'handle' => $part['attribute'],
                'label' => $part['label'],
                'prefixedHandleForMap' => $prefixedHandleForMap,
                'prefixedHandleForMatchCriteria' => $prefixedHandleForMatchCriteria,
                'prefixedHandle' => $prefixedHandle,
                'prefixedHandleAsArray' => $prefixedHandleAsArray,
                'isContainer' => false,
                'canBeMatchCriteria' => $part['canBeMatchCriteria'] ?? false,
            ];
        }

        $cols['subfields'] = $subfields;

        return $cols;
    }

    #[Override]
    public function canBeMatchCriteria(): bool
    {
        // this is taken care of by the getFieldsForMapping() method
        return false;
    }
}
