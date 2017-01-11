<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\behaviors;

use Craft;
use craft\models\FieldLayout;
use yii\base\Behavior;
use yii\base\InvalidConfigException;

/**
 * Field Layout behavior.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class FieldLayoutBehavior extends Behavior
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The element type that the field layout will be associated with
     */
    public $elementType;

    /**
     * @var string The name of the attribute on the owner class that is used to store the field layout’s ID
     */
    public $idAttribute = 'fieldLayoutId';

    /**
     * @var FieldLayout|null The field layout associated with the owner
     */
    private $_fieldLayout;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @throws InvalidConfigException if the behavior was not configured properly
     */
    public function init()
    {
        parent::init();

        if ($this->elementType === null) {
            throw new InvalidConfigException('The element type has not been set.');
        }

        if ($this->idAttribute === null) {
            throw new InvalidConfigException('The ID attribute has not been set.');
        }
    }

    /**
     * Returns the owner's field layout.
     *
     * @return FieldLayout
     */
    public function getFieldLayout(): FieldLayout
    {
        if ($this->_fieldLayout === null) {
            if ($id = $this->owner->{$this->idAttribute}) {
                $this->_fieldLayout = Craft::$app->getFields()->getLayoutById($id);
            }

            /** @noinspection NotOptimalIfConditionsInspection */
            if ($this->_fieldLayout === null) {
                $this->_fieldLayout = new FieldLayout();
                $this->_fieldLayout->type = $this->elementType;
            }
        }

        return $this->_fieldLayout;
    }

    /**
     * Sets the owner's field layout.
     *
     * @param FieldLayout $fieldLayout
     *
     * @return void
     */
    public function setFieldLayout(FieldLayout $fieldLayout)
    {
        $this->_fieldLayout = $fieldLayout;
    }
}
