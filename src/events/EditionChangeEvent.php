<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\events;

/**
 * Edition Change event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class EditionChangeEvent extends \yii\base\Event
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
