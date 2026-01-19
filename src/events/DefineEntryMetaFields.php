<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;


use craft\elements\Entry;
use craft\base\Event;

/**
 * class DefineEntryMetaFields
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.8.18
 */

class DefineEntryMetaFields extends Event
{
    /**
     * @var Entry The current entry
     */
    public Entry $entry;

    /**
     * @var bool Whether the fields should be static (non-interactive)
     */
    public bool $static;

    /**
     * @var array array of all meta fields
     */
    public array $fields;
}
