<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * ResolveResourcePathEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ResolveResourcePathEvent extends Event
{
    /**
     * @var string The resource URI (sans "cpresources/").
     */
    public string $uri;

    /**
     * @var string|null The file path that the URI should resolve to.
     */
    public ?string $path = null;
}
