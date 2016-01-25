<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\events;

/**
 * Edition Change event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class EditionChangeEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var integer The old edition
     */
    public $oldEdition;

    /**
     * @var integer The new edition
     */
    public $newEdition;
}
