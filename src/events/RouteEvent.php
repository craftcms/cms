<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * RouteEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class RouteEvent extends Event
{
    /**
     * @var array The URI as defined by the user. This is an array where each element is either a
     * string or an array containing the name of a subpattern and the subpattern.
     */
    public array $uriParts;

    /**
     * @var string The template to route matching requests to
     */
    public string $template;

    /**
     * @var string|null The site UID the route should be limited to, if any
     */
    public ?string $siteUid = null;
}
