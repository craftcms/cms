<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * RegisterElementDefaultCardAttributesEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.5.0
 * @deprecated 6.0.0 Use {@see \CraftCms\Cms\Element\Events\RegisterDefaultCardAttributes} instead.
 */
class RegisterElementDefaultCardAttributesEvent extends Event
{
    /**
     * @var string[] List of registered default card attributes for the element type.
     */
    public array $cardAttributes = [];
}
