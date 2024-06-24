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
 * @since 5.3.0
 */
class RegisterElementDefaultCardAttributesEvent extends Event
{
    /**
     * @var string The selected source’s key
     */
    public string $source;

    /**
     * @var string[] List of registered default card attributes for the element type.
     */
    public array $cardAttributes = [];
}
