<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FieldLayoutDesigner;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Field\Exceptions\FieldNotFoundException;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json as JsonHelper;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;

use function CraftCms\Cms\t;

#[Singleton]
class FieldLayoutDesigner
{
    /** @param array<string, mixed> $config */
    public function html(FieldLayout $fieldLayout, array $config = []): string
    {
        $config += [
            'id' => 'fld'.mt_rand(),
            'customizableTabs' => true,
            'customizableUi' => true,
            'disabled' => false,
        ];

        $tabs = array_values($fieldLayout->getTabs());

        if (! $config['customizableTabs']) {
            $tab = array_shift($tabs) ?? new FieldLayoutTab([
                'uid' => Str::uuid()->toString(),
                'layout' => $fieldLayout,
            ]);
            $tab->name = $config['pretendTabName'] ?? FieldLayout::defaultTabName();

            // Any extra tabs?
            if (! empty($tabs)) {
                $elements = $tab->getElements();
                foreach ($tabs as $extraTab) {
                    array_push($elements, ...$extraTab->getElements());
                }
                $tab->setElements($elements);
            }

            $tabs = [$tab];
        }

        // Make sure all tabs and their elements have UUIDs
        // (We do this here instead of from FieldLayoutComponent::init() because the we don't want field layout forms to
        // get the impression that tabs/elements have persisting UUIDs if they don't.)
        foreach ($tabs as $tab) {
            if (! isset($tab->uid)) {
                $tab->uid = Str::uuid()->toString();
            }

            $layoutElements = [];

            foreach ($tab->getElements() as $layoutElement) {
                // If this is a custom field, make sure the field still exists
                if ($layoutElement instanceof CustomField) {
                    try {
                        $layoutElement->getField();
                    } catch (FieldNotFoundException) {
                        continue;
                    }
                }

                if (! isset($layoutElement->uid)) {
                    $layoutElement->uid = Str::uuid()->toString();
                }

                $layoutElements[] = $layoutElement;
            }

            $tab->setElements($layoutElements);
        }

        $settings = [
            'elementType' => $fieldLayout->type,
            'customizableTabs' => $config['customizableTabs'],
            'customizableUi' => $config['customizableUi'],
            'withCardViewDesigner' => $config['withCardViewDesigner'] ?? false,
            'alwaysShowThumbAlignmentBtns' => $fieldLayout->type::hasThumbs(),
            'readOnly' => $config['disabled'],
        ];

        $availableCustomFields = $fieldLayout->getAvailableCustomFields();
        $availableNativeFields = $fieldLayout->getAvailableNativeFields();
        $availableUiElements = $fieldLayout->getAvailableUiElements();

        // Make sure everything has the field layout set properly
        foreach ($availableCustomFields as $groupFields) {
            $this->setLayoutOnElements($groupFields, $fieldLayout);
        }
        $this->setLayoutOnElements($availableNativeFields, $fieldLayout);
        $this->setLayoutOnElements($availableUiElements, $fieldLayout);

        $fieldLayoutConfig = [
            'uid' => $fieldLayout->uid,
            ...(array) $fieldLayout->getConfig(),
        ];

        // Default `dateAdded` to a minute ago for each element, so there's no chance that an element that predated 5.3
        // would get the same timestamp as a newly-added element, if the layout was saved within a minute of being
        // edited, after updating to Craft 5.3+.
        if (isset($fieldLayoutConfig['tabs'])) {
            foreach ($fieldLayoutConfig['tabs'] as &$tabConfig) {
                foreach ($tabConfig['elements'] as &$elementConfig) {
                    if (! isset($elementConfig['dateAdded'])) {
                        $elementConfig['dateAdded'] = DateTimeHelper::toIso8601(now()->subMinute());
                    }
                }
            }
        }

        if ($fieldLayout->id) {
            $fieldLayoutConfig['id'] = $fieldLayout->id;
        }

        if ($fieldLayout->type) {
            $fieldLayoutConfig['type'] = $fieldLayout->type;
        }

        return view('c::forms.fld.designer', [
            'designer' => $this,
            'id' => $config['id'],
            'settings' => $settings,
            'fieldLayoutConfig' => $fieldLayoutConfig,
            'fieldLayout' => $fieldLayout,
            'tabs' => $tabs,
            'customizableTabs' => $config['customizableTabs'],
            'customizableUi' => $config['customizableUi'],
            'disabled' => $config['disabled'],
            'availableNativeFields' => $availableNativeFields,
            'availableCustomFields' => $availableCustomFields,
            'availableUiElements' => $availableUiElements,
        ])->render();
    }

    /** @param array<string, mixed> $config */
    public function fieldHtml(FieldLayout $fieldLayout, array $config = []): string
    {
        $config += [
            'withGeneratedFields' => false,
            'withCardViewDesigner' => false,
            'disabled' => false,
        ];

        return view('c::forms.fld.field', [
            'designer' => $this,
            'cardDesigner' => app(CardDesigner::class),
            'fieldLayout' => $fieldLayout,
            'config' => $config,
            'withGeneratedFields' => $config['withGeneratedFields'],
            'withCardViewDesigner' => $config['withCardViewDesigner'],
            'disabled' => $config['disabled'],
        ])->render();
    }

    /** @param array<string, mixed> $attributes */
    public function layoutElementSelectorHtml(
        FieldLayoutElement $element,
        bool $forLibrary = false,
        array $attributes = [],
    ): string {
        // ignore invalid custom fields
        if ($element instanceof CustomField) {
            try {
                $element->getField();
            } catch (FieldNotFoundException) {
                return '';
            }
        }

        if ($element instanceof BaseField) {
            $attributes = Arr::merge($attributes, [
                'data' => [
                    'keywords' => $forLibrary ? implode(' ', array_map(mb_strtolower(...), $element->keywords())) : false,
                ],
            ]);
        }

        if ($element instanceof CustomField) {
            $originalField = app(Fields::class)->getFieldByUid($element->getFieldUid());
            if ($originalField) {
                $attributes['data']['default-handle'] = $originalField->handle;
            }
        }

        $attributes = Arr::merge($attributes, [
            'class' => array_filter([
                'fld-element',
                $forLibrary ? 'unused' : null,
            ]),
            'data' => [
                'uid' => ! $forLibrary ? $element->uid : false,
                'config' => $forLibrary ? ['type' => $element::class] + $element->toArray() : false,
                'ui-label' => $forLibrary && $element instanceof CustomField ? $element->getField()->getUiLabel() : false,
                'is-multi-instance' => $element->isMultiInstance(),
                'has-custom-width' => $element->hasCustomWidth(),
                'has-settings' => $element->hasSettings(),
            ],
        ]);

        return Html::modifyTagAttributes($element->selectorHtml(), $attributes);
    }

    /** @param array<string, mixed> $config */
    public function generatedFieldsTableHtml(FieldLayout $fieldLayout, array $config = []): string
    {
        $config += [
            'id' => sprintf('generated-fields-table-%s', mt_rand()),
            'disabled' => false,
        ];

        $name = 'generatedFields';

        $cols = [
            'name' => [
                'heading' => t('Name'),
                'type' => 'singleline',
                'width' => '15%',
            ],
            'handle' => [
                'heading' => t('Handle'),
                'type' => 'singleline',
                'code' => true,
                'width' => '15%',
            ],
            'template' => [
                'heading' => t('Template'),
                'type' => 'multiline',
                'code' => true,
                'info' => SelectOptions::getObjectTemplateTip(),
                'textExpanderTriggers' => SelectOptions::getObjectTemplateTextExpanderTriggers(
                    $fieldLayout->type,
                    [$fieldLayout],
                ),
            ],
        ];

        $rows = array_map(function (array $field) {
            if (isset($field['uid'])) {
                $field['hiddenInputs'] = [
                    'uid' => $field['uid'],
                ];
            }

            return $field;
        }, $fieldLayout->getGeneratedFields());

        $settings = [
            'allowAdd' => true,
            'allowReorder' => true,
            'allowDelete' => true,
            'static' => $config['disabled'],
        ];

        $table = FormFields::editableTableHtml([
            'id' => $config['id'],
            'name' => $name,
            'cols' => $cols,
            'rows' => $rows,
            'addRowLabel' => t('Add a field'),
            'static' => $config['disabled'],
            'initJs' => false,
            ...$settings,
        ]);

        // No `id` on the wrapper: the element reads its child <table>'s id (the
        // one `EditableTable` resolves via `$('#'+id)`), and a matching id here
        // would shadow that table for `getElementById` when no input namespace
        // is active.
        return Html::tag('craft-generated-fields-table', $table, [
            'name' => InputNamespace::namespaceInputName($name),
            'cols' => JsonHelper::encode($cols),
            'settings' => JsonHelper::encode($settings),
        ]);
    }

    /**
     * @param  FieldLayoutElement[]  $elements
     */
    private function setLayoutOnElements(array $elements, FieldLayout $fieldLayout): void
    {
        foreach ($elements as $element) {
            $element->setLayout($fieldLayout);
        }
    }

    public function fldTabHtml(FieldLayoutTab $tab, bool $customizable, bool $disabled): string
    {
        return view('c::forms.fld.tab', [
            'designer' => $this,
            'tab' => $tab,
            'customizable' => $customizable,
            'disabled' => $disabled,
        ])->render();
    }

    /**
     * @param  BaseField[]  $groupFields
     */
    public function fldFieldSelectorsHtml(string $groupName, array $groupFields, FieldLayout $fieldLayout): string
    {
        $showGroup = Collection::make($groupFields)->contains(
            fn (BaseField $field) => $this->showFldFieldSelector($fieldLayout, $field),
        );

        return
            Html::beginTag('div', [
                'class' => array_filter([
                    'fld-field-group',
                    $showGroup ? null : 'hidden',
                ]),
                'data' => ['name' => mb_strtolower($groupName)],
            ]).
            Html::tag('h6', Html::encode($groupName)).
            implode('', array_map(fn (BaseField $field) => $this->layoutElementSelectorHtml($field, true, [
                'class' => array_filter([
                    ! $this->showFldFieldSelector($fieldLayout, $field) ? 'hidden' : null,
                ]),
            ]), $groupFields)).
            Html::endTag('div'); // .fld-field-group
    }

    private function showFldFieldSelector(FieldLayout $fieldLayout, BaseField $field): bool
    {
        $attribute = $field->attribute();
        $uid = $field instanceof CustomField ? $field->getField()->uid : null;
        if ($field->isMultiInstance()) {
            return true;
        }

        return ! $fieldLayout->isFieldIncluded(function (BaseField $field) use ($attribute, $uid) {
            if ($field instanceof CustomField) {
                return $field->getFieldUid() === $uid;
            }

            return $field->attribute() === $attribute;
        });
    }

    public function showFldUiElementSelector(FieldLayout $fieldLayout, FieldLayoutElement $uiElement): bool
    {
        if ($uiElement->isMultiInstance()) {
            return true;
        }

        return ! $fieldLayout->isUiElementIncluded(
            fn (FieldLayoutElement $element) => $uiElement::class === $element::class
        );
    }
}
