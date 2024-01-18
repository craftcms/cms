<?php

namespace craft\web\twig;

use Craft;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ComponentExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'component',
                [$this, 'render'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    public function render(string $name, array $params = []): string
    {
        return Craft::$app->getUi()->renderComponent($name, $params);
    }
}
