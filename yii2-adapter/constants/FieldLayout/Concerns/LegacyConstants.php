<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Concerns;

use craft\base\Event as YiiEvent;
use craft\events\DefineFieldLayoutCustomFieldsEvent;
use craft\events\DefineFieldLayoutElementsEvent;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\models\FieldLayout;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\Events\FieldLayoutCustomFieldsResolving;
use CraftCms\Cms\FieldLayout\Events\FieldLayoutFormResolving;
use CraftCms\Cms\FieldLayout\Events\FieldLayoutUIElementsResolving;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\NativeFields;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Yii2Adapter\FieldLayout\LegacyFormEvents;
use Deprecated;
use Generator;
use Illuminate\Support\Facades\Event;

/**
 * @internal
 *
 * @deprecated 6.0.0
 *
 * @mixin \CraftCms\Cms\FieldLayout\FieldLayout
 *
 * @phpstan-ignore trait.unused
 */
trait LegacyConstants
{
    public const EVENT_DEFINE_CUSTOM_FIELDS = 'defineCustomFields';

    public const EVENT_DEFINE_NATIVE_FIELDS = 'defineNativeFields';

    public const EVENT_DEFINE_UI_ELEMENTS = 'defineUiElements';

    public const EVENT_CREATE_FORM = 'createForm';

    /**
     * @see getThumbField()
     */
    private BaseField|false $thumbField;

    /**
     * Returns the field layout’s designated thumbnail field.
     */
    #[Deprecated(message: 'in 5.9.6. [[hasThumbField()]] or [[getThumbHtmlForElement()]] should be used instead.')]
    public function getThumbField(): ?BaseField
    {
        if (!isset($this->thumbField)) {
            if (!isset($this->thumbFieldKey)) {
                return null;
            }

            $field = $this->getElementByKey($this->thumbFieldKey);
            if (!$field instanceof BaseField || !$field->thumbable()) {
                $this->thumbField = false;

                return null;
            }

            $this->thumbField = $field;
        }

        return $this->thumbField ?: null;
    }

    /**
     * Returns the custom fields that should be used in element card bodies.
     *
     * @return BaseField[]
     */
    #[Deprecated(message: 'in 5.9.0')]
    public function getCardBodyFields(?ElementInterface $element): array
    {
        $cardViewItems = array_flip($this->getCardView());

        /** @var BaseField[] */
        return iterator_to_array($this->_elements(fn(FieldLayoutElement $layoutElement) => (
            $layoutElement instanceof BaseField &&
            $layoutElement->previewable() &&
            (isset($cardViewItems[$layoutElement->attribute()]) || isset($cardViewItems["layoutElement:$layoutElement->uid"]))
        ), $element));
    }

    /**
     * Returns the attributes that should be used in element card bodies.
     */
    #[Deprecated(message: 'in 5.9.0')]
    public function getCardBodyAttributes(): array
    {
        $cardViewItems = array_flip($this->getCardView());

        // filter only the selected attributes
        $attributes = array_filter(
            $this->type::cardAttributes($this),
            fn($cardAttribute, $key) => isset($cardViewItems[$key]),
            ARRAY_FILTER_USE_BOTH
        );

        // ensure we have value set too (not just the label)
        array_walk($attributes, function(&$attribute, $key) {
            $attribute['value'] = $key;
        });

        return $attributes;
    }

    private function _elements(?callable $filter = null, ?ElementInterface $element = null): Generator
    {
        foreach ($this->getTabs() as $tab) {
            if (!$element || !isset($tab->uid) || $tab->showInForm($element)) {
                foreach ($tab->getElements() as $layoutElement) {
                    if (
                        (!$filter || $filter($layoutElement)) &&
                        (!$element || !isset($layoutElement->uid) || $layoutElement->showInForm($element))
                    ) {
                        yield $layoutElement;
                    }
                }
            }
        }
    }

    public static function registerEvents(): void
    {
        Event::listen(function(FieldLayoutFormResolving $event) {
            if (!YiiEvent::hasHandlers(FieldLayout::class, FieldLayout::EVENT_CREATE_FORM)) {
                return;
            }

            $static = $event->context->mode !== ControlMode::Editable;
            $legacyEvents = app(LegacyFormEvents::class);
            $yiiEvent = $legacyEvents->prepare($event->fieldLayout, $event->element, $event->context);

            if ($yiiEvent === null) {
                return;
            }

            try {
                $nodes = $event->form->nodes();
                $tabUids = array_map(fn($tab) => $tab->uid, $event->fieldLayout->getTabs());
                $ordered = [];

                foreach ($yiiEvent->tabs as $index => $tab) {
                    $node = array_find($nodes, fn(Node $node) => $node->uid() === $tab->uid)
                        ?? $legacyEvents->compileTab(
                            $event->fieldLayout,
                            $tab,
                            $event->element,
                            $event->context,
                            "yii2-adapter:event-tab:{$index}",
                        );

                    if ($node !== null) {
                        $ordered[] = $node;
                    }
                }
                $event->form = Form::make([
                    ...$ordered,
                    ...array_filter($nodes, fn(Node $node) => !in_array($node->uid(), $tabUids, true)),
                ]);

                if ($yiiEvent->static !== $static) {
                    self::setFormMode($event->form->nodes(), $yiiEvent->static ? ControlMode::ReadOnly : ControlMode::Editable);
                }
            } finally {
                $legacyEvents->forget($event->fieldLayout, $event->context);
            }
        });

        Event::listen(function(FieldLayoutCustomFieldsResolving $event) {
            if (YiiEvent::hasHandlers(FieldLayout::class, FieldLayout::EVENT_DEFINE_CUSTOM_FIELDS)) {
                $yiiEvent = new DefineFieldLayoutCustomFieldsEvent(['fields' => $event->fields]);
                $yiiEvent->sender = $event->fieldLayout;
                YiiEvent::trigger(FieldLayout::class, FieldLayout::EVENT_DEFINE_CUSTOM_FIELDS, $yiiEvent);
                $event->fields = $yiiEvent->fields;
            }
        });

        $nativeFields = app(NativeFields::class);
        $nativeFields->remove('yii2-adapter:legacy-events');
        $nativeFields->register('yii2-adapter:legacy-events', function(\CraftCms\Cms\FieldLayout\FieldLayout $fieldLayout, array $fields): array {
            if (!YiiEvent::hasHandlers(FieldLayout::class, FieldLayout::EVENT_DEFINE_NATIVE_FIELDS)) {
                return $fields;
            }

            $yiiEvent = new DefineFieldLayoutFieldsEvent(['fields' => $fields]);
            $yiiEvent->sender = $fieldLayout;

            YiiEvent::trigger(FieldLayout::class, FieldLayout::EVENT_DEFINE_NATIVE_FIELDS, $yiiEvent);

            return $yiiEvent->fields;
        });

        Event::listen(function(FieldLayoutUIElementsResolving $event) {
            if (YiiEvent::hasHandlers(FieldLayout::class, FieldLayout::EVENT_DEFINE_UI_ELEMENTS)) {
                $yiiEvent = new DefineFieldLayoutElementsEvent(['elements' => $event->elements]);
                $yiiEvent->sender = $event->fieldLayout;

                YiiEvent::trigger(FieldLayout::class, FieldLayout::EVENT_DEFINE_UI_ELEMENTS, $yiiEvent);

                $event->elements = $yiiEvent->elements;
            }
        });
    }

    /** @param list<Node> $nodes */
    private static function setFormMode(array $nodes, ControlMode $mode): void
    {
        foreach ($nodes as $node) {
            $node->getControl()?->mode($mode);
            self::setFormMode($node->children(), $mode);
        }
    }
}
