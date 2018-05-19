<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\base\Model;

/**
 * FieldLayout model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldLayout extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var string|null Type
     */
    public $type;


    /**
     * @var
     */
    private $_tabs;

    /**
     * @var
     */
    private $_fields;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'number', 'integerOnly' => true],
        ];
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

        if (!$this->id) {
            return [];
        }

        return $this->_tabs = Craft::$app->getFields()->getLayoutTabsById($this->id);
    }

    /**
     * Returns the layout’s fields.
     *
     * @return FieldInterface[] The layout’s fields.
     */
    public function getFields(): array
    {
        if ($this->_fields !== null) {
            return $this->_fields;
        }

        if (!$this->id) {
            return [];
        }

        return $this->_fields = Craft::$app->getFields()->getFieldsByLayoutId($this->id);
    }

    /**
     * Returns the layout’s fields’ IDs.
     *
     * @return array The layout’s fields’ IDs.
     */
    public function getFieldIds(): array
    {
        $ids = [];

        foreach ($this->getFields() as $field) {
            /** @var Field $field */
            $ids[] = $field->id;
        }

        return $ids;
    }

    /**
     * Returns a field by its handle.
     *
     * @param string $handle The field handle.
     * @return Field|FieldInterface|null
     */
    public function getFieldByHandle(string $handle)
    {
        foreach ($this->getFields() as $field) {
            /** @var Field $field */
            if ($field->handle === $handle) {
                return $field;
            }
        }

        return null;
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
     * Sets the layout']”s fields.
     *
     * @param FieldInterface[] $fields An array of the layout’s fields, which can either be
     * FieldLayoutFieldModel objects or arrays defining the tab’s
     * attributes.
     */
    public function setFields(array $fields)
    {
        $this->_fields = $fields;
    }
}
