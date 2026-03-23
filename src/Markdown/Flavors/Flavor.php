<?php

declare(strict_types=1);

namespace CraftCms\Cms\Markdown\Flavors;

use CraftCms\Cms\Markdown\MarkdownOptions;
use League\CommonMark\Environment\Environment;

abstract class Flavor
{
    protected function environment(MarkdownOptions $options, string $softBreak = "\n"): Environment
    {
        return new Environment([
            'html_input' => 'allow',
            'allow_unsafe_links' => $options->allowUnsafeLinks,
            'max_nesting_level' => PHP_INT_MAX,
            'max_delimiters_per_line' => PHP_INT_MAX,
            'renderer' => [
                'block_separator' => "\n",
                'inner_separator' => "\n",
                'soft_break' => $softBreak,
            ],
        ]);
    }
}
