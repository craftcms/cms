<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\web\twig\AllowedInSandbox;
use Twig\Markup;

/**
 * Renderable defines the common interface to be implemented by objects that can be rendered as HTML.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
interface Renderable
{
    /**
     * Renders the object.
     *
     * @param array $variables Variables that should be made available to the rendered template
     * @return Markup
     */
    #[AllowedInSandbox]
    public function render(array $variables = []): Markup;
}
