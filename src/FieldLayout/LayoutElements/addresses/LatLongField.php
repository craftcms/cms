<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements\addresses;

use craft\base\ElementInterface;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseNativeField;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\t;

class LatLongField extends BaseNativeField
{
    #[Override]
    public string $attribute = 'latLong';

    #[Override]
    public bool $requirable = true;

    public function __construct($config = [])
    {
        parent::__construct(Arr::except($config, [
            'mandatory',
            'translatable',
            'maxlength',
            'autofocus',
        ]));
    }

    #[Override]
    public function fields(): array
    {
        return Arr::except(parent::fields(), [
            'mandatory',
            'translatable',
            'maxlength',
            'autofocus',
        ]);
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
        return sprintf('%s, %s', $element->longitude ?? '0', $element->latitude ?? '0');
    }

    #[Override]
    protected function showLabel(): bool
    {
        return false;
    }

    protected function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        // we need it for the card view designer
        return t('Latitude/Longitude');
    }

    #[Override]
    protected function selectorLabel(): ?string
    {
        return t('Latitude/Longitude');
    }

    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (! $element instanceof Address) {
            throw new InvalidArgumentException(sprintf('%s can only be used in address field layouts.', self::class));
        }

        $isAdmin = Auth::user()?->isAdmin();

        return
            Html::beginTag('div', ['class' => 'flex-fields']).
            FormFields::textFieldHtml([
                'fieldClass' => 'width-50',
                'label' => t('Latitude'),
                'id' => 'latitude',
                'name' => 'latitude',
                'value' => $element->latitude,
                'required' => $this->required,
                'data' => [
                    'error-key' => 'latitude',
                ],
                'actionMenuItems' => array_filter([
                    $isAdmin ? $this->copyAttributeAction(['attribute' => 'latitude']) : null,
                ]),
            ]).
            FormFields::textFieldHtml([
                'fieldClass' => 'width-50',
                'label' => t('Longitude'),
                'id' => 'longitude',
                'name' => 'longitude',
                'value' => $element->longitude,
                'required' => $this->required,
                'data' => [
                    'error-key' => 'longitude',
                ],
                'actionMenuItems' => array_filter([
                    $isAdmin ? $this->copyAttributeAction(['attribute' => 'longitude']) : null,
                ]),
            ]).
            Html::endTag('div');
    }

    #[Override]
    protected function fieldErrors(?ElementInterface $element = null): array
    {
        if (! $element) {
            return [];
        }

        return array_merge(
            $element->errors()->get('latitude'),
            $element->errors()->get('longitude'),
        );
    }

    #[Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if ($element) {
            return $this->previewHtml($element);
        }

        return '61.108, -149.779';
    }
}
