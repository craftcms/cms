<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\FsInterface;
use yii\base\Event;

/**
 * FsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class FsEvent extends Event
{
    /**
     * @var FsInterface The filesystem associated with the event.
     */
    public FsInterface $fs;

    /**
     * @var bool Whether the filesystem is brand new
     */
    public bool $isNew = false;
}
