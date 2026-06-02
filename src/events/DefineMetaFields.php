<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use craft\base\Event;

/**
 * class DefineEntryMetaFields
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.9.0
 */

class DefineMetaFields extends Event
{
    /**
     * @var ElementInterface The element the meta fields are for
     */
    public ElementInterface $element;

    /**
     * @var bool Whether the fields should be static (non-interactive)
     */
    public bool $static;

    /**
     * @var array The meta fields
     */
    public array $fields;
}
