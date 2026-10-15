<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Contracts;

use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use Illuminate\Support\HtmlString;

/**
 * Renderable defines the common interface to be implemented by objects that
 * can be rendered as HTML, such as with the `{% render %}` Twig tag.
 *
 * @since 6.0.0
 */
interface Renderable
{
    /**
     * Renders the object.
     *
     * @param  array<string,mixed>  $variables  Variables that should be made available to the rendered template
     */
    #[AllowedInSandbox]
    public function render(array $variables = []): HtmlString;
}
