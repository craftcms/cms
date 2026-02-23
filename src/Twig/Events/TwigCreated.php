<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Events;

use CraftCms\Cms\Twig\Environment;
use CraftCms\Cms\View\TemplateMode;

final readonly class TwigCreated
{
    public function __construct(
        public Environment $twig,
        public TemplateMode $templateMode,
    ) {}
}
