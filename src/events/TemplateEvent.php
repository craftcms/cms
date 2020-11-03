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
 * @since 3.0.0
 */
class TemplateEvent extends CancelableEvent
{
    /**
     * @var string The name of the template being rendered
     */
    public $template;

    /**
     * @var array The variables to be passed to the template
     */
    public $variables;

    /**
     * @var string The template mode to be used
     * @since 3.4.0
     */
    public $templateMode;

    /**
     * @var string The rendering result of [[\craft\web\View::renderTemplate()]].
     *
     * Event handlers may modify this property and the modified output will be
     * returned by [[\craft\web\View::renderTemplate()]]. This property is only used
     * by [[\craft\web\View::EVENT_AFTER_RENDER_TEMPLATE]] event.
     */
    public $output;
}
