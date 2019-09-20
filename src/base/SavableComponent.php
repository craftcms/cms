<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\events\ModelEvent;

/**
 * SavableComponent is the base class for classes representing savable Craft components in terms of objects.
 *
 * @property bool $isNew Whether the component is new (unsaved)
 * @property array $settings The component’s settings
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class SavableComponent extends Component implements SavableComponentInterface
{
    // Traits
    // =========================================================================

    use SavableComponentTrait;

    // Constants
    // =========================================================================

    /**
     * @event ModelEvent The event that is triggered before the component is saved
     * You may set [[ModelEvent::isValid]] to `false` to prevent the component from getting saved.
     */
    const EVENT_BEFORE_SAVE = 'beforeSave';

    /**
     * @event ModelEvent The event that is triggered after the component is saved
     */
    const EVENT_AFTER_SAVE = 'afterSave';

    /**
     * @event ModelEvent The event that is triggered before the component is deleted
     * You may set [[ModelEvent::isValid]] to `false` to prevent the component from getting deleted.
     */
    const EVENT_BEFORE_DELETE = 'beforeDelete';

    /**
     * @event ModelEvent The event that is triggered before the delete is applied to the database
     */
    const EVENT_BEFORE_APPLY_DELETE = 'beforeApplyDelete';

    /**
     * @event \yii\base\Event The event that is triggered after the component is deleted
     */
    const EVENT_AFTER_DELETE = 'afterDelete';

    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function isSelectable(): bool
    {
        return true;
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getIsNew(): bool
    {
        return (!$this->id || strpos($this->id, 'new') === 0);
    }

    /**
     * @inheritdoc
     */
    public function getSettings(): array
    {
        $settings = [];

        foreach ($this->settingsAttributes() as $attribute) {
            $settings[$attribute] = $this->$attribute;
        }

        return $settings;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function settingsAttributes(): array
    {
        // By default, include all public, non-static properties that were not defined in an abstract class
        $class = new \ReflectionClass($this);
        $names = [];

        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic() && !$property->getDeclaringClass()->isAbstract()) {
                $names[] = $property->getName();
            }
        }

        return $names;
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function beforeSave(bool $isNew): bool
    {
        // Trigger a 'beforeSave' event
        $event = new ModelEvent([
            'isNew' => $isNew,
        ]);
        $this->trigger(self::EVENT_BEFORE_SAVE, $event);

        return $event->isValid;
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew)
    {
        // Trigger an 'afterSave' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE)) {
            $this->trigger(self::EVENT_AFTER_SAVE, new ModelEvent([
                'isNew' => $isNew,
            ]));
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete(): bool
    {
        // Trigger a 'beforeDelete' event
        $event = new ModelEvent();
        $this->trigger(self::EVENT_BEFORE_DELETE, $event);

        return $event->isValid;
    }

    /**
     * @inheritdoc
     */
    public function beforeApplyDelete()
    {
        // Trigger an 'beforeApplyDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_DELETE);
        }
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        // Trigger an 'afterDelete' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE)) {
            $this->trigger(self::EVENT_AFTER_DELETE);
        }
    }
}
