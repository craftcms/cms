<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements\Addresses;

use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\FieldLayout\Contracts\ImportableFieldLayoutElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Address as AddressControl;
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

    #[Override]
    protected function formControl(FieldLayoutElementContext $context): ?Control
    {
        if (! $context->element instanceof Address) {
            throw new InvalidArgumentException(sprintf('%s can only be used in address field layouts.', self::class));
        }

        return AddressControl::make('address')
            ->countryCode($context->element->countryCode)
            ->value($context->element->toArray([
                'addressLine1',
                'addressLine2',
                'addressLine3',
                'administrativeArea',
                'locality',
                'dependentLocality',
                'postalCode',
                'sortingCode',
            ]));
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
            [$prefixedHandleForMap, $prefixedHandleForMatchCriteria, $prefixedHandleForClear, $prefixedHandle, $prefixedHandleAsArray] = ImportHelper::getPrefixedHandlesForMapping($part['attribute'], $ownerField, null, $fieldLayout, $provider, $prefix);

            $subfields[] = [
                'handle' => $part['attribute'],
                'label' => $part['label'],
                'prefixedHandleForMap' => $prefixedHandleForMap,
                'prefixedHandleForMatchCriteria' => $prefixedHandleForMatchCriteria,
                'prefixedHandleForClear' => $prefixedHandleForClear,
                'prefixedHandle' => $prefixedHandle,
                'prefixedHandleAsArray' => $prefixedHandleAsArray,
                'isContainer' => false,
                'canBeMatchCriteria' => $part['canBeMatchCriteria'] ?? false,
                'canBeCleared' => $part['canBeCleared'] ?? true,
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

    #[Override]
    public function canBeCleared(): bool
    {
        // this is taken care of by the getFieldsForMapping() method
        return false;
    }
}
