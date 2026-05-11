<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use CraftCms\Cms\FieldLayout\FieldLayout;
use yii\base\Event;

/**
 * RegisterElementCardAttributesEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.5.0
 * @deprecated 6.0.0 Use {@see \CraftCms\Cms\Element\Events\ElementCardAttributesResolving} instead.
 */
class RegisterElementCardAttributesEvent extends Event
{
    /**
     * @var array List of registered card attributes for the element type.
     */
    public array $cardAttributes = [];

    /**
     * @var \CraftCms\Cms\FieldLayout\FieldLayout|null The field layout associated with the card designer
     * @since 5.9.0
     */
    public ?FieldLayout $fieldLayout = null;
}
