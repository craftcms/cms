<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\base\FieldLayoutElement;
use craft\base\FieldLayoutProviderInterface;
use craft\base\Model;
use craft\events\CreateFieldLayoutFormEvent;
use craft\events\DefineFieldLayoutCustomFieldsEvent;
use craft\events\DefineFieldLayoutElementsEvent;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\fieldlayoutelements\BaseField;
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
use Generator;
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
     * @var string|null The element type
     * @phpstan-var class-string<ElementInterface>|null
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
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id'], 'number', 'integerOnly' => true];
        $rules[] = [['customFields'], 'validateFields'];
        return $rules;
    }

    /**
     * Validates the field selections.
     *
     * @since 3.7.0
     */
    public function validateFields(): void
    {
        if (!$this->reservedFieldHandles) {
            return;
        }

        // Make sure no field handles are duplicated or using one of our reserved attribute names
        $handles = [];
        foreach ($this->getCustomFields() as $field) {
            if (in_array($field->handle, $this->reservedFieldHandles, true)) {
                $this->addError('fields', Craft::t('app', '“{handle}” is a reserved word.', [
                    'handle' => $field->handle,
                ]));
                return;
            }
            if (isset($handles[$field->handle])) {
                $this->addError('fields', Craft::t('yii', '{attribute} "{value}" has already been taken.', [
                    'attribute' => Craft::t('app', 'Handle'),
                    'value' => $field->handle,
                ]));
                return;
            }
            $handles[$field->handle] = true;
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
            /** @var BaseField $field */
            $includedFields[$field->attribute()] = true;
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
            $this->prependElements(array_values($missingFields));
        }

        // Clear caches
        $this->reset();
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
     * Returns the field layout’s config.
     *
     * @return array|null
     * @since 3.1.0
     */
    public function getConfig(): ?array
    {
        $tabConfigs = array_values(array_map(
            fn(FieldLayoutTab $tab) => $tab->getConfig(),
            $this->getTabs(),
        ));

        if (empty($tabConfigs)) {
            return null;
        }

        return [
            'tabs' => $tabConfigs,
        ];
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
     * @param string $class
     * @phpstan-param class-string<T> $class
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
     * @param string $class
     * @phpstan-param class-string<T> $class
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
     * @param string $class
     * @phpstan-param class-string<T> $class
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
     * @param string $class
     * @phpstan-param class-string<T> $class
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
        return $this->getElementsByType(CustomField::class);
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
        $filter = fn(FieldLayoutElement $layoutElement) => $layoutElement instanceof CustomField;
        return iterator_to_array($this->_elements($filter, $element));
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
     * Returns the visible custom fields included in the layout, taking conditions into account.
     *
     * @param ElementInterface $element
     * @return FieldInterface[]
     * @since 4.0.0
     */
    public function getVisibleCustomFields(ElementInterface $element): array
    {
        return $this->_customFields($element);
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
     * @param ElementInterface|null $element
     * @return FieldInterface[]
     */
    private function _customFields(?ElementInterface $element = null): array
    {
        return array_map(
            fn(CustomField $layoutElement) => $layoutElement->getField(),
            iterator_to_array($this->_elements(
                fn(FieldLayoutElement $layoutElement) => $layoutElement instanceof CustomField,
                $element,
            )),
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
        foreach ($this->getCustomFields() as $field) {
            if ($field->handle === $handle) {
                return $field;
            }
        }

        return null;
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
                        $visibleElements !== null &&
                        (!$isConditional || (isset($visibleElements[$tab->uid]) && in_array($layoutElement->uid, $visibleElements[$tab->uid])))
                    ) {
                        $layoutElements[] = [$layoutElement, $isConditional, true];
                        $hasVisibleFields = true;
                    } else {
                        $html = $view->namespaceInputs(function() use ($layoutElement, $element, $static) {
                            return $layoutElement->formHtml($element, $static) ?? '';
                        }, $namespace);

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
        $this->_customFields = null;
    }
}
