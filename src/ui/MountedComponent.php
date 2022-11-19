<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\ui;

use yii\base\BaseObject;

class MountedComponent extends BaseObject
{
    /**
     * @var string Name of the component
     */
    public string $name;

    /**
     * @var object Basic component object
     */
    public object $component;

    /**
     * @var ComponentAttributes HTML attributes to be passed to the component.
     */
    public ComponentAttributes $attributes;
}
