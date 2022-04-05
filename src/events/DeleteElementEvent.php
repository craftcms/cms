<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

/**
 * Delete element event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.0
 */
class DeleteElementEvent extends ElementEvent
{
    /**
     * @var bool Whether to immediately hard-delete the element, rather than soft-deleting it
     */
    public $hardDelete = false;
}
