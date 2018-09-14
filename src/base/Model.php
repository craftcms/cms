<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\helpers\StringHelper;

/**
 * Model base class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class Model extends \yii\base\Model
{
    // Traits
    // =========================================================================

    use ClonefixTrait;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Normalize the DateTime attributes
        foreach ($this->datetimeAttributes() as $attribute) {
            if ($this->$attribute !== null) {
                $this->$attribute = DateTimeHelper::toDateTime($this->$attribute);
            }
        }
    }

    /**
     * Returns the names of any attributes that should hold [[\DateTime]] values.
     *
     * @return string[]
     * @see init()
     * @see fields()
     */
    public function datetimeAttributes(): array
    {
        $attributes = [];

        if (property_exists($this, 'dateCreated')) {
            $attributes[] = 'dateCreated';
        }

        if (property_exists($this, 'dateUpdated')) {
            $attributes[] = 'dateUpdated';
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        // Have all DateTime attributes converted to ISO-8601 strings
        foreach ($this->datetimeAttributes() as $attribute) {
            $fields[$attribute] = function($model, $attribute) {
                if (!empty($model->$attribute)) {
                    return DateTimeHelper::toIso8601($model->$attribute);
                }

                return $model->$attribute;
            };
        }

        return $fields;
    }

    /**
     * Adds errors from another model, with a given attribute name prefix.
     *
     * @param \yii\base\Model $model The other model
     * @param string $attrPrefix The prefix that should be added to error attributes when adding them to this model
     */
    public function addModelErrors(\yii\base\Model $model, string $attrPrefix = '')
    {
        if ($attrPrefix !== '') {
            $attrPrefix = rtrim($attrPrefix, '.') . '.';
        }

        foreach ($model->getErrors() as $attribute => $errors) {
            foreach ($errors as $error) {
                $this->addError($attrPrefix . $attribute, $error);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function hasErrors($attribute = null)
    {
        $includeNested = $attribute !== null && StringHelper::endsWith($attribute, '.*');

        if ($includeNested) {
            $attribute = StringHelper::removeRight($attribute, '.*');
        }

        if (parent::hasErrors($attribute)) {
            return true;
        }

        if ($includeNested) {
            foreach ($this->getErrors() as $attr => $errors) {
                if (strpos($attr, $attribute . '.') === 0) {
                    return true;
                }
                if (strpos($attr, $attribute . '[') === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    // Deprecated Methods
    // -------------------------------------------------------------------------

    /**
     * Returns the first error of the specified attribute.
     *
     * @param string $attribute The attribute name.
     * @return string The error message. Null is returned if no error.
     * @deprecated in 3.0. Use [[getFirstError()]] instead.
     */
    public function getError(string $attribute): string
    {
        Craft::$app->getDeprecator()->log('Model::getError()', 'getError() has been deprecated. Use getFirstError() instead.');

        return $this->getFirstError($attribute);
    }
}
