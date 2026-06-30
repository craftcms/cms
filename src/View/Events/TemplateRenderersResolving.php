<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Events;

use CraftCms\Cms\View\Contracts\TemplateRendererInterface;

class TemplateRenderersResolving
{
    public function __construct(
        /** @var class-string<TemplateRendererInterface>[] */
        public array $renderers,
    ) {}
}
