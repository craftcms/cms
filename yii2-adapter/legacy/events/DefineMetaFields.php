<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * class DefineEntryMetaFields
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.9.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Entry\Events\DefineMetaFields} instead.
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
