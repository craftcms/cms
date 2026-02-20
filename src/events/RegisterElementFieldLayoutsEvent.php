<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use craft\models\FieldLayout;

/**
 * RegisterElementFieldLayoutsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class RegisterElementFieldLayoutsEvent extends Event
{
    /**
     * @var string|null The selected source’s key
     */
    public ?string $source = null;

    /**
     * @var FieldLayout[] List of all of the field layouts associated with elements from the given source
     */
    public array $fieldLayouts = [];
}
