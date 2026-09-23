<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\FieldLayout\Concerns\ImportableFieldLayoutElement;
use CraftCms\Cms\FieldLayout\Contracts\ImportableFieldLayoutElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\Group;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\ImportHelper;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\t;

class FullNameField extends TextField implements ImportableFieldLayoutElementInterface
{
    use ImportableFieldLayoutElement {
        getFieldsForMapping as traitGetFieldsForMapping;
    }

    #[Override]
    public string $attribute = 'fullName';

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
    public function formNode(FieldLayoutElementContext $context): ?Node
    {
        $element = $context->element;

        if (
            ! $element ||
            ! Cms::config()->showFirstAndLastNameFields ||
            count(array_intersect($element->safeAttributes(), ['firstName', 'lastName'])) !== 2
        ) {
            return parent::formNode($context);
        }

        if (! $this->uid) {
            throw new InvalidArgumentException('Persisted Full Name FieldLayout elements require stable UIDs.');
        }

        // Both halves inherit the Full Name field’s change-tracking status.
        $static = $context->mode !== ControlMode::Editable;
        $status = $this->showStatus() ? $this->statusClass($element, $static) : null;
        $statusLabel = $status !== null
            ? ($this->statusLabel($element, $static) ?? ucfirst($status))
            : null;

        return Group::make($this->uid, [
            Field::make(t('First Name'), Text::make('firstName')->value($element->firstName ?? null))
                ->required($this->required)
                ->status($status, $statusLabel),
            Field::make(t('Last Name'), Text::make('lastName')->value($element->lastName ?? null))
                ->required($this->required)
                ->status($status, $statusLabel),
        ]);
    }

    #[Override]
    protected function settingsNodes(FormContext $context): array
    {
        if (Cms::config()->showFirstAndLastNameFields) {
            // can't know for sure if the element will support firstName and lastName, but probably?
            return [];
        }

        return parent::settingsNodes($context);
    }

    protected function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return t('Full Name');
    }

    #[Override]
    public function getFieldsForMapping(FieldLayout $fieldLayout, ?FieldInterface $ownerField, mixed $provider, ?string $prefix = null): array
    {
        if (! Cms::config()->showFirstAndLastNameFields) {
            return self::traitGetFieldsForMapping($fieldLayout, $ownerField, $provider, $prefix);
        }

        $cols = [
            'multiple' => true,
            'heading' => $this->label(),
        ];

        $subfields = [];

        $parts = [
            ['attribute' => 'firstName', 'label' => t('First Name'), 'canBeMatchCriteria' => true, 'canBeCleared' => true],
            ['attribute' => 'lastName', 'label' => t('Last Name'), 'canBeMatchCriteria' => true, 'canBeCleared' => true],
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
}
