<?php

declare(strict_types=1);

namespace CraftCms\Cms\Markdown\Flavors;

use CraftCms\Cms\Markdown\MarkdownOptions;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\InlinesOnly\InlinesOnlyExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

class ExtraFlavor extends Flavor
{
    public function __invoke(MarkdownOptions $options): MarkdownConverter
    {
        $environment = $this->environment($options);

        if ($options->inlineOnly) {
            $environment->addExtension(new InlinesOnlyExtension);

            return new MarkdownConverter($environment);
        }

        $environment
            ->addExtension(new CommonMarkCoreExtension)
            ->addExtension(new TableExtension);

        return new MarkdownConverter($environment);
    }
}
