<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use Throwable;

/**
 * Multi element action event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class MultiElementActionEvent extends ElementQueryEvent
{
    /**
     * @var ElementInterface The element being processed
     */
    public ElementInterface $element;

    /**
     * @var int The element’s position in the query (1-indexed)
     */
    public int $position;

    /**
     * @var Throwable|null The exception that was thrown if any
     */
    public ?Throwable $exception = null;
}
