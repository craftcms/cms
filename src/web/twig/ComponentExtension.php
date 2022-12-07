<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig;

use Craft;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ComponentExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('component', [$this, 'render'], ['is_safe' => ['all']]),
        ];
    }

    /**
     * Renders a Craft Twig component
     *
     * @param string $name Name of the component to render.
     * @param array $props Properties to pass into the component
     * @return string
     */
    public function render(string $name, array $props = []): string
    {
        return Craft::$app->getUi()->createAndRender($name, $props);
    }
}
