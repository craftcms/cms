<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\twig\nodevisitors;

/**
 * EventTagFinder adds “head”, “beginBody”, and “endBody” events to the template as it’s being compiled.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
abstract class BaseEventTagVisitor implements \Twig_NodeVisitorInterface
{
    // Static
    // =========================================================================

    /**
     * @var boolean Whether the head() tag has been found/added
     */
    protected static $foundHead = false;

    /**
     * @var boolean Whether the beginBody() tag has been found/added
     */
    protected static $foundBeginBody = false;

    /**
     * @var boolean Whether the endBody() tag has been found/added
     */
    protected static $foundEndBody = false;

    /**
     * Returns whether all event tags have been found/added.
     *
     * @return boolean
     */
    protected static function foundAllEventTags()
    {
        return (
            static::$foundHead === true &&
            static::$foundBeginBody === true &&
            static::$foundEndBody === true
        );
    }
}
