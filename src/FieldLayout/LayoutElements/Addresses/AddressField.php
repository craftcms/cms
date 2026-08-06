<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements\Addresses;

use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Address as AddressControl;
use CraftCms\Cms\Support\Html;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\t;

/**
 * AddressField represents an Address field that can be included within an Address field layout designer.
 */
class AddressField extends BaseField
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
}
