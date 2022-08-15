<?php
/**
 * @link http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license http://craftcms.com/license
 */

namespace craft\behaviors;

use yii\base\Behavior;

/**
 * This file is never loaded at runtime. It’s only here for PHPStan’n sake.
 *
 * @internal
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
     * @var string[] List of supported field handles.
     */
    public static $fieldHandles = [];
}
