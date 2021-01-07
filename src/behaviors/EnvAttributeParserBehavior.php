<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\behaviors;

use Craft;
use craft\helpers\StringHelper;
use yii\base\Behavior;
use yii\base\Model;
use yii\validators\UrlValidator;

/**
 * EnvAttributeParserBehavior can be applied to models with attributes that can be
 * set to either environment variables (`$VARIABLE_NAME`) or aliases (`@aliasName`).
 *
 * ---
 *
 * ```php
 * public function behaviors()
 * {
 *     return [
 *         'parser' => [
 *             'class' => EnvAttributeParserBehavior::class,
 *             'attributes' => ['attr1', 'attr2', '...'],
 *         ],
 *     ];
 * }
 * ```
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.0
 */
class EnvAttributeParserBehavior extends Behavior
{
    /**
     * @var Model
     */
    public $owner;

    /**
     * @var string[]|callable[] The attributes names that can be set to environment
     * variables (`$VARIABLE_NAME`) and/or aliases (`@aliasName`).
     *
     * If the raw (unparsed) attribute value can’t be obtained from the attribute directly (`$model->foo`),
     * then the attribute name should be specified as an array key instead, and the value should be set to the
     * raw value, or a callable that returns the raw value. For example:
     *
     * ```php
     * 'attributes' => [
     *     'foo' => '$FOO',
     *     'bar' => function() {
     *         return $this->_bar;
     *     },
     * ],
     * ```
     */
    public $attributes = [];

    /**
     * @var array Keeps track of the original attribute values
     */
    private $_values;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            Model::EVENT_BEFORE_VALIDATE => 'beforeValidate',
            Model::EVENT_AFTER_VALIDATE => 'afterValidate',
        ];
    }

    /**
     * Replaces attribute values before validation occurs.
     */
    public function beforeValidate()
    {
        $this->_values = [];

        foreach ($this->attributes as $i => $attribute) {
            if (is_string($i)) {
                if (is_callable($attribute)) {
                    $value = $attribute();
                } else {
                    $value = $attribute;
                }
                $attribute = $i;
            } else {
                $value = $this->owner->$attribute;
            }

            if (($parsed = Craft::parseEnv($value)) !== $value) {
                $this->_values[$attribute] = $value;
                $this->owner->$attribute = $parsed;

                foreach ($this->owner->getActiveValidators($attribute) as $validator) {
                    if ($validator instanceof UrlValidator) {
                        $validator->defaultScheme = null;
                    }

                    if (is_string($validator->message)) {
                        $validator->message = StringHelper::ensureRight($validator->message, ' ({value})');
                    }
                }
            }
        }
    }

    /**
     * Restores the original attribute values after validation occurs.
     */
    public function afterValidate()
    {
        foreach ($this->_values as $attribute => $value) {
            $this->owner->$attribute = $value;
        }
    }
}
