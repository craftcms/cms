<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Craft;
use craft\events\DefineBehaviorsEvent;
use craft\events\DefineFieldsEvent;
use craft\events\DefineRulesEvent;
use craft\helpers\DateTimeHelper;
use craft\helpers\StringHelper;
use yii\validators\Validator;

/**
 * Model base class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
abstract class Model extends \yii\base\Model
{
    use ClonefixTrait;

    /**
     * @event \yii\base\Event The event that is triggered after the model's init cycle
     * @see init()
     */
    const EVENT_INIT = 'init';

    /**
     * @event DefineBehaviorsEvent The event that is triggered when defining the class behaviors
     * @see behaviors()
     */
    const EVENT_DEFINE_BEHAVIORS = 'defineBehaviors';

    /**
     * @event DefineRulesEvent The event that is triggered when defining the model rules
     * @see rules()
     * @since 3.1.0
     */
    const EVENT_DEFINE_RULES = 'defineRules';

    /**
     * @event DefineRulesEvent The event that is triggered when defining the arrayable fields
     * @see fields()
     * @since 3.5.0
     */
    const EVENT_DEFINE_FIELDS = 'defineFields';

    /**
     * @event DefineRulesEvent The event that is triggered when defining the extra arrayable fields
     * @see extraFields()
     * @since 3.5.0
     */
    const EVENT_DEFINE_EXTRA_FIELDS = 'defineExtraFields';

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

        if ($this->hasEventHandlers(self::EVENT_INIT)) {
            $this->trigger(self::EVENT_INIT);
        }
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        // Fire a 'defineBehaviors' event
        $event = new DefineBehaviorsEvent();
        $this->trigger(self::EVENT_DEFINE_BEHAVIORS, $event);
        return $event->behaviors;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = $this->defineRules();

        // Give plugins a chance to modify them
        $event = new DefineRulesEvent([
            'rules' => $rules,
        ]);
        $this->trigger(self::EVENT_DEFINE_RULES, $event);

        foreach ($event->rules as &$rule) {
            $this->_normalizeRule($rule);
        }

        return $event->rules;
    }

    /**
     * Normalizes a validation rule.
     *
     * @param Validator|array $rule
     */
    private function _normalizeRule(&$rule)
    {
        if (is_array($rule) && isset($rule[1]) && $rule[1] instanceof \Closure) {
            // Wrap the closure in another one, so InlineValidator doesn’t bind it to the model
            $method = $rule[1];
            $rule[1] = function($attribute, $params, $validator, $current) use ($method) {
                $method($attribute, $params, $validator, $current);
            };
        }
    }

    /**
     * Returns the validation rules for attributes.
     *
     * See [[rules()]] for details about what should be returned.
     *
     * Models should override this method instead of [[rules()]] so [[EVENT_DEFINE_RULES]] handlers can modify the
     * class-defined rules.
     *
     * @return array
     * @since 3.4.0
     */
    protected function defineRules(): array
    {
        return [];
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

        if (property_exists($this, 'dateDeleted')) {
            $attributes[] = 'dateDeleted';
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

        $event = new DefineFieldsEvent([
            'fields' => $fields,
        ]);
        $this->trigger(self::EVENT_DEFINE_FIELDS, $event);
        return $event->fields;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        $fields = parent::extraFields();
        $event = new DefineFieldsEvent([
            'fields' => $fields,
        ]);
        $this->trigger(self::EVENT_DEFINE_EXTRA_FIELDS, $event);
        return $event->fields;
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
     * @return string|null The error message, or null if there are no errors.
     * @deprecated in 3.0.0. Use [[getFirstError()]] instead.
     */
    public function getError(string $attribute)
    {
        Craft::$app->getDeprecator()->log('Model::getError()', 'getError() has been deprecated. Use getFirstError() instead.');

        return $this->getFirstError($attribute);
    }
}
