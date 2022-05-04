<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\web\View;

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
    public string $template;

    /**
     * @var array The variables to be passed to the template
     */
    public array $variables;

    /**
     * @var string The template mode to be used
     * @phpstan-var View::TEMPLATE_MODE_CP|View::TEMPLATE_MODE_SITE
     * @since 3.4.0
     */
    public string $templateMode;

    /**
     * @var string The rendering result of [[\craft\web\View::renderTemplate()]].
     *
     * Event handlers may modify this property and the modified output will be
     * returned by [[\craft\web\View::renderTemplate()]]. This property is only used
     * by [[\craft\web\View::EVENT_AFTER_RENDER_TEMPLATE]] event.
     */
    public string $output;
}
