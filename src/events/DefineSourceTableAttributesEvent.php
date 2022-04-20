<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use yii\base\Event;

/**
 * DefineSourceTableAttributesEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.5
 */
class DefineSourceTableAttributesEvent extends Event
{
    /**
     * @var string The element type class
     * @phpstan-var class-string<ElementInterface>
     */
    public string $elementType;

    /**
     * @var string The element source key
     */
    public string $source;

    /**
     * @var array The available columns that can be shown.
     *
     * This should be set to an array whose keys represent element attribute names, and whose values are
     * nested arrays with the following keys:
     *
     * - `label` – The table column header
     * - `icon` _(optional)_ – The name of the icon that should be shown instead of a textual label (e.g. `'world'`)
     *
     * The first item in the array will determine the first table column’s header (and which
     * [[\craft\base\ElementInterface::sortOptions()|sort option]] it should be mapped to, if any), however it
     * doesn’t have any effect on the table body, because the first column is reserved for displaying whatever
     * the elements’ [[\craft\base\ElementInterface::getUiLabel()|getUiLabel()]] methods return.
     */
    public array $attributes = [];
}
