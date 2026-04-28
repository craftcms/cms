<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * RegisterTemplateRootsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\View\Events\RegisterCpTemplateRoots} or {@see \CraftCms\Cms\View\Events\RegisterSiteTemplateRoots} instead.
 */
class RegisterTemplateRootsEvent extends Event
{
    /**
     * @var array The registered template roots. Each key should be a root template path, and values should be the
     * corresponding directory path, or an array of directory paths.
     */
    public array $roots = [];
}
