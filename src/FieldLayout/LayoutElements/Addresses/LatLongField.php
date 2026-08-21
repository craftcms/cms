<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements\Addresses;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\FieldLayout\Contracts\ImportableFieldLayoutElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseNativeField;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\Group;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\ImportHelper;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

class LatLongField extends BaseNativeField implements ImportableFieldLayoutElementInterface
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
        if (! $element->longitude && ! $element->latitude) {
            return '';
        }

        return sprintf('%s, %s', $element->longitude ?? '0', $element->latitude ?? '0');
    }

    #[Override]
    public function formNode(FieldLayoutElementContext $context): ?Node
    {
        if (! $context->element instanceof Address) {
            throw new InvalidArgumentException(sprintf('%s can only be used in address field layouts.', self::class));
        }

        if (! $this->uid) {
            throw new InvalidArgumentException('Persisted Latitude/Longitude FieldLayout elements require stable UIDs.');
        }

        return Group::make($this->uid, [
            Field::make(t('Latitude'), Text::make('latitude')->value($context->element->latitude))
                ->required($this->required),
            Field::make(t('Longitude'), Text::make('longitude')->value($context->element->longitude))
                ->required($this->required),
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

        $isAdmin = currentUser()?->isAdmin();

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

    #[Override]
    public function getFieldsForMapping(FieldLayout $fieldLayout, ?FieldInterface $ownerField, mixed $provider, ?string $prefix = null): array
    {
        $cols = [
            'multiple' => true,
            'heading' => $this->label(),
        ];

        $subfields = [];

        $parts = [
            ['attribute' => 'latitude', 'label' => t('Latitude'), 'canBeMatchCriteria' => true, 'canBeCleared' => true],
            ['attribute' => 'longitude', 'label' => t('Longitude'), 'canBeMatchCriteria' => true, 'canBeCleared' => true],
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
                'canBeMatchCriteria' => $part['canBeMatchCriteria'],
                'canBeCleared' => $part['canBeCleared'],
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
