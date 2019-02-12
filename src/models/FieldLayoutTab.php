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
use craft\helpers\StringHelper;
use yii\base\InvalidConfigException;

/**
 * FieldLayoutTab model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldLayoutTab extends Model
{
    // Properties
    // =========================================================================

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

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['id', 'layoutId'], 'number', 'integerOnly' => true];
        $rules[] = [['name'], 'string', 'max' => 255];
        $rules[] = [['sortOrder'], 'string', 'max' => 4];
        return $rules;
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
     * Returns the tab’s fields.
     *
     * @return FieldInterface[] The tab’s fields.
     */
    public function getFields(): array
    {
        if ($this->_fields !== null) {
            return $this->_fields;
        }

        $this->_fields = [];

        if ($layout = $this->getLayout()) {
            foreach ($layout->getFields() as $field) {
                /** @var Field $field */
                if ($field->tabId == $this->id) {
                    $this->_fields[] = $field;
                }
            }
        }

        return $this->_fields;
    }

    /**
     * Sets the tab’s fields.
     *
     * @param FieldInterface[] $fields The tab’s fields.
     */
    public function setFields(array $fields)
    {
        $this->_fields = $fields;
    }

    /**
     * Returns the tab’s anchor name.
     *
     * @return string
     */
    public function getHtmlId(): string
    {
        return 'tab-' . StringHelper::toKebabCase($this->name);
    }
}
