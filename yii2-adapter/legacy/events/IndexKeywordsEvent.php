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
 * Index keywords event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.2.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Search\Events\KeywordsIndexing} instead.
 */
class IndexKeywordsEvent extends CancelableEvent
{
    /**
     * @var ElementInterface The element being indexed
     */
    public ElementInterface $element;

    /**
     * @var string|null The attribute name being indexed, or `null` if this is for a custom field
     */
    public ?string $attribute = null;

    /**
     * @var int|null The field ID being indexed, or `null` if this is for an attribute
     */
    public ?int $fieldId = null;

    /**
     * @var string Space-separated list of keywords to be indexed
     */
    public string $keywords;
}
