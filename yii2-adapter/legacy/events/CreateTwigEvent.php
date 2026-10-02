<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use CraftCms\Cms\Twig\Environment;

/**
 * CreateTwigEvent event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Twig\Events\TwigCreated} instead.
 */
class CreateTwigEvent extends Event
{
    /**
     * @var string The template mode Twig is being created for (`site` or `cp`)
     */
    public string $templateMode;

    /**
     * @var Environment The Twig environment
     */
    public Environment $twig;
}
