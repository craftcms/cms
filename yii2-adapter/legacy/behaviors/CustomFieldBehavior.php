<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\behaviors;

use yii\base\Behavior;

/**
 * Custom field behavior
 *
 * This class provides magic access for all custom field handles.
 *
 * @property \CraftCms\Cms\Element\Element $owner
 */
class CustomFieldBehavior extends Behavior
{
    /**
     * @var bool Whether the behavior should provide methods based on the field handles.
     */
    public bool $hasMethods = false;

    /**
     * @var bool Whether properties on the class should be settable directly.
     */
    public bool $canSetProperties = true;

    /**
     * @var array<string,bool> List of supported field handles.
     */
    public static $fieldHandles = [];

    /**
     * @var array<string,bool> List of generated field handles.
     */
    public static $generatedFieldHandles = [];

    /**
     * @var array Additional custom field values we don't know about yet.
     */
    private array $_customFieldValues = [];

    /**
     * {@inheritdoc}
     */
    public function __call($name, $params)
    {
        if (
            $this->hasMethods &&
            (isset(self::$fieldHandles[$name]) || isset(self::$generatedFieldHandles[$name])) &&
            count($params) === 1
        ) {
            $this->$name = $params[0];

            return $this->owner;
        }

        return parent::__call($name, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function hasMethod($name): bool
    {
        if ($this->hasMethods && (isset(self::$fieldHandles[$name]) || isset(self::$generatedFieldHandles[$name]))) {
            return true;
        }

        return parent::hasMethod($name);
    }

    /**
     * {@inheritdoc}
     */
    public function __isset($name): bool
    {
        if (isset(self::$fieldHandles[$name]) || isset(self::$generatedFieldHandles[$name])) {
            return true;
        }

        return parent::__isset($name);
    }

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        if (isset(self::$fieldHandles[$name]) || isset(self::$generatedFieldHandles[$name])) {
            if (method_exists($this->owner, 'getCustomFieldRawValue')) {
                return isset(self::$generatedFieldHandles[$name])
                    ? $this->owner->getGeneratedFieldRawValue($name)
                    : $this->owner->getCustomFieldRawValue($name);
            }

            return $this->_customFieldValues[$name] ?? null;
        }

        return parent::__get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function __set($name, $value)
    {
        if (isset(self::$fieldHandles[$name]) || isset(self::$generatedFieldHandles[$name])) {
            if (method_exists($this->owner, 'setCustomFieldRawValue')) {
                if (isset(self::$generatedFieldHandles[$name])) {
                    $this->owner->setGeneratedFieldRawValue($name, $value);
                } else {
                    $this->owner->setCustomFieldRawValue($name, $value);
                }

                return;
            }
            $this->_customFieldValues[$name] = $value;

            return;
        }
        parent::__set($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function canGetProperty($name, $checkVars = true): bool
    {
        if ($checkVars && (isset(self::$fieldHandles[$name]) || isset(self::$generatedFieldHandles[$name]))) {
            return true;
        }

        return parent::canGetProperty($name, $checkVars);
    }

    /**
     * {@inheritdoc}
     */
    public function canSetProperty($name, $checkVars = true): bool
    {
        if (!$this->canSetProperties) {
            return false;
        }
        if ($checkVars && (isset(self::$fieldHandles[$name]) || isset(self::$generatedFieldHandles[$name]))) {
            return true;
        }

        return parent::canSetProperty($name, $checkVars);
    }
}
