<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use CraftCms\Cms\Dashboard\CustomWidgets;
use CraftCms\Cms\Dashboard\Data\CustomWidgetDefinition;
use CraftCms\Cms\Support\Facades\Markdown;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Twig\Twig;
use Override;

class Custom extends Widget
{
    public string $definitionId = '';

    public function __construct(
        private readonly CustomWidgets $customWidgets,
        private readonly Twig $twig,
        array|object $config = [],
    ) {
        parent::__construct($config);
    }

    #[Override]
    public function getType(): string
    {
        return $this->definition()?->type() ?? static::class;
    }

    #[Override]
    public function getDisplayName(): string
    {
        $definition = $this->definition();

        if (! $definition) {
            return static::displayName();
        }

        $label = $definition->label === null ? '' : trim($this->render($definition->label, false));

        return $label !== '' ? $label : Str::headline(pathinfo($definition->filename, PATHINFO_FILENAME));
    }

    #[Override]
    public function getIcon(): ?string
    {
        return $this->definition()?->icon;
    }

    #[Override]
    public function getMaxColspan(): ?int
    {
        return $this->definition()?->maxColspan;
    }

    #[Override]
    public function getTitle(): ?string
    {
        $definition = $this->definition();

        if (! $definition) {
            return null;
        }

        if ($definition->titleFromLabel) {
            return $this->getDisplayName();
        }

        return $definition->title === null ? null : trim($this->render($definition->title, false));
    }

    #[Override]
    public function getSubtitle(): ?string
    {
        $subtitle = $this->definition()?->subtitle;

        return $subtitle === null ? null : trim($this->render($subtitle, false));
    }

    #[Override]
    public function getBodyHtml(): ?string
    {
        $definition = $this->definition();

        if (! $definition) {
            return null;
        }

        return Markdown::parse($this->render($definition->body, 'html'));
    }

    private function definition(): ?CustomWidgetDefinition
    {
        return $this->customWidgets->find($this->definitionId);
    }

    private function render(string $template, string|false $escaper): string
    {
        $twig = $this->twig->get();
        $twig->setDefaultEscaperStrategy($escaper);

        try {
            return $twig->createTemplate($template, $this->definition()?->filename)->render();
        } finally {
            $twig->setDefaultEscaperStrategy();
        }
    }
}
