<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodevisitors;

use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * EventTagFinder adds “head”, “beginBody”, and “endBody” events to the template as it’s being compiled.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
abstract class BaseEventTagVisitor implements NodeVisitorInterface
{
    /**
     * @var bool Whether the head() tag has been found/added
     */
    protected static bool $foundHead = false;

    /**
     * @var bool Whether the beginBody() tag has been found/added
     */
    protected static bool $foundBeginBody = false;

    /**
     * @var bool Whether the endBody() tag has been found/added
     */
    protected static bool $foundEndBody = false;

    /**
     * Returns whether all event tags have been found/added.
     *
     * @return bool
     */
    protected static function foundAllEventTags(): bool
    {
        return (
            static::$foundHead === true &&
            static::$foundBeginBody === true &&
            static::$foundEndBody === true
        );
    }
}
