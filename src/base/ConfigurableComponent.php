<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\helpers\DateTimeHelper;

/**
 * Component is the base class for classes representing Craft components that are configurable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
abstract class ConfigurableComponent extends Component implements ConfigurableComponentInterface
{
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

    /**
     * @inheritdoc
     */
    public function getSettings(): array
    {
        $settings = [];
        $datetimeAttributes = array_flip($this->datetimeAttributes());

        foreach ($this->settingsAttributes() as $attribute) {
            if (isset($datetimeAttributes[$attribute])) {
                $settings[$attribute] = DateTimeHelper::toIso8601($this->$attribute) ?: null;
            } else {
                $settings[$attribute] = $this->$attribute;
            }
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
}
