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
use craft\fieldlayoutelements\BaseField;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

/**
 * FieldLayoutTab model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class FieldLayoutTab extends Model
{
    /**
     * Creates a new field layout tab from the given config.
     *
     * @param array $config
     * @return self
     * @since 3.5.0
     */
    public static function createFromConfig(array $config): self
    {
        static::updateConfig($config);
        return new self($config);
    }

    /**
     * Updates a field layout tab’s config to the new format.
     *
     * @param array $config
     * @since 3.5.0
     */
    public static function updateConfig(array &$config)
    {
        if (!array_key_exists('fields', $config)) {
            return;
        }

        $config['elements'] = [];

        ArrayHelper::multisort($config['fields'], 'sortOrder');
        foreach ($config['fields'] as $fieldUid => $fieldConfig) {
            $config['elements'][] = [
                'type' => CustomField::class,
                'fieldUid' => $fieldUid,
                'required' => (bool)$fieldConfig['required'],
            ];
        }

        unset($config['fields']);
    }


    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int|null Layout ID
     */
    public $layoutId;

    /**
     * @var string|null Name
     */
    public $name;

    /**
     * @var FieldLayoutElementInterface[]|null The tab’s layout elements
     * @since 3.5.0
     */
    public $elements;

    /**
     * @var int|null Sort order
     */
    public $sortOrder;

    /**
     * @var string|null UID
     */
    public $uid;

    /**
     * @var FieldLayout|null
     */
    private $_layout;

    /**
     * @var FieldInterface[]|null
     */
    private $_fields;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->elements === null) {
            $this->elements = [];
            foreach ($this->getFields() as $field) {
                $this->elements[] = Craft::createObject([
                    'class' => CustomField::class,
                    'required' => $field->required,
                ], [
                    $field,
                ]);
            }
        } else {
            if (is_string($this->elements)) {
                $this->elements = Json::decode($this->elements);
            }
            $fieldsService = Craft::$app->getFields();
            foreach ($this->elements as $i => $element) {
                if (is_array($element)) {
                    try {
                        $this->elements[$i] = $fieldsService->createLayoutElement($element);
                    } catch (InvalidArgumentException $e) {
                        Craft::warning('Invalid field layout element config: ' . $e->getMessage(), __METHOD__);
                        Craft::$app->getErrorHandler()->logException($e);
                        unset($this->elements[$i]);
                    }
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'layoutId'], 'number', 'integerOnly' => true];
        $rules[] = [['name'], 'string', 'max' => 255];
        $rules[] = [['sortOrder'], 'string', 'max' => 4];
        return $rules;
    }

    /**
     * Returns the field layout config for this field layout tab.
     *
     * @return array|null
     * @since 3.5.0
     */
    public function getConfig()
    {
        if (empty($this->elements)) {
            return null;
        }

        return [
            'name' => $this->name,
            'sortOrder' => (int)$this->sortOrder,
            'elements' => $this->getElementConfigs(),
        ];
    }

    /**
     * Returns the field layout configs for this field layout’s tabs.
     *
     * @return array[]
     * @since 3.5.0
     */
    public function getElementConfigs(): array
    {
        $elementConfigs = [];
        foreach ($this->elements as $element) {
            $elementConfigs[] = ['type' => get_class($element)] + $element->toArray();
        }
        return $elementConfigs;
    }

    /**
     * Returns the tab’s layout.
     *
     * @return FieldLayout|null The tab’s layout.
     * @throws InvalidConfigException if [[groupId]] is set but invalid
     */
    public function getLayout()
    {
        if ($this->_layout !== null) {
            return $this->_layout;
        }

        if (!$this->layoutId) {
            return null;
        }

        if (($this->_layout = Craft::$app->getFields()->getLayoutById($this->layoutId)) === null) {
            throw new InvalidConfigException('Invalid layout ID: ' . $this->layoutId);
        }

        return $this->_layout;
    }

    /**
     * Sets the tab’s layout.
     *
     * @param FieldLayout $layout The tab’s layout.
     */
    public function setLayout(FieldLayout $layout)
    {
        $this->_layout = $layout;
    }

    /**
     * Returns the custom fields included in this tab.
     *
     * @return FieldInterface[]
     */
    public function getFields(): array
    {
        if ($this->_fields !== null) {
            return $this->_fields;
        }

        $this->_fields = [];

        if ($layout = $this->getLayout()) {
            foreach ($layout->getFields() as $field) {
                if ($field->tabId == $this->id) {
                    $this->_fields[] = $field;
                }
            }
        }

        return $this->_fields;
    }

    /**
     * Sets the custom fields included in this tab.
     *
     * @param FieldInterface[] $fields
     */
    public function setFields(array $fields)
    {
        ArrayHelper::multisort($fields, 'sortOrder');
        $this->_fields = $fields;

        $this->elements = [];
        foreach ($this->_fields as $field) {
            $this->elements[] = Craft::createObject([
                'class' => CustomField::class,
                'required' => $field->required,
            ], [
                $field,
            ]);
        }

        // Clear the field layout's field cache
        if ($this->_layout !== null) {
            $this->_layout->setFields(null);
        }
    }

    /**
     * Returns the tab’s HTML ID.
     *
     * @return string
     */
    public function getHtmlId(): string
    {
        return 'tab-' . StringHelper::toKebabCase($this->name);
    }

    /**
     * Returns whether the given element has any validation errors for the custom fields included in this tab.
     *
     * @param ElementInterface $element
     * @return bool
     * @since 3.4.0
     */
    public function elementHasErrors(ElementInterface $element): bool
    {
        if (!$element->hasErrors()) {
            return false;
        }

        foreach ($this->elements as $layoutElement) {
            if ($layoutElement instanceof BaseField && $element->hasErrors($layoutElement->attribute() . '.*')) {
                return true;
            }
        }

        return false;
    }
}
