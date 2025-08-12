<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\base\FieldLayoutElement;
use craft\base\FieldLayoutProviderInterface;
use craft\base\Model;
use craft\errors\FieldNotFoundException;
use craft\events\CreateFieldLayoutFormEvent;
use craft\events\DefineFieldLayoutCustomFieldsEvent;
use craft\events\DefineFieldLayoutElementsEvent;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\fieldlayoutelements\BaseField;
use craft\fieldlayoutelements\BaseUiElement;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\Heading;
use craft\fieldlayoutelements\HorizontalRule;
use craft\fieldlayoutelements\LineBreak;
use craft\fieldlayoutelements\Markdown;
use craft\fieldlayoutelements\Template;
use craft\fieldlayoutelements\Tip;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\validators\HandleValidator;
use Generator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

/**
 * FieldLayout model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class FieldLayout extends Model
{
    /**
     * @event DefineFieldLayoutFieldsEvent The event that is triggered when defining the native (not custom) fields for the layout.
     *
     * ```php
     * use craft\models\FieldLayout;
     * use craft\events\DefineFieldLayoutFieldsEvent;
     * use yii\base\Event;
     *
     * Event::on(
     *     FieldLayout::class,
     *     FieldLayout::EVENT_DEFINE_NATIVE_FIELDS,
     *     function(DefineFieldLayoutFieldsEvent $event) {
     *         // @var FieldLayout $layout
     *         $layout = $event->sender;
     *
     *         if ($layout->type === MyElementType::class) {
     *             $event->fields[] = MyNativeField::class;
     *         }
     *     }
     * );
     * ```
     *
     * @see getAvailableNativeFields()
     * @since 4.0.0
     */
    public const EVENT_DEFINE_NATIVE_FIELDS = 'defineNativeFields';

    /**
     * @event DefineFieldLayoutCustomFieldsEvent The event that is triggered when defining the custom fields for the layout.
     *
     * Note that fields set on [[DefineFieldLayoutCustomFieldsEvent::$fields]] will be grouped by field group, indexed by the group names.
     *
     * ```php
     * use craft\models\FieldLayout;
     * use craft\events\DefineFieldLayoutFieldsEvent;
     * use craft\fields\PlainText;
     * use yii\base\Event;
     *
     * Event::on(
     *     FieldLayout::class,
     *     FieldLayout::EVENT_DEFINE_CUSTOM_FIELDS,
     *     function(DefineFieldLayoutFieldsEvent $event) {
     *         // @var FieldLayout $layout
     *         $layout = $event->sender;
     *
     *         if ($layout->type === MyElementType::class) {
     *             // Only allow Plain Text fields
     *             foreach ($event->fields as $groupName => &$fields) {
     *                 $fields = array_filter($fields, fn($field) => $field instanceof PlainText);
     *             }
     *         }
     *     }
     * );
     * ```
     *
     * @see getAvailableCustomFields()
     * @since 4.2.0
     */
    public const EVENT_DEFINE_CUSTOM_FIELDS = 'defineCustomFields';

    /**
     * @event DefineFieldLayoutElementsEvent The event that is triggered when defining UI elements for the layout.
     *
     * ```php
     * use craft\models\FieldLayout;
     * use craft\events\DefineFieldLayoutElementsEvent;
     * use yii\base\Event;
     *
     * Event::on(
     *     FieldLayout::class,
     *     FieldLayout::EVENT_DEFINE_UI_ELEMENTS,
     *     function(DefineFieldLayoutElementsEvent $event) {
     *         $event->elements[] = MyUiElement::class;
     *     }
     * );
     * ```
     *
     * @see getAvailableUiElements()
     * @since 3.5.0
     */
    public const EVENT_DEFINE_UI_ELEMENTS = 'defineUiElements';

    /**
     * @event CreateFieldLayoutFormEvent The event that is triggered when creating a new field layout form.
     *
     * ```php
     * use craft\elements\Entry;
     * use craft\events\CreateFieldLayoutFormEvent;
     * use craft\fieldlayoutelements\HorizontalRule;
     * use craft\fieldlayoutelements\StandardTextField;
     * use craft\fieldlayoutelements\Template;
     * use craft\models\FieldLayout;
     * use craft\models\FieldLayoutTab;
     * use yii\base\Event;
     *
     * Event::on(
     *     FieldLayout::class,
     *     FieldLayout::EVENT_CREATE_FORM,
     *     function(CreateFieldLayoutFormEvent $event) {
     *         if ($event->element instanceof Entry) {
     *             $event->tabs[] = new FieldLayoutTab([
     *                 'name' => 'My Tab',
     *                 'elements' => [
     *                     new StandardTextField([
     *                         'attribute' => 'myTextField',
     *                         'label' => 'My Text Field',
     *                     ]),
     *                     new HorizontalRule(),
     *                     new Template([
     *                         'template' => '_layout-elements/info'
     *                     ]),
     *                 ],
     *             ]);
     *         }
     *     }
     * );
     * ```
     *
     * @see createForm()
     * @since 3.6.0
     */
    public const EVENT_CREATE_FORM = 'createForm';

    /**
     * Creates a new field layout from the given config.
     *
     * @param array $config
     * @return self
     * @since 3.1.0
     */
    public static function createFromConfig(array $config): self
    {
        $tabConfigs = ArrayHelper::remove($config, 'tabs');
        $layout = new self($config);

        if (is_array($tabConfigs)) {
            $layout->setTabs(array_values(array_map(
                fn(array $tabConfig) => FieldLayoutTab::createFromConfig(['layout' => $layout] + $tabConfig),
                $tabConfigs,
            )));
        } else {
            $layout->setTabs([]);
        }

        return $layout;
    }

    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var class-string<ElementInterface>|null The element type
     */
    public ?string $type = null;

    /**
     * @var string UID
     */
    public string $uid;

    /**
     * @var FieldLayoutProviderInterface|null The field layout’s provider.
     * @since 4.5.0
     */
    public ?FieldLayoutProviderInterface $provider = null;

    /**
     * @var string[]|null Reserved custom field handles
     * @since 3.7.0
     */
    public ?array $reservedFieldHandles = null;

    /**
     * @var BaseField[][]
     * @see getAvailableCustomFields()
     */
    private array $_availableCustomFields;

    /**
     * @var BaseField[]
     * @see getAvailableNativeFields()
     */
    private array $_availableNativeFields;

    /**
     * @var FieldLayoutTab[]
     * @see getTabs()
     * @see setTabs()
     */
    private array $_tabs;

    /**
     * @var FieldInterface[]
     * @see getCustomFields()
     */
    private ?array $_customFields = null;

    /**
     * @var array<string,FieldInterface>|null
     * @see getFieldByHandle()
     */
    private ?array $_indexedCustomFields = null;

    /**
     * @var array|null
     * @see getGeneratedFields()
     * @see setGeneratedFields()
     */
    private ?array $_generatedFields = null;

    /**
     * @var array
     * @see getCardView()
     * @see setCardView()
     */
    private array $_cardView;

    /**
     * @var string
     * @see getCardThumbAlignment()
     * @see setCardThumbAlignment()
     */
    private string $_cardThumbAlignment;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->uid)) {
            $this->uid = StringHelper::UUID();
        }

        if (!isset($this->_tabs)) {
            // go through setTabs() so any mandatory fields get added
            $this->setTabs([]);
        }

        if (!isset($this->_cardView)) {
            if ($this->type && class_exists($this->type)) {
                $this->setCardView($this->type::defaultCardAttributes());
            } else {
                $this->setCardView([]);
            }
        }

        if (!isset($this->_cardThumbAlignment)) {
            $this->setCardThumbAlignment();
        }
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id'], 'number', 'integerOnly' => true];
        $rules[] = [['customFields'], 'validateFields', 'skipOnEmpty' => false];
        return $rules;
    }

    /**
     * Validates the field selections.
     *
     * @since 3.7.0
     */
    public function validateFields(): void
    {
        // Make sure no field handles are duplicated or using one of our reserved attribute names
        $handles = [];

        foreach ($this->getCustomFields() as $field) {
            if (isset($this->reservedFieldHandles) && in_array($field->handle, $this->reservedFieldHandles, true)) {
                $this->addError('customFields', Craft::t('app', '“{handle}” is a reserved word.', [
                    'handle' => $field->handle,
                ]));
            } elseif (isset($handles[$field->handle])) {
                $this->addError('customFields', Craft::t('yii', '{attribute} "{value}" has already been taken.', [
                    'attribute' => Craft::t('app', 'Handle'),
                    'value' => $field->handle,
                ]));
            } else {
                $handles[$field->handle] = true;
            }
        }

        $generatedFields = $this->getGeneratedFields();

        if (!empty($generatedFields)) {
            $validator = new HandleValidator([
                'reservedWords' => [
                    ...Field::RESERVED_HANDLES,
                    ...(array)$this->reservedFieldHandles,
                ],
            ]);

            foreach ($generatedFields as &$field) {
                $field['name'] = trim($field['name'] ?? '');
                $field['handle'] = trim($field['handle'] ?? '');
                $field['template'] = trim($field['template'] ?? '');

                if ($field['handle'] === '') {
                    continue;
                }

                $error = null;
                $validator->validate($field['handle'], $error);
                if ($error === null && isset($handles[$field['handle']])) {
                    $error = Craft::t('yii', '{attribute} "{value}" has already been taken.', [
                        'attribute' => Craft::t('app', 'Handle'),
                        'value' => $field['handle'],
                    ]);
                }

                if ($error !== null) {
                    $this->addError('generatedFields', $error);
                    $field['handle'] = [
                        'value' => $field['handle'],
                        'hasErrors' => true,
                    ];
                } else {
                    $handles[$field['handle']] = true;
                }
            }

            $this->setGeneratedFields($generatedFields);
        }
    }

    /**
     * Returns the layout’s tabs.
     *
     * @return FieldLayoutTab[] The layout’s tabs.
     */
    public function getTabs(): array
    {
        if (!isset($this->_tabs)) {
            // go through setTabs() so any mandatory fields get added
            $this->setTabs([]);
        }

        return $this->_tabs;
    }

    /**
     * Sets the layout’s tabs.
     *
     * @param array $tabs An array of the layout’s tabs, which can either be FieldLayoutTab objects or arrays defining the tab’s attributes.
     * @phpstan-param array<array|FieldLayoutTab> $tabs
     */
    public function setTabs(array $tabs): void
    {
        $this->_tabs = [];

        $index = 0;

        foreach ($tabs as $tab) {
            if (is_array($tab)) {
                // Set the layout before anything else
                $tab = ['layout' => $this] + $tab;
                $tab = new FieldLayoutTab($tab);
            } else {
                $tab->setLayout($this);
            }

            $tab->sortOrder = ++$index;
            $this->_tabs[] = $tab;
        }

        // Make sure that we aren't missing any mandatory fields
        $includedFields = [];
        $missingFields = [];

        foreach ($this->getElementsByType(BaseField::class) as $field) {
            try {
                /** @var BaseField $field */
                $includedFields[$field->attribute()] = true;
            } catch (FieldNotFoundException) {
                // move on
            }
        }

        foreach ($this->getAvailableNativeFields() as $field) {
            if ($field->mandatory()) {
                $attribute = $field->attribute();
                if (!isset($includedFields[$attribute])) {
                    $missingFields[] = $field;
                    $includedFields[$attribute] = true;
                }
            }
        }

        if (!empty($missingFields)) {
            $this->prependElements($missingFields);
        }

        // Clear caches
        $this->reset();
    }

    /**
     * Returns the layout’s generated fields.
     *
     * @return array
     * @since 5.8.0
     */
    public function getGeneratedFields(): array
    {
        return $this->_generatedFields ?? [];
    }

    /**
     * Returns a generated field by its UUID.
     *
     * @return array|null
     * @since 5.8.0
     */
    public function getGeneratedFieldByUid(string $uid): ?array
    {
        foreach ($this->getGeneratedFields() as $field) {
            if ($field['uid'] === $uid) {
                return $field;
            }
        }

        return null;
    }

    /**
     * Sets the layout’s generated fields.
     *
     * @param array|null $fields An array of the layout’s generated fields.
     * @since 5.8.0
     */
    public function setGeneratedFields(?array $fields): void
    {
        if (!empty($fields)) {
            foreach ($fields as &$field) {
                // make sure it has a UUID
                $field['uid'] ??= StringHelper::UUID();
            }
            $fields = array_values($fields);
        } else {
            $fields = null;
        }

        $this->_generatedFields = $fields;
    }

    /**
     * Returns the layout’s card view makeup.
     *
     * @return array The layout’s card view makeup.
     * @since 5.5.0
     */
    public function getCardView(): array
    {
        if (!isset($this->_cardView)) {
            $this->setCardView([]);
        }

        return $this->_cardView;
    }

    /**
     * Sets the layout’s card view makeup.
     *
     * @param array|null $items An array of the layout’s card view items
     * @since 5.5.0
     */
    public function setCardView(?array $items): void
    {
        $this->_cardView = [];

        if ($items !== null) {
            foreach ($items as $item) {
                $this->_cardView[] = $item;
            }
        }

        // Clear caches
        $this->reset();
    }

    /**
     * Returns the thumbnail alignment that should be used in element cards.
     *
     * @return string `start` or `end`
     * @since 5.8.0
     */
    public function getCardThumbAlignment(): string
    {
        if (!isset($this->_cardThumbAlignment)) {
            $this->setCardThumbAlignment();
        }

        return $this->_cardThumbAlignment;
    }

    /**
     * Sets the thumbnail alignment that should be used in element cards.
     *
     * @param string|null $alignment `start` or `end`
     * @since 5.8.0
     */
    public function setCardThumbAlignment(?string $alignment = null): void
    {
        $validOptions = ['start', 'end'];

        if (!in_array($alignment, $validOptions)) {
            $alignment = null;
        }

        $this->_cardThumbAlignment = $alignment ?? 'end';
    }

    /**
     * Returns the available fields, grouped by field group name.
     *
     * @return BaseField[][]
     * @since 3.5.0
     */
    public function getAvailableCustomFields(): array
    {
        if (!isset($this->_availableCustomFields)) {
            $customFields = [];

            foreach (Craft::$app->getFields()->getAllFields() as $field) {
                $customFields[] = Craft::createObject([
                    'class' => CustomField::class,
                    'layout' => $this,
                ], [$field]);
            }

            $this->_availableCustomFields = [
                Craft::t('app', 'Custom Fields') => $customFields,
            ];

            // Fire a 'defineCustomFields' event
            if ($this->hasEventHandlers(self::EVENT_DEFINE_CUSTOM_FIELDS)) {
                $event = new DefineFieldLayoutCustomFieldsEvent(['fields' => $this->_availableCustomFields]);
                $this->trigger(self::EVENT_DEFINE_CUSTOM_FIELDS, $event);
                $this->_availableCustomFields = $event->fields;
            }
        }

        return $this->_availableCustomFields;
    }

    /**
     * Returns the available native fields.
     *
     * @return BaseField[]
     * @since 3.5.0
     */
    public function getAvailableNativeFields(): array
    {
        if (!isset($this->_availableNativeFields)) {
            $this->_availableNativeFields = [];

            // Fire a 'defineNativeFields' event
            if ($this->hasEventHandlers(self::EVENT_DEFINE_NATIVE_FIELDS)) {
                $event = new DefineFieldLayoutFieldsEvent();
                $this->trigger(self::EVENT_DEFINE_NATIVE_FIELDS, $event);

                // Instantiate them
                foreach ($event->fields as $field) {
                    if (is_string($field) || is_array($field)) {
                        $field = Craft::createObject($field);
                    }
                    if (!$field instanceof BaseField) {
                        throw new InvalidConfigException('Invalid standard field config');
                    }
                    $field->setLayout($this);
                    $this->_availableNativeFields[] = $field;
                }
            }
        }

        return $this->_availableNativeFields;
    }

    /**
     * Returns the layout elements that are available to the field layout, grouped by the type name and (optionally) group name.
     *
     * @return FieldLayoutElement[]
     * @since 3.5.0
     */
    public function getAvailableUiElements(): array
    {
        $elements = [
            new Heading(),
            new Tip(['style' => Tip::STYLE_TIP]),
            new Tip(['style' => Tip::STYLE_WARNING]),
            new Markdown(),
            new Template(),
        ];

        // Fire a 'defineUiElements' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_UI_ELEMENTS)) {
            $event = new DefineFieldLayoutElementsEvent(['elements' => $elements]);
            $this->trigger(self::EVENT_DEFINE_UI_ELEMENTS, $event);
            $elements = $event->elements;
        }

        // HR and Line Break should always be last
        $elements[] = new HorizontalRule();
        $elements[] = new LineBreak();

        // Instantiate them
        foreach ($elements as &$element) {
            if (is_string($element) || is_array($element)) {
                $element = Craft::createObject($element);
            }
            if (!$element instanceof FieldLayoutElement) {
                throw new InvalidConfigException('Invalid UI element config');
            }
        }

        return $elements;
    }

    /**
     * Returns whether a field is included in the layout by a callback or its attribute
     *
     * @param callable|string $filter
     * @return bool
     * @since 3.5.0
     */
    public function isFieldIncluded(callable|string $filter): bool
    {
        try {
            $this->getField($filter);
            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Returns whether UI Element is included in the field layout.
     *
     * @param callable $filter
     * @return bool
     * @since 5.7.12
     */
    public function isUiElementIncluded(callable $filter): bool
    {
        $element = $this->_element(fn(FieldLayoutElement $layoutElement) => (
            $layoutElement instanceof BaseUiElement &&
            $filter($layoutElement)
        ));

        if (!$element) {
            return false;
        }

        return true;
    }

    /**
     * Returns a field that’s included in the layout by a callback or its attribute name.
     *
     * @param callable|string $filter
     * @return BaseField
     * @throws InvalidArgumentException if the field isn’t included
     * @since 3.5.0
     */
    public function getField(callable|string $filter): BaseField
    {
        if (is_string($filter)) {
            $attribute = $filter;
            $filter = fn(BaseField $field) => $field->attribute() === $attribute;
        }

        /** @var BaseField|null $field */
        $field = $this->_element(fn(FieldLayoutElement $layoutElement) => (
            $layoutElement instanceof BaseField &&
            $filter($layoutElement)
        ));

        if (!$field) {
            throw new InvalidArgumentException(isset($attribute) ? "Invalid field: $attribute" : 'Invalid field');
        }

        return $field;
    }

    /**
     * Returns all fields in the layout that match a given callback.
     *
     * @param callable $filter
     * @return BaseField[]
     * @throws InvalidArgumentException if the field isn’t included
     * @since 5.8.0
     */
    public function getFields(callable $filter): array
    {
        return iterator_to_array($this->_elements(fn(FieldLayoutElement $layoutElement) => (
            $layoutElement instanceof BaseField &&
            $filter($layoutElement)
        )));
    }

    /**
     * Returns the field layout’s config.
     *
     * @return array|null
     * @since 3.1.0
     */
    public function getConfig(): ?array
    {
        $tabConfigs = array_map(
            fn(FieldLayoutTab $tab) => $tab->getConfig(),
            $this->getTabs(),
        );

        $generatedFields = $this->getGeneratedFields();
        $cardViewConfig = $this->getCardView();
        $cardThumbAlignment = $this->getCardThumbAlignment();

        if (empty($generatedFields) && empty($tabConfigs) && empty($cardViewConfig)) {
            // no point bothering with the thumb alignment if we don't have the card view
            return null;
        }

        return [
            'tabs' => $tabConfigs,
            'generatedFields' => $generatedFields,
            'cardView' => $cardViewConfig,
            'cardThumbAlignment' => $cardThumbAlignment,
        ];
    }

    /**
     * Resets the field layout’s UUIDs.
     *
     * @since 5.8.0
     */
    public function resetUids(): void
    {
        $this->uid = StringHelper::UUID();
        $cardView = $this->getCardView();

        foreach ($this->getTabs() as $tab) {
            $tab->uid = StringHelper::UUID();

            foreach ($tab->getElements() as $element) {
                $oldUid = $element->uid;
                $element->uid = StringHelper::UUID();

                $cardViewPos = array_search("layoutElement:$oldUid", $cardView);
                if ($cardViewPos !== false) {
                    $cardView[$cardViewPos] = "layoutElement:$element->uid";
                }
            }
        }

        $this->setCardView($cardView);
    }

    /**
     * Returns a layout element by its UID.
     *
     * @param string $uid
     * @return FieldLayoutElement|null
     * @since 5.0.0
     */
    public function getElementByUid(string $uid): ?FieldLayoutElement
    {
        $filter = fn(FieldLayoutElement $layoutElement) => $layoutElement->uid === $uid;
        return $this->_element($filter);
    }

    /**
     * Returns the layout elements of a given type.
     *
     * @return FieldLayoutElement[]
     * @since 5.3.0
     */
    public function getAllElements(): array
    {
        return iterator_to_array($this->_elements());
    }

    /**
     * Returns the layout elements of a given type.
     *
     * @template T
     * @param class-string<T> $class
     * @return T[]
     * @since 4.0.0
     */
    public function getElementsByType(string $class): array
    {
        $filter = fn(FieldLayoutElement $layoutElement) => $layoutElement instanceof $class;
        return iterator_to_array($this->_elements($filter));
    }

    /**
     * Returns the visible layout elements of a given type, taking conditions into account.
     *
     * @template T
     * @param class-string<T> $class
     * @param ElementInterface $element
     * @return T[]
     * @since 4.0.0
     */
    public function getVisibleElementsByType(string $class, ElementInterface $element): array
    {
        $filter = fn(FieldLayoutElement $layoutElement) => $layoutElement instanceof $class;
        return iterator_to_array($this->_elements($filter, $element));
    }

    /**
     * Returns the first layout element of a given type.
     *
     * @template T of FieldLayoutElement
     * @param class-string<T> $class
     * @return T|null The layout element, or `null` if none were found
     * @since 4.0.0
     */
    public function getFirstElementByType(string $class): ?FieldLayoutElement
    {
        $filter = fn(FieldLayoutElement $layoutElement) => $layoutElement instanceof $class;
        return $this->_element($filter);
    }

    /**
     * Returns the first visible layout element of a given type, taking conditions into account.
     *
     * @template T of FieldLayoutElement
     * @param class-string<T> $class
     * @param ElementInterface $element
     * @return T|null The layout element, or `null` if none were found
     * @since 4.0.0
     */
    public function getFirstVisibleElementByType(string $class, ElementInterface $element): ?FieldLayoutElement
    {
        $filter = fn(FieldLayoutElement $layoutElement) => $layoutElement instanceof $class;
        return $this->_element($filter, $element);
    }

    /**
     * Returns the layout elements representing custom fields.
     *
     * @return CustomField[]
     * @since 3.7.27
     */
    public function getCustomFieldElements(): array
    {
        return Collection::make($this->getElementsByType(CustomField::class))
            ->filter(function(CustomField $layoutElement) {
                try {
                    $layoutElement->getField();
                } catch (FieldNotFoundException) {
                    return false;
                }
                return true;
            })
            ->values()
            ->all();
    }

    /**
     * Returns the visible layout elements representing custom fields, taking conditions into account.
     *
     * @param ElementInterface $element
     * @return CustomField[]
     * @since 4.1.4
     */
    public function getVisibleCustomFieldElements(ElementInterface $element): array
    {
        return iterator_to_array($this->_elements(function(FieldLayoutElement $layoutElement) {
            if (!$layoutElement instanceof CustomField) {
                return false;
            }

            try {
                $layoutElement->getField();
            } catch (FieldNotFoundException) {
                return false;
            }

            return true;
        }, $element));
    }

    /**
     * Prepends elements to the first tab.
     *
     * @param FieldLayoutElement[] $elements
     * @since 5.5.0
     */
    public function prependElements(array $elements): void
    {
        // Make sure there's at least one tab
        $tab = reset($this->_tabs);
        if (!$tab) {
            $this->_tabs[] = $tab = new FieldLayoutTab([
                'layout' => $this,
                'layoutId' => $this->id,
                'name' => Craft::t('app', 'Content'),
                'sortOrder' => 1,
                'elements' => [],
            ]);
        }

        $layoutElements = $tab->getElements();
        array_unshift($layoutElements, ...$elements);
        $tab->setElements($layoutElements);
    }

    /**
     * Returns the custom fields included in the layout.
     *
     * @return FieldInterface[]
     * @since 4.0.0
     */
    public function getCustomFields(): array
    {
        return $this->_customFields ??= $this->_customFields();
    }

    /**
     * Returns the custom fields included in the layout, taking visibility conditions into account.
     *
     * @param ElementInterface $element
     * @return FieldInterface[]
     * @since 4.0.0
     */
    public function getVisibleCustomFields(ElementInterface $element): array
    {
        return $this->_customFields(element: $element);
    }

    /**
     * Returns the custom fields included in the layout, taking editability conditions into account.
     *
     * @param ElementInterface $element
     * @return FieldInterface[]
     * @since 5.7.0
     */
    public function getEditableCustomFields(ElementInterface $element): array
    {
        return $this->_customFields(
            fn(CustomField $layoutElement) => $layoutElement->editable(),
            $element,
        );
    }

    /**
     * Returns the field layout’s designated thumbnail field.
     *
     * @return BaseField|null
     * @since 5.0.0
     */
    public function getThumbField(): ?BaseField
    {
        /** @var BaseField|null */
        return $this->_element(fn(FieldLayoutElement $layoutElement) => (
            $layoutElement instanceof BaseField &&
            $layoutElement->thumbable() &&
            $layoutElement->providesThumbs
        ));
    }

    /**
     * Returns the custom fields that should be used in element card bodies.
     *
     * @param ElementInterface|null $element
     * @return BaseField[]
     * @since 5.0.0
     */
    public function getCardBodyFields(?ElementInterface $element): array
    {
        /** @var BaseField[] */
        return iterator_to_array($this->_elements(fn(FieldLayoutElement $layoutElement) => (
            $layoutElement instanceof BaseField &&
            $layoutElement->previewable() &&
            $layoutElement->includeInCards
        ), $element));
    }

    /**
     * Returns the attributes that should be used in element card bodies.
     *
     * @return array
     * @since 5.5.0
     */
    public function getCardBodyAttributes(): array
    {
        $cardViewValues = $this->getCardView();

        // filter only the selected attributes
        $attributes = array_filter(
            $this->type::cardAttributes(),
            fn($cardAttribute, $key) => in_array($key, $cardViewValues),
            ARRAY_FILTER_USE_BOTH
        );

        // ensure we have value set too (not just the label)
        array_walk($attributes, function(&$attribute, $key) {
            $attribute['value'] = $key;
        });

        return $attributes;
    }

    /**
     * Returns the fields and attributes that should be used in element card bodies in the correct order.
     *
     * @param ElementInterface|null $element
     * @return array
     * @since 5.5.0
     */
    public function getCardBodyElements(?ElementInterface $element = null, array $cardElements = []): array
    {
        // get attributes that should show in a card
        $attributes = $this->getCardBodyAttributes();

        $layoutElements = [];

        if (empty($cardElements)) {
            // index field layout elements by prefix + uid
            foreach ($this->getCardBodyFields($element) as $layoutElement) {
                $layoutElements["layoutElement:$layoutElement->uid"] = $layoutElement;
            }

            foreach ($this->getGeneratedFields() as $field) {
                if (($field['name'] ?? '') !== '') {
                    $layoutElements["generatedField:{$field['uid']}"] = [
                        'html' => $element ? ($element->getGeneratedFieldValues()[$field['uid']] ?? '') : Html::encode($field['name']),
                    ];
                }
            }
        } else {
            // we only need to worry about body fields as the attributes are taken care of via getCardBodyAttributes()
            foreach ($cardElements as $cardElement) {
                if (str_starts_with($cardElement['value'], 'layoutElement:')) {
                    $uid = str_replace('layoutElement:', '', $cardElement['value']);
                    $layoutElement = $this->getElementByUid($uid);
                    if ($layoutElement === null) {
                        $fieldId = $cardElement['fieldId'];
                        if ($fieldId) {
                            $field = Craft::$app->getFields()->getFieldById($fieldId);
                            $layoutElement = new CustomField();
                            $layoutElement->setField($field);
                        } else {
                            // this will kick in for native field that have just been dragged into the field layout designer
                            $fieldLabel = $cardElement['fieldLabel'];
                            if ($fieldLabel) {
                                $layoutElement['value'] = $layoutElement;
                                $layoutElement['label'] = $fieldLabel;
                            }
                        }
                    }

                    $layoutElements[$cardElement['value']] = $layoutElement;
                } elseif (str_starts_with($cardElement['value'], 'generatedField:')) {
                    $uid = str_replace('generatedField:', '', $cardElement['value']);
                    $field = $this->getGeneratedFieldByUid($uid);
                    if ($field) {
                        $layoutElements[$cardElement['value']] = [
                            'html' => $element ? ($element->getGeneratedFieldValues()[$uid] ?? '') : Html::encode($field['name']),
                        ];
                    } elseif (isset($cardElement['fieldLabel'])) {
                        $layoutElements[$cardElement['value']] = [
                            'html' => Html::encode($cardElement['fieldLabel']),
                        ];
                    }
                }
            }
        }

        // get the card view config - array of all the attributes, fields and generated fields that should be shown in the card
        $cardViewValues = $this->getCardView();

        // filter out any generated fields that shouldn't show in the card
        $layoutElements = array_filter(
            $layoutElements,
            fn($key) => !str_starts_with($key, 'generatedField:') || in_array($key, $cardViewValues),
            ARRAY_FILTER_USE_KEY
        );

        $elements = array_merge($layoutElements, $attributes);

        // make sure we don't have any cardViewValues that are no longer allowed to show in cards
        $cardViewValues = array_filter($cardViewValues, fn($value) => isset($elements[$value]));

        // return elements in the order specified in the config
        return array_replace(
            array_flip($cardViewValues),
            $elements
        );
    }

    /**
     * @param callable|null $filter
     * @param ElementInterface|null $element
     * @return FieldInterface[]
     */
    private function _customFields(?callable $filter = null, ?ElementInterface $element = null): array
    {
        return array_map(
            fn(CustomField $layoutElement) => $layoutElement->getField(),
            iterator_to_array($this->_elements(function(FieldLayoutElement $layoutElement) use ($filter) {
                if (
                    !$layoutElement instanceof CustomField ||
                    ($filter && !$filter($layoutElement))
                ) {
                    return false;
                }

                // make sure the field exists
                try {
                    $layoutElement->getField();
                } catch (FieldNotFoundException) {
                    return false;
                }

                return true;
            }, $element)),
        );
    }

    /**
     * Returns a custom field by its ID.
     *
     * @param int $id The field ID.
     * @return FieldInterface|null
     * @since 5.0.0
     */
    public function getFieldById(int $id): ?FieldInterface
    {
        foreach ($this->getCustomFields() as $field) {
            if ($field->id === $id) {
                return $field;
            }
        }

        return null;
    }

    /**
     * Returns a custom field by its UUID.
     *
     * @param string $uid The field UUID.
     * @return FieldInterface|null
     * @since 5.0.0
     */
    public function getFieldByUid(string $uid): ?FieldInterface
    {
        foreach ($this->getCustomFields() as $field) {
            if ($field->uid === $uid) {
                return $field;
            }
        }

        return null;
    }

    /**
     * Returns a custom field by its handle.
     *
     * @param string $handle The field handle.
     * @return FieldInterface|null
     */
    public function getFieldByHandle(string $handle): ?FieldInterface
    {
        $this->_indexedCustomFields ??= Arr::keyBy($this->getCustomFields(), fn(FieldInterface $field) => $field->handle);
        return $this->_indexedCustomFields[$handle] ?? null;
    }

    /**
     * Creates a new [[FieldLayoutForm]] object for the given element.
     *
     * The `$config` array can contain the following keys:
     *
     * - `tabIdPrefix` – prefix that should be applied to the tab content containers’ `id` attributes
     * - `namespace` – Namespace that should be applied to the tab contents
     * - `registerDeltas` – Whether delta name registration should be enabled/disabled for the form (by default its state will be left alone)
     * - `visibleElements` – Lists of already-visible layout elements from [[FieldLayoutForm::getVisibleElements()]]
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @param array $config The [[FieldLayoutForm]] config
     * @return FieldLayoutForm
     * @since 3.5.0
     */
    public function createForm(?ElementInterface $element = null, bool $static = false, array $config = []): FieldLayoutForm
    {
        $view = Craft::$app->getView();

        // Calling this with an existing namespace isn’t fully supported,
        // since the tab anchors’ `href` attributes won’t end up getting set properly
        $namespace = ArrayHelper::remove($config, 'namespace');

        // Register delta names?
        $registerDeltas = ArrayHelper::remove($config, 'registerDeltas');
        $changeDeltaRegistration = $registerDeltas !== null;
        if ($changeDeltaRegistration) {
            $view = Craft::$app->getView();
            $isDeltaRegistrationActive = $view->getIsDeltaRegistrationActive();
            $view->setIsDeltaRegistrationActive($registerDeltas);
        }

        // Any already-included layout elements?
        $visibleElements = ArrayHelper::remove($config, 'visibleElements');

        $form = new FieldLayoutForm($config);
        $tabs = $this->getTabs();

        // Fire a 'createForm' event
        if ($this->hasEventHandlers(self::EVENT_CREATE_FORM)) {
            $event = new CreateFieldLayoutFormEvent([
                'form' => $form,
                'element' => $element,
                'static' => $static,
                'tabs' => $tabs,
            ]);
            $this->trigger(self::EVENT_CREATE_FORM, $event);
            $tabs = $event->tabs;
            $static = $event->static;
        }

        foreach ($tabs as $tab) {
            $layoutElements = [];
            $showTab = !isset($tab->uid) || $tab->showInForm($element);
            $hasVisibleFields = false;

            foreach ($tab->getElements() as $layoutElement) {
                // Only tabs + elements that were saved with UUIDs can be conditional
                $isConditional = isset($tab->uid, $layoutElement->uid);

                if ($showTab && (!$isConditional || $layoutElement->showInForm($element))) {
                    // If it was already included and we just need the missing elements, only keep track that it’s still included
                    if (
                        !$layoutElement->alwaysRefresh() &&
                        $visibleElements !== null &&
                        (!$isConditional || (isset($visibleElements[$tab->uid]) && in_array($layoutElement->uid, $visibleElements[$tab->uid])))
                    ) {
                        $layoutElements[] = [$layoutElement, $isConditional, true];
                        $hasVisibleFields = true;
                    } else {
                        $html = $view->namespaceInputs(fn() => $layoutElement->formHtml($element, $static) ?? '', $namespace);

                        if ($html) {
                            $errorKey = null;
                            // if error key prefix was set on the FieldLayoutForm - use it
                            if ($form->errorKeyPrefix) {
                                $tagAttributes = Html::parseTagAttributes($html);
                                // if we already have an error-key for this field, prefix it
                                if (isset($tagAttributes['data']['error-key'])) {
                                    $errorKey = $form->errorKeyPrefix . '.' . $tagAttributes['data']['error-key'];
                                } elseif ($layoutElement instanceof BaseField) {
                                    // otherwise let's construct it
                                    $errorKey = $form->errorKeyPrefix . '.' . ($layoutElement->name ?? $layoutElement->attribute());
                                }
                            }

                            $html = Html::modifyTagAttributes($html, [
                                'data' => [
                                    'layout-element' => $isConditional ? $layoutElement->uid : true,
                                ] + ($errorKey ? ['error-key' => $errorKey] : []),
                            ]);

                            $layoutElements[] = [$layoutElement, $isConditional, $html];
                            $hasVisibleFields = true;
                        } else {
                            $layoutElements[] = [$layoutElement, $isConditional, false];
                        }
                    }
                } else {
                    $layoutElements[] = [$layoutElement, $isConditional, false];
                }
            }

            if ($hasVisibleFields) {
                $form->tabs[] = new FieldLayoutFormTab([
                    'layoutTab' => $tab,
                    'hasErrors' => $element && $tab->elementHasErrors($element),
                    'elements' => $layoutElements,
                ]);
            }
        }

        if ($changeDeltaRegistration) {
            $view->setIsDeltaRegistrationActive($isDeltaRegistrationActive);
        }

        return $form;
    }

    /**
     * @param callable $filter
     * @param ElementInterface|null $element
     * @return FieldLayoutElement|null
     */
    private function _element(callable $filter, ?ElementInterface $element = null): ?FieldLayoutElement
    {
        return $this->_elements($filter, $element)->current();
    }

    /**
     * @param callable|null $filter
     * @param ElementInterface|null $element
     * @return Generator
     */
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

    /**
     * Resets the memoized custom fields.
     *
     * @internal
     */
    public function reset(): void
    {
        $this->_customFields = $this->_indexedCustomFields = null;
    }
}
