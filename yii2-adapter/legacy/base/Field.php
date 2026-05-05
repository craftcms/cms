<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Closure;
use Craft;
use craft\base\Event as YiiEvent;
use craft\events\FieldElementEvent as YiiFieldElementEvent;
use craft\events\FieldEvent as YiiFieldEvent;
use craft\events\ModelEvent;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Events\FieldDeletionApplying;
use CraftCms\Cms\Field\Events\FieldElementDeleted;
use CraftCms\Cms\Field\Events\FieldElementDeleting;
use CraftCms\Cms\Field\Events\FieldElementOccurred;
use CraftCms\Cms\Field\Events\FieldElementPropagated;
use CraftCms\Cms\Field\Events\FieldElementRestored;
use CraftCms\Cms\Field\Events\FieldElementRestoring;
use CraftCms\Cms\Field\Events\FieldElementSaved;
use CraftCms\Cms\Field\Events\FieldElementSaving;
use CraftCms\Cms\Field\Events\FieldEvent;
use CraftCms\Cms\Field\Events\FieldLifecycleDeleted;
use CraftCms\Cms\Field\Events\FieldLifecycleDeleting;
use CraftCms\Cms\Field\Events\FieldLifecycleSaved;
use CraftCms\Cms\Field\Events\FieldLifecycleSaving;
use CraftCms\Cms\Field\Events\FieldMergeFromCompleted;
use CraftCms\Cms\Field\Events\FieldMergeIntoCompleted;
use CraftCms\Cms\Support\Arr;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\Event;
use yii\base\InvalidConfigException;
use yii\validators\Validator;

/**
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Field} instead.
 */
abstract class Field extends \CraftCms\Cms\Field\Field
{
    use FieldEventConstants;
    use LegacyEventConstants;

    public string $translationMethod {
        get {
            return $this->_translationMethod->value;
        }
    set(string | TranslationMethod $value) {
            $translationMethod = $value instanceof TranslationMethod
                ? $value
                : TranslationMethod::tryFrom($value);

            if ($translationMethod === null) {
                $supportedTranslationMethods = static::supportedTranslationMethods();
                $translationMethod = reset($supportedTranslationMethods) ?: TranslationMethod::None;
            }

            $this->_translationMethod = $translationMethod;
        }
    }

    public function __construct($config = [])
    {
        parent::__construct(Arr::except($config, ['fieldLimit', 'limitUnit']));
    }

    public static function registerEvents(): void
    {
        Event::listen(function(FieldLifecycleSaving $event) {
            self::triggerModelEvent($event, self::EVENT_BEFORE_SAVE, $event->isNew);
        });

        Event::listen(function(FieldLifecycleSaved $event) {
            self::triggerModelEvent($event, self::EVENT_AFTER_SAVE, $event->isNew);
        });

        Event::listen(function(FieldLifecycleDeleting $event) {
            self::triggerModelEvent($event, self::EVENT_BEFORE_DELETE);
        });

        Event::listen(function(FieldDeletionApplying $event) {
            self::triggerEvent($event, self::EVENT_BEFORE_APPLY_DELETE);
        });

        Event::listen(function(FieldLifecycleDeleted $event) {
            self::triggerEvent($event, self::EVENT_AFTER_DELETE);
        });

        Event::listen(function(FieldElementSaving $event) {
            self::triggerFieldElementEvent($event, self::EVENT_BEFORE_ELEMENT_SAVE);
        });

        Event::listen(function(FieldElementSaved $event) {
            self::triggerFieldElementEvent($event, self::EVENT_AFTER_ELEMENT_SAVE);
        });

        Event::listen(function(FieldElementPropagated $event) {
            self::triggerFieldElementEvent($event, self::EVENT_AFTER_ELEMENT_PROPAGATE);
        });

        Event::listen(function(FieldElementDeleting $event) {
            self::triggerFieldElementEvent($event, self::EVENT_BEFORE_ELEMENT_DELETE);
        });

        Event::listen(function(FieldElementDeleted $event) {
            self::triggerFieldElementEvent($event, self::EVENT_AFTER_ELEMENT_DELETE);
        });

        Event::listen(function(FieldElementRestoring $event) {
            self::triggerFieldElementEvent($event, self::EVENT_BEFORE_ELEMENT_RESTORE);
        });

        Event::listen(function(FieldElementRestored $event) {
            self::triggerFieldElementEvent($event, self::EVENT_AFTER_ELEMENT_RESTORE);
        });

        Event::listen(function(FieldMergeIntoCompleted $event) {
            self::triggerFieldEvent($event, self::EVENT_AFTER_MERGE_INTO, $event->persistingField);
        });

        Event::listen(function(FieldMergeFromCompleted $event) {
            self::triggerFieldEvent($event, self::EVENT_AFTER_MERGE_FROM, $event->outgoingField);
        });
    }

    private static function triggerModelEvent(FieldEvent $event, string $name, bool $isNew = false): void
    {
        foreach (self::eventClasses($event->field) as $class) {
            if (!YiiEvent::hasHandlers($class, $name)) {
                continue;
            }

            $yiiEvent = new ModelEvent([
                'sender' => $event->field,
                'isNew' => $isNew,
            ]);

            YiiEvent::trigger($class, $name, $yiiEvent);

            if (property_exists($event, 'isValid') && !$yiiEvent->isValid) {
                $event->isValid = false;
            }
        }
    }

    private static function triggerEvent(FieldEvent $event, string $name): void
    {
        foreach (self::eventClasses($event->field) as $class) {
            if (YiiEvent::hasHandlers($class, $name)) {
                YiiEvent::trigger($class, $name, new \craft\base\Event(['sender' => $event->field]));
            }
        }
    }

    private static function triggerFieldElementEvent(FieldElementOccurred $event, string $name): void
    {
        foreach (self::eventClasses($event->field) as $class) {
            if (!YiiEvent::hasHandlers($class, $name)) {
                continue;
            }

            $yiiEvent = new YiiFieldElementEvent([
                'sender' => $event->field,
                'element' => $event->element,
                'isNew' => $event->isNew,
            ]);

            YiiEvent::trigger($class, $name, $yiiEvent);

            if (!$yiiEvent->isValid) {
                $event->isValid = false;
            }
        }
    }

    private static function triggerFieldEvent(FieldEvent $event, string $name, \CraftCms\Cms\Field\Contracts\FieldInterface $field): void
    {
        foreach (self::eventClasses($event->field) as $class) {
            if (YiiEvent::hasHandlers($class, $name)) {
                YiiEvent::trigger($class, $name, new YiiFieldEvent([
                    'sender' => $event->field,
                    'field' => $field,
                ]));
            }
        }
    }

    private static function eventClasses(\CraftCms\Cms\Field\Contracts\FieldInterface $field): array
    {
        return array_unique([
            $field::class,
            ...class_parents($field),
            ...class_implements($field),
            self::class,
        ]);
    }

    public static function modifyQuery(Builder $query, array $instances, mixed $value): Builder
    {
        if (!method_exists(static::class, 'queryCondition')) {
            return $query;
        }

        $params = [];

        $condition = static::queryCondition($instances, $value, $params);

        if ($condition === null || $condition === false) {
            return $query;
        }

        $db = Craft::$app->getDb();
        $sql = $db->getQueryBuilder()->buildCondition($condition, $params);

        // Yii uses named parameters, Laravel uses positional
        $sql = preg_replace('/:qp\d+/', '?', $sql);

        return $query->whereRaw($sql, array_values($params));
    }

    public function getElementValidationRules(): array
    {
        return [];
    }

    public function getElementRules(ElementInterface $element): array
    {
        if (!$element instanceof Model) {
            return [];
        }

        return [
            function(string $attribute, mixed $value, Closure $fail) use ($element) {
                $scenario = $element->ruleset->getScenario();
                $isEmpty = fn() => $this->isValueEmpty($element->getFieldValue($this->handle), $element);

                foreach ($this->getElementValidationRules() as $rule) {
                    $validator = $this->_normalizeFieldValidator($attribute, $rule, $element, $isEmpty);

                    if (
                        in_array($element->ruleset->getScenario(), $validator->on) ||
                        (empty($validator->on) && !in_array($scenario, $validator->except))
                    ) {
                        $validator->validateAttributes($element);
                    }
                }
            },
        ];
    }

    /**
     * Normalizes a field’s validation rule.
     *
     *
     * @throws InvalidConfigException
     */
    private function _normalizeFieldValidator(
        string $attribute,
        mixed $rule,
        Model $element,
        callable $isEmpty,
    ): Validator {
        if ($rule instanceof Validator) {
            return $rule;
        }

        if (is_string($rule)) {
            // "Validator" syntax
            $rule = [$attribute, $rule, 'on' => [ElementRules::SCENARIO_DEFAULT, ElementRules::SCENARIO_LIVE]];
        }

        if (!is_array($rule) || !isset($rule[0])) {
            throw new InvalidConfigException('Invalid validation rule for custom field "' . $this->handle . '".');
        }

        if (isset($rule[1])) {
            // Make sure the attribute name starts with 'field:'
            if ($rule[0] === $this->handle) {
                $rule[0] = $attribute;
            }
        } else {
            // ["Validator"] syntax
            array_unshift($rule, $attribute);
        }

        if (
            (!is_string($rule[1]) || !isset(Validator::$builtInValidators[$rule[1]])) &&
            (is_callable($rule[1]) || method_exists($this, $rule[1]))
        ) {
            // InlineValidator assumes that the closure is on the model being validated
            // so it won’t pass a reference to the element
            $rule['params'] = [
                $this,
                $rule[1],
                $rule['params'] ?? null,
            ];
            $rule[1] = 'validateCustomFieldAttribute';
        }

        // Set 'isEmpty' to the field's isEmpty() method by default
        if (!array_key_exists('isEmpty', $rule)) {
            $rule['isEmpty'] = $isEmpty;
        }

        // Set 'on' to the main scenarios by default
        if (!array_key_exists('on', $rule)) {
            $rule['on'] = [ElementRules::SCENARIO_DEFAULT, ElementRules::SCENARIO_LIVE];
        }

        return Validator::createValidator($rule[1], $element, (array) $rule[0], array_slice($rule, 2));
    }

    /**
     * Calls a custom validation function on a custom field.
     *
     * This will be called by [[\yii\validators\InlineValidator]] if a custom field specified
     * a closure or the name of a class-level method as the validation type.
     *
     * @param  string  $attribute  The field handle
     */
    public function validateCustomFieldAttribute(string $attribute, ?array $params = null): void
    {
        /** @var array|null $params */
        [$field, $method, $fieldParams] = $params;

        if (is_string($method) && !is_callable($method)) {
            $method = [$field, $method];
        }

        $method($this, $fieldParams);
    }
}
