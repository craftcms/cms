<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Closure;
use craft\events\DefineBehaviorsEvent;
use craft\events\DefineFieldsEvent;
use craft\events\DefineRulesEvent;
use craft\helpers\App;
use craft\helpers\Component;
use craft\helpers\DateTimeHelper;
use craft\helpers\StringHelper;
use craft\helpers\Typecast;
use yii\validators\Validator;

/**
 * Model base class.
 *
 * @method ($attribute is string ? string[] : array<string,string[]>) getErrors(?string $attribute = null)
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
abstract class Model extends \yii\base\Model implements ModelInterface
{
    use ClonefixTrait;

    /**
     * @event \yii\base\Event The event that is triggered after the model's init cycle
     * @see init()
     */
    public const EVENT_INIT = 'init';

    /**
     * @event DefineBehaviorsEvent The event that is triggered when defining the class behaviors
     * @see behaviors()
     */
    public const EVENT_DEFINE_BEHAVIORS = 'defineBehaviors';

    /**
     * @event DefineRulesEvent The event that is triggered when defining the model rules
     * @see rules()
     * @since 3.1.0
     */
    public const EVENT_DEFINE_RULES = 'defineRules';

    /**
     * @event DefineFieldsEvent The event that is triggered when defining the arrayable fields
     * @see fields()
     * @since 3.5.0
     */
    public const EVENT_DEFINE_FIELDS = 'defineFields';

    /**
     * @event DefineFieldsEvent The event that is triggered when defining the extra arrayable fields
     * @see extraFields()
     * @since 3.5.0
     */
    public const EVENT_DEFINE_EXTRA_FIELDS = 'defineExtraFields';

    public function __construct($config = [])
    {
        // Typecast the properties
        Typecast::properties(static::class, $config);

        // Normalize the DateTime attributes
        foreach ($this->datetimeAttributes() as $attribute) {
            if (array_key_exists($attribute, $config) && $config[$attribute] !== null) {
                $config[$attribute] = DateTimeHelper::toDateTime($config[$attribute]);
            }
        }

        // Call App::configure() rather than BaseYii::configure() (via BaseObject::__construct()),
        // in case \Yii isn't loaded yet. (Mainly an issue for GeneralConfig/DbConfig, if config/general.php
        // or config/db.php return an array.)
        // Note that inlining the foreach loop is no good, because then private/protected properties will be
        // set directly rather than going through __set().
        App::configure($this, $config);

        // Intentionally not passing $config along
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if ($this->hasEventHandlers(self::EVENT_INIT)) {
            $this->trigger(self::EVENT_INIT);
        }
    }

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = $this->defineBehaviors();

        // Fire a 'defineBehaviors' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_BEHAVIORS)) {
            $event = new DefineBehaviorsEvent(['behaviors' => $behaviors]);
            $this->trigger(self::EVENT_DEFINE_BEHAVIORS, $event);
            return $event->behaviors;
        }

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = $this->defineRules();

        // Fire a 'defineRules' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_RULES)) {
            $event = new DefineRulesEvent(['rules' => $rules]);
            $this->trigger(self::EVENT_DEFINE_RULES, $event);
            $rules = $event->rules;
        }

        foreach ($rules as &$rule) {
            $this->_normalizeRule($rule);
        }

        return $rules;
    }

    /**
     * Normalizes a validation rule.
     *
     * @param array|Validator $rule
     */
    private function _normalizeRule(array|Validator &$rule): void
    {
        if (is_array($rule) && isset($rule[1]) && $rule[1] instanceof Closure) {
            // Wrap the closure in another one, so InlineValidator doesn’t bind it to the model
            $method = $rule[1];
            $rule[1] = function($attribute, $params, $validator, $current) use ($method) {
                $method($attribute, $params, $validator, $current);
            };
        }
    }

    /**
     * Returns the behaviors to attach to this class.
     *
     * See [[behaviors()]] for details about what should be returned.
     *
     * Models should override this method instead of [[behaviors()]] so [[EVENT_DEFINE_BEHAVIORS]] handlers can modify the
     * class-defined behaviors.
     *
     * @return array
     * @since 4.0.0
     */
    protected function defineBehaviors(): array
    {
        return [];
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
     * @deprecated in 4.0.0. Use [[\DateTime]] type declarations instead.
     */
    public function datetimeAttributes(): array
    {
        $attributes = [];

        if (property_exists($this, 'dateCreated')) {
            $attributes[] = 'dateCreated';
        }

        if (property_exists($this, 'dateAdded')) {
            $attributes[] = 'dateAdded';
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
     * @since 4.0.0
     */
    public function setAttributes($values, $safeOnly = true): void
    {
        // Typecast them
        Typecast::properties(static::class, $values);

        // Normalize the date/time attributes
        foreach ($this->datetimeAttributes() as $name) {
            if (isset($values[$name])) {
                $values[$name] = DateTimeHelper::toDateTime($values[$name]) ?: null;
            }
        }

        parent::setAttributes($values, $safeOnly);
    }

    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        $fields = parent::fields();

        $datetimeAttributes = Component::datetimeAttributes($this);

        // Have all DateTime attributes converted to ISO-8601 strings
        foreach ($datetimeAttributes as $attribute) {
            $fields[$attribute] = function($model, $attribute) {
                $date = $model->$attribute;
                if ($date) {
                    return DateTimeHelper::toIso8601($date, true);
                }

                return $model->$attribute;
            };
        }

        // Fire a 'defineFields' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_FIELDS)) {
            $event = new DefineFieldsEvent(['fields' => $fields]);
            $this->trigger(self::EVENT_DEFINE_FIELDS, $event);
            return $event->fields;
        }

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function extraFields(): array
    {
        $fields = parent::extraFields();

        // Fire a 'defineExtraFields' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_EXTRA_FIELDS)) {
            $event = new DefineFieldsEvent(['fields' => $fields]);
            $this->trigger(self::EVENT_DEFINE_EXTRA_FIELDS, $event);
            return $event->fields;
        }

        return $fields;
    }

    /**
     * Adds errors from another model, with a given attribute name prefix.
     *
     * @param \yii\base\Model $model The other model
     * @param string $attrPrefix The prefix that should be added to error attributes when adding them to this model
     */
    public function addModelErrors(\yii\base\Model $model, string $attrPrefix = ''): void
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
    public function hasErrors($attribute = null): bool
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
                if (str_starts_with($attr, $attribute . '.')) {
                    return true;
                }
                if (str_starts_with($attr, $attribute . '[')) {
                    return true;
                }
            }
        }

        return false;
    }
}
