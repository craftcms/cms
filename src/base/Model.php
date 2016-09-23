<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\base;

use Craft;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\DateTimeHelper;
use yii\base\UnknownMethodException;

/**
 * Model base class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
abstract class Model extends \yii\base\Model
{
    // Static
    // =========================================================================

    /**
     * Instantiates and populates a new model instance with the given set of attributes.
     *
     * @param mixed $config Attribute values to populate the model with (name => value).
     *
     * @return $this The new model
     */
    public static function create($config)
    {
        $model = new static();
        static::populateModel($model, ArrayHelper::toArray($config, [], false));

        return $model;
    }

    /**
     * Populates a new model instance with a given set of attributes.
     *
     * @param Model $model  The model to be populated.
     * @param array $config Attribute values to populate the model with (name => value).
     *
     * @return void
     */
    public static function populateModel($model, $config)
    {
        $attributes = array_flip($model->attributes());
        $datetimeAttributes = array_flip($model->datetimeAttributes());

        foreach ($config as $name => $value) {
            if (isset($attributes[$name]) || $model->canSetProperty($name)) {
                if ($value !== null && isset($datetimeAttributes[$name])) {
                    $value = DateTimeHelper::toDateTime($value);
                }

                $model->$name = $value;
            }
        }
    }

    // Properties
    // =========================================================================

    /**
     * @var string
     */
    protected $classSuffix = 'Model';

    // Public Methods
    // =========================================================================

    /**
     * Magic __call() method, used for chain-setting attribute values.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return $this
     * @throws UnknownMethodException when calling an unknown method
     */
    public function __call($name, $arguments)
    {
        try {
            return parent::__call($name, $arguments);
        } catch (UnknownMethodException $e) {
            // Is this one of our attributes?
            if (in_array($name, $this->attributes())) {
                $copy = $this->copy();

                if (count($arguments) == 1) {
                    $copy->$name = $arguments[0];
                } else {
                    $copy->$name = $arguments;
                }

                return $copy;
            }

            throw $e;
        }
    }

    /**
     * Returns the names of any attributes that should be converted to DateTime objects from [[populate()]].
     *
     * @return string[]
     */
    public function datetimeAttributes()
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
            $fields[$attribute] = function ($model, $attribute) {
                if (!empty($model->$attribute)) {
                    return DateTimeHelper::toIso8601($model->$attribute);
                }

                return $model->$attribute;
            };
        }

        return $fields;
    }

    /**
     * Returns all errors in a single list.
     *
     * @return array
     */
    public function getAllErrors()
    {
        $errors = [];

        foreach ($this->getErrors() as $attributeErrors) {
            $errors = array_merge($errors, $attributeErrors);
        }

        return $errors;
    }

    /**
     * Returns a copy of this model.
     *
     * @return $this
     */
    public function copy()
    {
        $class = get_class($this);

        return new $class($this->getAttributes());
    }

    // Deprecated Methods
    // -------------------------------------------------------------------------

    /**
     * Returns the first error of the specified attribute.
     *
     * @param string $attribute The attribute name.
     *
     * @return string The error message. Null is returned if no error.
     *
     * @deprecated in 3.0. Use [[getFirstError()]] instead.
     */
    public function getError($attribute)
    {
        Craft::$app->getDeprecator()->log('Model::getError()', 'getError() has been deprecated. Use getFirstError() instead.');

        return $this->getFirstError($attribute);
    }
}
