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
        $tabs = [];

        if (is_array($tabConfigs)) {
            foreach ($tabConfigs as $tabConfig) {
                $tab = FieldLayoutTab::createFromConfig(['layout' => $layout] + $tabConfig);

                // Ignore empty tabs
                if (!empty($tab->getElements())) {
                    $tabs[] = $tab;
                }
            }
        }

        $layout->setTabs($tabs);
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
     */
    private array $_tabs;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->uid)) {
            $this->uid = StringHelper::UUID();
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

        // Make sure no fields are using one of our reserved attribute names
        foreach ($this->getTabs() as $tab) {
            foreach ($tab->getElements() as $layoutElement) {
                if (
                    $layoutElement instanceof CustomField &&
                    in_array($layoutElement->attribute(), $this->reservedFieldHandles, true)
                ) {
                    $this->addError('fields', Craft::t('app', '“{handle}” is a reserved word.', [
                        'handle' => $layoutElement->attribute(),
                    ]));
                }
            }
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
            if ($this->id) {
                $this->setTabs(Craft::$app->getFields()->getLayoutTabsById($this->id));
            } else {
                $this->setTabs([]);
            }
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
            array_unshift($layoutElements, ...array_values($missingFields));
            $tab->setElements($layoutElements);
        }
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
            $fields = [];

            foreach (Craft::$app->getFields()->getAllGroups() as $group) {
                $groupName = Craft::t('site', $group->name);
                foreach ($group->getFields() as $field) {
                    $fields[$groupName][] = Craft::createObject([
                        'class' => CustomField::class,
                        'layout' => $this,
                    ], [$field]);
                }
            }

            // Allow changes
            $event = new DefineFieldLayoutCustomFieldsEvent(['fields' => $fields]);
            $this->trigger(self::EVENT_DEFINE_CUSTOM_FIELDS, $event);

            $this->_availableCustomFields = $event->fields;
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
        $event = new DefineFieldLayoutElementsEvent([
            'elements' => [
                new Heading(),
                new Tip([
                    'style' => Tip::STYLE_TIP,
                ]),
                new Tip([
                    'style' => Tip::STYLE_WARNING,
                ]),
                new Template(),
            ],
        ]);

        $this->trigger(self::EVENT_DEFINE_UI_ELEMENTS, $event);

        // HR and Line Break should always be last
        $event->elements[] = new HorizontalRule();
        $event->elements[] = new LineBreak();

        // Instantiate them
        foreach ($event->elements as &$element) {
            if (is_string($element) || is_array($element)) {
                $element = Craft::createObject($element);
            }
            if (!$element instanceof FieldLayoutElement) {
                throw new InvalidConfigException('Invalid UI element config');
            }
        }

        return $event->elements;
    }

    /**
     * Returns whether a field is included in the layout by its attribute.
     *
     * @param string $attribute
     * @return bool
     * @since 3.5.0
     */
    public function isFieldIncluded(string $attribute): bool
    {
        try {
            $this->getField($attribute);
            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Returns a field that’s included in the layout by its attribute.
     *
     * @param string $attribute
     * @return BaseField
     * @throws InvalidArgumentException if the field isn’t included
     * @since 3.5.0
     */
    public function getField(string $attribute): BaseField
    {
        $filter = fn(FieldLayoutElement $layoutElement) => (
            $layoutElement instanceof BaseField &&
            $layoutElement->attribute() === $attribute
        );
        /** @var BaseField|null $field */
        $field = $this->_element($filter);
        if (!$field) {
            throw new InvalidArgumentException("Invalid field: $attribute");
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
        $tabConfigs = [];

        foreach ($this->getTabs() as $tab) {
            $tabConfig = $tab->getConfig();
            if (!empty($tabConfig['elements'])) {
                $tabConfigs[] = $tabConfig;
            }
        }

        if (empty($tabConfigs)) {
            return null;
        }

        return [
            'tabs' => $tabConfigs,
        ];
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
     * Returns the custom fields included in the layout.
     *
     * @return FieldInterface[]
     * @since 4.0.0
     */
    public function getCustomFields(): array
    {
        return $this->_customFields();
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
     * @param ElementInterface|null $element
     * @return FieldInterface[]
     */
    private function _customFields(?ElementInterface $element = null): array
    {
        $fields = [];
        $filter = fn(FieldLayoutElement $layoutElement) => $layoutElement instanceof CustomField;
        foreach ($this->_elements($filter, $element) as $layoutElement) {
            /** @var CustomField $layoutElement */
            $field = $layoutElement->getField();
            $field->required = $layoutElement->required;
            $fields[] = $field;
        }
        return $fields;
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

        // Fine a 'createForm' event
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
                            $html = Html::modifyTagAttributes($html, [
                                'data' => [
                                    'layout-element' => $isConditional ? $layoutElement->uid : true,
                                ],
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
     * @param callable $filter
     * @param ElementInterface|null $element
     * @return Generator
     */
    private function _elements(callable $filter, ?ElementInterface $element = null): Generator
    {
        foreach ($this->getTabs() as $tab) {
            if (!$element || !isset($tab->uid) || $tab->showInForm($element)) {
                foreach ($tab->getElements() as $layoutElement) {
                    if ($filter($layoutElement) && (!$element || !isset($layoutElement->uid) || $layoutElement->showInForm($element))) {
                        yield $layoutElement;
                    }
                }
            }
        }
    }
}
