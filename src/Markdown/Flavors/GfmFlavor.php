<?php

declare(strict_types=1);

namespace CraftCms\Cms\Markdown\Flavors;

use CraftCms\Cms\Markdown\CommonMark\Extensions\UserMentionExtension;
use CraftCms\Cms\Markdown\MarkdownOptions;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\InlinesOnly\InlinesOnlyExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\MarkdownConverter;

class GfmFlavor extends Flavor
{
    public function __construct(private readonly string $softBreak = "\n") {}

    public function __invoke(MarkdownOptions $options): MarkdownConverter
    {
        $environment = $this->environment($options, $this->softBreak);
        $environment->addExtension(new UserMentionExtension);

        if ($options->inlineOnly) {
            $environment
                ->addExtension(new InlinesOnlyExtension)
                ->addExtension(new StrikethroughExtension);

            return new MarkdownConverter($environment);
        }

        $environment->addExtension(new CommonMarkCoreExtension);

        $this->registerExtensions($environment);

        return new MarkdownConverter($environment);
    }

    private function registerExtensions(Environment $environment): void
    {
        $environment
            ->addExtension(new AutolinkExtension)
            ->addExtension(new StrikethroughExtension)
            ->addExtension(new TableExtension)
            ->addExtension(new TaskListExtension);
    }
}
