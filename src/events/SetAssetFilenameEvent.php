<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * SetAssetFilenameEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SetAssetFilenameEvent extends Event
{
    /**
     * @var string|null The asset filename (sans extension).
     */
    public $filename;

    /**
     * @var string|null The asset filename prior to sanitation (sans extension).
     */
    public $originalFilename;

    /**
     * @var string|null The asset extension
     */
    public $extension;
}
