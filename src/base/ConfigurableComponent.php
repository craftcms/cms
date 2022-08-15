<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\events\DefineValueEvent;
use craft\helpers\DateTimeHelper;
use DateTime;
use ReflectionClass;
use ReflectionProperty;

/**
 * Component is the base class for classes representing Craft components that are configurable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
abstract class ConfigurableComponent extends Component implements ConfigurableComponentInterface
{
    /**
     * @event DefineValueEvent The event that is triggered when defining the component’s settings attributes, as returned by [[settingsAttributes()]].
     * @since 3.7.0
     */
    public const EVENT_DEFINE_SETTINGS_ATTRIBUTES = 'defineSettingsAttributes';

    /**
     * @inheritdoc
     */
    public function settingsAttributes(): array
    {
        // By default, include all public, non-static properties that were not defined in an abstract class
        $class = new ReflectionClass($this);
        $names = [];

        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic() && !$property->getDeclaringClass()->isAbstract()) {
                $names[] = $property->getName();
            }
        }

        if ($this->hasEventHandlers(self::EVENT_DEFINE_SETTINGS_ATTRIBUTES)) {
            $event = new DefineValueEvent([
                'value' => $names,
            ]);
            $this->trigger(self::EVENT_DEFINE_SETTINGS_ATTRIBUTES, $event);
            $names = $event->value;
        }

        return $names;
    }

    /**
     * @inheritdoc
     */
    public function getSettings(): array
    {
        $settings = [];
        $datetimeAttributes = array_flip($this->datetimeAttributes());

        foreach ($this->settingsAttributes() as $attribute) {
            $value = $this->$attribute;
            if ($value instanceof DateTime || isset($datetimeAttributes[$attribute])) {
                $value = DateTimeHelper::toIso8601($value) ?: null;
            }
            $settings[$attribute] = $value;
        }

        return $settings;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return null;
    }
}
