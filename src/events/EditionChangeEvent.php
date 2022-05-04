<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * Edition Change event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class EditionChangeEvent extends Event
{
    /**
     * @var int The old edition
     */
    public int $oldEdition;

    /**
     * @var int The new edition
     */
    public int $newEdition;
}
