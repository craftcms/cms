<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use craft\base\FsInterface;

/**
 * Filesystem event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class FsEvent extends Event
{
    /**
     * Constructor
     *
     * @param FsInterface $fs
     * @param array $config
     */
    public function __construct(FsInterface $fs, array $config = [])
    {
        $this->fs = $fs;
        parent::__construct($config);
    }

    /**
     * @var FsInterface The filesystem
     */
    public FsInterface $fs;
}
