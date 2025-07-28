<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\utilities;

use craft\base\Utility;

/**
 * Queue manager is a utility used for managing jobs in the Queue.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.4.0
 * @deprecated in 6.0.0. [[\CraftCms\Cms\Utility\Utilities\QueueManager]] should be used instead.
 */
class QueueManager extends Utility
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return \CraftCms\Cms\Utility\Utilities\QueueManager::displayName();
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return \CraftCms\Cms\Utility\Utilities\QueueManager::id();
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return \CraftCms\Cms\Utility\Utilities\QueueManager::icon();
    }

    /**
     * @inheritdoc
     */
    public static function toolbarHtml(): string
    {
        return \CraftCms\Cms\Utility\Utilities\QueueManager::toolbarHtml();
    }

    /**
     * @inheritdoc
     */
    public static function footerHtml(): string
    {
        return \CraftCms\Cms\Utility\Utilities\QueueManager::footerHtml();
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        return \CraftCms\Cms\Utility\Utilities\QueueManager::contentHtml();
    }
}
