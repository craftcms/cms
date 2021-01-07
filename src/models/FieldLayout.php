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
use craft\base\FieldLayoutElementInterface;
use craft\base\Model;
use craft\events\CreateFieldLayoutFormEvent;
use craft\events\DefineFieldLayoutElementsEvent;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\fieldlayoutelements\BaseField;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\Heading;
use craft\fieldlayoutelements\HorizontalRule;
use craft\fieldlayoutelements\Template;
use craft\fieldlayoutelements\Tip;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
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
     * @event DefineFieldLayoutFieldsEvent The event that is triggered when defining the standard (not custom) fields for the layout.
     *
     * ```php
     * use craft\models\FieldLayout;
     * use craft\events\DefineFieldLayoutFieldsEvent;
     * use yii\base\Event;
     *
     * Event::on(
     *     FieldLayout::class,
     *     FieldLayout::EVENT_DEFINE_STANDARD_FIELDS,
     *     function(DefineFieldLayoutFieldsEvent $event) {
     *         // @var FieldLayout $layout
     *         $layout = $event->sender;
     *
     *         if ($layout->type === MyElementType::class) {
     *             $event->fields[] = MyStandardField::class;
     *         }
     *     }
     * );
     * ```
     *
     * @see getAvailableStandardFields()
     * @since 3.5.0
     */
    const EVENT_DEFINE_STANDARD_FIELDS = 'defineStandardFields';

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
     * @see getAvailableStandardFields()
     * @since 3.5.0
     */
    const EVENT_DEFINE_UI_ELEMENTS = 'defineUiElements';

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
    const EVENT_CREATE_FORM = 'createForm';

    /**
     * Creates a new field layout from the given config.
     *
     * @param array $config
     * @return self
     * @since 3.1.0
     */
    public static function createFromConfig(array $config): self
    {
        $layout = new self();
        $tabs = [];

        if (!empty($config['tabs']) && is_array($config['tabs'])) {
            foreach ($config['tabs'] as $tabConfig) {
                $tab = FieldLayoutTab::createFromConfig($tabConfig);

                // Ignore empty tabs
                if (!empty($tab->elements)) {
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
    public $id;

    /**
     * @var string|null The element type
     */
    public $type;

    /**
     * @var string|null UID
     */
    public $uid;

    /**
     * @var BaseField[][]
     * @see getAvailableCustomFields()
     */
    private $_availableCustomFields;

    /**
     * @var BaseField[][]
     * @see getAvailableStandardFields()
     */
    private $_availableStandardFields;

    /**
     * @var FieldLayoutTab[]
     */
    private $_tabs;

    /**
     * @var BaseField[]
     * @see getTabs()
     * @see isFieldIncluded()
     */
    private $_fields;

    /**
     * @var FieldInterface[]
     * @see getFields()
     * @see setFields()
     */
    private $_customFields;

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id'], 'number', 'integerOnly' => true];
        return $rules;
    }

    /**
     * Returns the layout’s tabs.
     *
     * @return FieldLayoutTab[] The layout’s tabs.
     */
    public function getTabs(): array
    {
        if ($this->_tabs !== null) {
            return $this->_tabs;
        }

        $this->_fields = [];

        if ($this->id) {
            $this->_tabs = Craft::$app->getFields()->getLayoutTabsById($this->id);

            // Take stock of all the selected layout elements
            foreach ($this->_tabs as $tab) {
                foreach ($tab->elements as $element) {
                    if ($element instanceof BaseField) {
                        $this->_fields[$element->attribute()] = $element;
                    }
                }
            }
        } else {
            $this->_tabs = [];
        }

        // Make sure that we aren't missing any mandatory fields
        /** @var BaseField[] $missingFields */
        $missingFields = [];
        foreach ($this->getAvailableStandardFields() as $field) {
            if ($field->mandatory() && !isset($this->_fields[$field->attribute()])) {
                $missingFields[$field->attribute()] = $field;
                $this->_fields[$field->attribute()] = $field;
            }
        }

        if (!empty($missingFields)) {
            // Make sure there's at least one tab
            $tab = reset($this->_tabs);
            if (!$tab) {
                $this->_tabs[] = $tab = new FieldLayoutTab([
                    'layoutId' => $this->id,
                    'name' => Craft::t('app', 'Content'),
                    'sortOrder' => 1,
                    'elements' => [],
                ]);
            }
            array_unshift($tab->elements, ...array_values($missingFields));
        }

        return $this->_tabs;
    }

    /**
     * Sets the layout’s tabs.
     *
     * @param array|FieldLayoutTab[] $tabs An array of the layout’s tabs, which can either be FieldLayoutTab
     * objects or arrays defining the tab’s attributes.
     */
    public function setTabs($tabs)
    {
        $this->_tabs = [];
        foreach ($tabs as $tab) {
            if (is_array($tab)) {
                $tab = new FieldLayoutTab($tab);
            }
            $tab->setLayout($this);
            $this->_tabs[] = $tab;
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
        if ($this->_availableCustomFields === null) {
            $this->_availableCustomFields = [];

            foreach (Craft::$app->getFields()->getAllGroups() as $group) {
                $groupName = Craft::t('site', $group->name);
                foreach ($group->getFields() as $field) {
                    $this->_availableCustomFields[$groupName][] = Craft::createObject(CustomField::class, [$field]);
                }
            }
        }

        return $this->_availableCustomFields;
    }

    /**
     * Returns the available standard fields.
     *
     * @return BaseField[]
     * @since 3.5.0
     */
    public function getAvailableStandardFields(): array
    {
        if ($this->_availableStandardFields === null) {
            $event = new DefineFieldLayoutFieldsEvent();
            $this->trigger(self::EVENT_DEFINE_STANDARD_FIELDS, $event);
            $this->_availableStandardFields = $event->fields;

            // Instantiate them
            foreach ($this->_availableStandardFields as &$field) {
                if (is_string($field) || is_array($field)) {
                    $field = Craft::createObject($field);
                }
                if (!$field instanceof BaseField) {
                    throw new InvalidConfigException('Invalid standard field config');
                }
            }
        }
        return $this->_availableStandardFields;
    }

    /**
     * Returns the layout elements that are available to the field layout, grouped by the type name and (optionally) group name.
     *
     * @return FieldLayoutElementInterface[]
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

        // HR should always be last
        $event->elements[] = new HorizontalRule();

        // Instantiate them
        foreach ($event->elements as &$element) {
            if (is_string($element) || is_array($element)) {
                $element = Craft::createObject($element);
            }
            if (!$element instanceof FieldLayoutElementInterface) {
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
        $this->getTabs();
        return isset($this->_fields[$attribute]);
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
        $this->getTabs();
        if (!isset($this->_fields[$attribute])) {
            throw new InvalidArgumentException("Invalid field: $attribute");
        }
        return $this->_fields[$attribute];
    }

    /**
     * Returns the field layout config for this field layout.
     *
     * @return array|null
     * @since 3.1.0
     */
    public function getConfig()
    {
        $tabConfigs = [];

        foreach ($this->getTabs() as $tab) {
            $tabConfig = $tab->getConfig();
            if ($tabConfig) {
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
     * Returns the custom fields included in the layout.
     *
     * @return FieldInterface[]
     */
    public function getFields(): array
    {
        if ($this->_customFields !== null) {
            return $this->_customFields;
        }

        if (!$this->id) {
            return [];
        }

        return $this->_customFields = Craft::$app->getFields()->getFieldsByLayoutId($this->id);
    }

    /**
     * Returns the IDs of the custom fields included in the layout.
     *
     * @return int[]
     * @deprecated in 3.5.0.
     */
    public function getFieldIds(): array
    {
        $ids = [];

        foreach ($this->getFields() as $field) {
            $ids[] = $field->id;
        }

        return $ids;
    }

    /**
     * Returns a custom field by its handle.
     *
     * @param string $handle The field handle.
     * @return FieldInterface|null
     */
    public function getFieldByHandle(string $handle)
    {
        foreach ($this->getFields() as $field) {
            if ($field->handle === $handle) {
                return $field;
            }
        }

        return null;
    }

    /**
     * Sets the custom fields included in this layout.
     *
     * @param FieldInterface[]|null $fields
     */
    public function setFields(array $fields = null)
    {
        $this->_customFields = $fields;
    }

    /**
     * Creates a new [[FieldLayoutForm]] object for the given element.
     *
     * The `$config` array can contain the following keys:
     *
     * - `tabIdPrefix` – prefix that should be applied to the tab content containers’ `id` attributes
     * - `namespace` – Namespace that should be applied to the tab contents
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @param array $config The [[FieldLayoutForm]] config
     * @return FieldLayoutForm
     * @since 3.5.0
     */
    public function createForm(ElementInterface $element = null, bool $static = false, array $config = []): FieldLayoutForm
    {
        $view = Craft::$app->getView();
        // Calling this with an existing namespace isn’t fully supported,
        // since the tab anchors’ `href` attributes won’t end up getting set properly
        $oldNamespace = $view->getNamespace();
        $namespace = ArrayHelper::remove($config, 'namespace');
        if ($namespace !== null) {
            $view->setNamespace($view->namespaceInputName($namespace));
        }

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
            $tabHtml = [];

            foreach ($tab->elements as $formElement) {
                $elementHtml = $formElement->formHtml($element, $static);
                if ($elementHtml !== null) {
                    if ($namespace !== null) {
                        $elementHtml = Html::namespaceHtml($elementHtml, $namespace);
                    }
                    $tabHtml[] = $elementHtml;
                }
            }

            if (!empty($tabHtml)) {
                $form->tabs[] = new FieldLayoutFormTab([
                    'name' => Craft::t('site', $tab->name),
                    'id' => $tab->getHtmlId(),
                    'hasErrors' => $element && $tab->elementHasErrors($element),
                    'content' => implode("\n", $tabHtml),
                ]);
            }
        }

        $view->setNamespace($oldNamespace);

        return $form;
    }
}
