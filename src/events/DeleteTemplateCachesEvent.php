<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * Delete Template Caches event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.2
 */
class DeleteTemplateCachesEvent extends Event
{
    /**
     * @var int[] Array of template cache IDs that are associated with this event
     */
    public array $cacheIds;
}
