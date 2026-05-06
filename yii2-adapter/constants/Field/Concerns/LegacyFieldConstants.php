<?php

declare(strict_types=1);
namespace CraftCms\Cms\Field\Concerns;

use Closure;
use Craft;
use craft\base\Event as YiiEvent;
use craft\base\Field;
use craft\base\Model;
use craft\events\DefineEntryTypesForFieldEvent;
use craft\events\DefineInputOptionsEvent;
use craft\events\ElementCriteriaEvent;
use craft\events\FieldElementEvent as YiiFieldElementEvent;
use craft\events\FieldEvent as YiiFieldEvent;
use craft\events\LocateUploadedFilesEvent;
use craft\events\ModelEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\fields\Assets;
use craft\fields\BaseOptionsField;
use craft\fields\BaseRelationField;
use craft\fields\Link;
use craft\fields\Matrix;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Events\ElementCriteriaResolving;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Events\AssetsUploadedFilesLocating;
use CraftCms\Cms\Field\Events\EntryTypesForFieldResolving;
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
use CraftCms\Cms\Field\Events\InputOptionsResolving;
use CraftCms\Cms\Field\Events\LinkTypesResolving;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Event;
use yii\base\InvalidConfigException;
use yii\validators\Validator;

/**
 * @internal
 * @deprecated 6.0.0
 * @phpstan-ignore trait.unused
 */
trait LegacyFieldConstants
{
    public const string EVENT_DEFINE_INPUT_HTML = 'defineInputHtml';

    public const string EVENT_DEFINE_ACTION_MENU_ITEMS = 'defineActionMenuItems';

    public const string EVENT_DEFINE_KEYWORDS = 'defineKeywords';

    public const string EVENT_AFTER_MERGE_INTO = 'afterMergeInto';

    public const string EVENT_AFTER_MERGE_FROM = 'afterMergeFrom';

    public const string EVENT_BEFORE_SAVE = 'beforeSave';

    public const string EVENT_AFTER_SAVE = 'afterSave';

    public const string EVENT_BEFORE_DELETE = 'beforeDelete';

    public const string EVENT_BEFORE_APPLY_DELETE = 'beforeApplyDelete';

    public const string EVENT_AFTER_DELETE = 'afterDelete';

    public const string EVENT_BEFORE_ELEMENT_SAVE = 'beforeElementSave';

    public const string EVENT_AFTER_ELEMENT_SAVE = 'afterElementSave';

    public const string EVENT_AFTER_ELEMENT_PROPAGATE = 'afterElementPropagate';

    public const string EVENT_BEFORE_ELEMENT_DELETE = 'beforeElementDelete';

    public const string EVENT_AFTER_ELEMENT_DELETE = 'afterElementDelete';

    public const string EVENT_BEFORE_ELEMENT_RESTORE = 'beforeElementRestore';

    public const string EVENT_AFTER_ELEMENT_RESTORE = 'afterElementRestore';

    public static function registerEvents(): void
    {
        Event::listen(function(FieldLifecycleSaving $event) {
            self::triggerModelEvent($event, $event->field::EVENT_BEFORE_SAVE, $event->isNew);
        });

        Event::listen(function(FieldLifecycleSaved $event) {
            self::triggerModelEvent($event, $event->field::EVENT_AFTER_SAVE, $event->isNew);
        });

        Event::listen(function(FieldLifecycleDeleting $event) {
            self::triggerModelEvent($event, $event->field::EVENT_BEFORE_DELETE);
        });

        Event::listen(function(FieldDeletionApplying $event) {
            self::triggerEvent($event, $event->field::EVENT_BEFORE_APPLY_DELETE);
        });

        Event::listen(function(FieldLifecycleDeleted $event) {
            self::triggerEvent($event, $event->field::EVENT_AFTER_DELETE);
        });

        Event::listen(function(FieldElementSaving $event) {
            self::triggerFieldElementEvent($event, $event->field::EVENT_BEFORE_ELEMENT_SAVE);
        });

        Event::listen(function(FieldElementSaved $event) {
            self::triggerFieldElementEvent($event, $event->field::EVENT_AFTER_ELEMENT_SAVE);
        });

        Event::listen(function(FieldElementPropagated $event) {
            self::triggerFieldElementEvent($event, $event->field::EVENT_AFTER_ELEMENT_PROPAGATE);
        });

        Event::listen(function(FieldElementDeleting $event) {
            self::triggerFieldElementEvent($event, $event->field::EVENT_BEFORE_ELEMENT_DELETE);
        });

        Event::listen(function(FieldElementDeleted $event) {
            self::triggerFieldElementEvent($event, $event->field::EVENT_AFTER_ELEMENT_DELETE);
        });

        Event::listen(function(FieldElementRestoring $event) {
            self::triggerFieldElementEvent($event, $event->field::EVENT_BEFORE_ELEMENT_RESTORE);
        });

        Event::listen(function(FieldElementRestored $event) {
            self::triggerFieldElementEvent($event, $event->field::EVENT_AFTER_ELEMENT_RESTORE);
        });

        Event::listen(function(FieldMergeIntoCompleted $event) {
            self::triggerFieldEvent($event, $event->field::EVENT_AFTER_MERGE_INTO, $event->persistingField);
        });

        Event::listen(function(FieldMergeFromCompleted $event) {
            self::triggerFieldEvent($event, $event->field::EVENT_AFTER_MERGE_FROM, $event->outgoingField);
        });

        self::assetsEvents();
        self::optionsFieldEvents();
        self::relationFieldEvents();
        self::linkEvents();
        self::matrixEvents();
    }

    /**
     * @event LocateUploadedFilesEvent The event that is triggered when identifying any uploaded files that
     * should be stored as assets and related by the field.
     *
     * @since 4.0.2
     */
    public const string EVENT_LOCATE_UPLOADED_FILES = 'locateUploadedFiles';

    private static function assetsEvents(): void
    {
        Event::listen(function(AssetsUploadedFilesLocating $event) {
            if (!$event->field instanceof \CraftCms\Cms\Field\Assets) {
                return;
            }

            if (!YiiEvent::hasHandlers(Assets::class, Assets::EVENT_LOCATE_UPLOADED_FILES)) {
                return;
            }

            $yiiEvent = new LocateUploadedFilesEvent([
                'element' => $event->element,
                'files' => $event->files,
                'sender' => $event->field,
            ]);

            YiiEvent::trigger(Assets::class, Assets::EVENT_LOCATE_UPLOADED_FILES, $yiiEvent);

            $event->files = $yiiEvent->files;
        });
    }

    /**
     * @event DefineInputOptionsEvent Event triggered when defining the options for the field's input.
     *
     * @since 4.4.0
     */
    public const string EVENT_DEFINE_OPTIONS = 'defineOptions';

    private static function optionsFieldEvents(): void
    {
        Event::listen(function(InputOptionsResolving $event) {
            if (!$event->field instanceof \CraftCms\Cms\Field\BaseOptionsField) {
                return;
            }

            if (!YiiEvent::hasHandlers(BaseOptionsField::class, BaseOptionsField::EVENT_DEFINE_OPTIONS)) {
                return;
            }

            $yiiEvent = new DefineInputOptionsEvent([
                'options' => $event->options,
                'value' => $event->value,
                'element' => $event->element,
                'sender' => $event->field,
            ]);

            YiiEvent::trigger(BaseOptionsField::class, BaseOptionsField::EVENT_DEFINE_OPTIONS, $yiiEvent);

            $event->options = $yiiEvent->options;
        });
    }

    /**
     * @event ElementCriteriaEvent The event that is triggered when defining the selection criteria for this field.
     */
    public const string EVENT_DEFINE_SELECTION_CRITERIA = 'defineSelectionCriteria';

    private static function relationFieldEvents(): void
    {
        Event::listen(function(ElementCriteriaResolving $event) {
            if (!$event->field instanceof \CraftCms\Cms\Field\BaseRelationField) {
                return;
            }

            if (!YiiEvent::hasHandlers(BaseRelationField::class, BaseRelationField::EVENT_DEFINE_SELECTION_CRITERIA)) {
                return;
            }

            $yiiEvent = new ElementCriteriaEvent([
                'criteria' => $event->criteria,
            ]);

            YiiEvent::trigger(BaseRelationField::class, BaseRelationField::EVENT_DEFINE_SELECTION_CRITERIA, $yiiEvent);

            $event->criteria = $yiiEvent->criteria;
        });
    }

    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering the link types for Link fields.
     *
     * @see types()
     */
    public const string EVENT_REGISTER_LINK_TYPES = 'registerLinkTypes';

    private static function linkEvents(): void
    {
        Event::listen(function(LinkTypesResolving $event) {
            if (!YiiEvent::hasHandlers(Link::class, Link::EVENT_REGISTER_LINK_TYPES)) {
                return;
            }

            $yiiEvent = new RegisterComponentTypesEvent([
                'types' => $event->types,
            ]);

            YiiEvent::trigger(Link::class, Link::EVENT_REGISTER_LINK_TYPES, $yiiEvent);

            $event->types = $yiiEvent->types;
        });
    }

    /**
     * @event DefineEntryTypesForFieldEvent The event that is triggered when defining the available entry types.
     *
     * @since 5.0.0
     */
    public const string EVENT_DEFINE_ENTRY_TYPES = 'defineEntryTypes';

    private static function matrixEvents(): void
    {
        Event::listen(function(EntryTypesForFieldResolving $event) {
            if (!$event->field instanceof \CraftCms\Cms\Field\Matrix) {
                return;
            }

            if (!YiiEvent::hasHandlers(Matrix::class, Matrix::EVENT_DEFINE_ENTRY_TYPES)) {
                return;
            }

            $yiiEvent = new DefineEntryTypesForFieldEvent([
                'entryTypes' => $event->entryTypes,
                'element' => $event->element,
                'value' => $event->value,
                'sender' => $event->field,
            ]);

            YiiEvent::trigger(Matrix::class, Matrix::EVENT_DEFINE_ENTRY_TYPES, $yiiEvent);

            $event->entryTypes = $yiiEvent->entryTypes;
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
                YiiEvent::trigger($class, $name, new YiiEvent(['sender' => $event->field]));
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

    private static function triggerFieldEvent(FieldEvent $event, string $name, FieldInterface $field): void
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

    private static function eventClasses(FieldInterface $field): array
    {
        $parentClasses = array_values(class_parents($field));

        return array_unique([
            $field::class,
            ...self::legacyEventClasses($field::class),
            ...$parentClasses,
            ...self::legacyEventClassesFor($parentClasses),
            ...class_implements($field),
            Field::class,
        ]);
    }

    private static function legacyEventClassesFor(array $classes): array
    {
        $legacyClasses = [];

        foreach ($classes as $class) {
            array_push($legacyClasses, ...self::legacyEventClasses($class));
        }

        return $legacyClasses;
    }

    private static function legacyEventClasses(string $class): array
    {
        if ($class === \CraftCms\Cms\Field\Field::class) {
            return [Field::class];
        }

        if (!str_starts_with($class, 'CraftCms\\Cms\\Field\\')) {
            return [];
        }

        $relativeClass = substr($class, strlen('CraftCms\\Cms\\Field\\'));

        if (str_contains($relativeClass, '\\')) {
            return [];
        }

        return ['craft\\fields\\' . $relativeClass];
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

    // Other compatibility methods

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
}
