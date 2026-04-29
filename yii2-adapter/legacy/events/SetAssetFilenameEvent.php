<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * SetAssetFilenameEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Events\SetAssetFilename} instead.
 */
class SetAssetFilenameEvent extends Event
{
    /**
     * @var string The asset filename (sans extension).
     */
    public string $filename;

    /**
     * @var string The asset filename prior to sanitation (sans extension).
     */
    public string $originalFilename;

    /**
     * @var string The asset extension
     */
    public string $extension;
}
