<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Element;
use craft\base\Event;

/**
 * Render element class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.8.0
 */
class RenderElementEvent extends Event
{
    /**
     * @var array{template:string,priority:int}[] The template paths to check when rendering the element’s partial template
     */
    public array $templates;

    /**
     * @var array Additional variables to be passed to the template
     */
    public array $variables;

    /**
     * @var string The output of the event
     */
    public string $output;
}
