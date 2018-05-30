<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

/**
 * TemplateEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class TemplateEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /**
     * @var string The name of the template being rendered
     */
    public $template;

    /**
     * @var array The variables that were passed to [[\craft\web\View::renderTemplate()]].
     */
    public $variables;

    /**
     * @var string The rendering result of [[\craft\web\View::renderTemplate()]].
     *
     * Event handlers may modify this property and the modified output will be
     * returned by [[\craft\web\View::renderTemplate()]]. This property is only used
     * by [[\craft\web\View::EVENT_AFTER_RENDER_TEMPLATE]] event.
     */
    public $output;
}
