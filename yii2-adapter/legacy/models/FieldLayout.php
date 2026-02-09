<?php

declare(strict_types=1);

namespace craft\models;

use craft\base\ElementInterface;
use craft\base\Event as YiiEvent;
use craft\events\CreateFieldLayoutFormEvent;
use craft\events\DefineFieldLayoutCustomFieldsEvent;
use craft\events\DefineFieldLayoutElementsEvent;
use craft\events\DefineFieldLayoutFieldsEvent;
use CraftCms\Cms\FieldLayout\Events\CreateFieldLayoutForm;
use CraftCms\Cms\FieldLayout\Events\DefineCustomFields;
use CraftCms\Cms\FieldLayout\Events\DefineNativeFields;
use CraftCms\Cms\FieldLayout\Events\DefineUIElements;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use Deprecated;
use Generator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;

/**
 * FieldLayout model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\FieldLayout\FieldLayout} instead.
 */
class FieldLayout extends \CraftCms\Cms\FieldLayout\FieldLayout
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

    /**
     * @return array<string,array{html:string}>
     * @phpstan-ignore method.childReturnType
     */
    public function getCardBodyElements(?ElementInterface $element = null, array $cardElements = []): array
    {
        return Collection::make(parent::getCardBodyElements($element))
            ->mapWithKeys(function($html, $key) {
                return [$key => ['html' => $html]];
            })
            ->all();
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
        Event::listen(function(DefineCustomFields $event) {
            if (YiiEvent::hasHandlers(self::class, self::EVENT_DEFINE_CUSTOM_FIELDS)) {
                $yiiEvent = new DefineFieldLayoutCustomFieldsEvent(['fields' => $event->fields]);
                $yiiEvent->sender = $event->fieldLayout;
                YiiEvent::trigger(self::class, self::EVENT_DEFINE_CUSTOM_FIELDS, $yiiEvent);
                $event->fields = $yiiEvent->fields;
            }
        });

        Event::listen(function(DefineNativeFields $event) {
            if (YiiEvent::hasHandlers(self::class, self::EVENT_DEFINE_NATIVE_FIELDS)) {
                $yiiEvent = new DefineFieldLayoutFieldsEvent(['fields' => $event->fields]);
                $yiiEvent->sender = $event->fieldLayout;

                YiiEvent::trigger(self::class, self::EVENT_DEFINE_NATIVE_FIELDS, $yiiEvent);

                $event->fields = $yiiEvent->fields;
            }
        });

        Event::listen(function(DefineUIElements $event) {
            if (YiiEvent::hasHandlers(self::class, self::EVENT_DEFINE_UI_ELEMENTS)) {
                $yiiEvent = new DefineFieldLayoutElementsEvent(['elements' => $event->elements]);
                $yiiEvent->sender = $event->fieldLayout;

                YiiEvent::trigger(self::class, self::EVENT_DEFINE_UI_ELEMENTS, $yiiEvent);

                $event->elements = $yiiEvent->elements;
            }
        });

        Event::listen(function(CreateFieldLayoutForm $event) {
            if (YiiEvent::hasHandlers(self::class, self::EVENT_CREATE_FORM)) {
                $yiiEvent = new CreateFieldLayoutFormEvent([
                    'form' => $event->form,
                    'element' => $event->element,
                    'static' => $event->static,
                    'tabs' => $event->tabs,
                ]);
                $yiiEvent->sender = $event->fieldLayout;

                YiiEvent::trigger(self::class, self::EVENT_CREATE_FORM, $yiiEvent);

                $event->tabs = $yiiEvent->tabs;
                $event->static = $yiiEvent->static;
            }
        });
    }
}
