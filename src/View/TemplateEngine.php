<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

enum TemplateEngine: string
{
    case Blade = 'blade';
    case Twig = 'twig';
}
