<?php

declare(strict_types=1);

namespace CraftCms\Cms\Markdown\Flavors;

use CraftCms\Cms\Markdown\CommonMark\Extensions\PreEncodedExtension;
use CraftCms\Cms\Markdown\MarkdownOptions;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\InlinesOnly\InlinesOnlyExtension;
use League\CommonMark\MarkdownConverter;

class CommonMarkFlavor extends Flavor
{
    public function __construct(
        private readonly string $softBreak = "\n",
        private readonly bool $preEncoded = false,
    ) {}

    public function __invoke(MarkdownOptions $options): MarkdownConverter
    {
        $environment = $this->environment($options, $this->softBreak);

        if ($options->inlineOnly) {
            $environment->addExtension(new InlinesOnlyExtension);
        } else {
            $environment->addExtension(new CommonMarkCoreExtension);
        }

        if ($this->preEncoded) {
            $environment->addExtension(new PreEncodedExtension);
        }

        return new MarkdownConverter($environment);
    }
}
