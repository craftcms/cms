<?php

declare(strict_types=1);

namespace CraftCms\Cms\Markdown\Flavors;

use CraftCms\Cms\Markdown\MarkdownOptions;
use League\CommonMark\Environment\Environment;

abstract class Flavor
{
    protected function environment(MarkdownOptions $options, string $softBreak = "\n"): Environment
    {
        $config = [];

        if (! $options->allowUnsafeLinks) {
            $config['allow_unsafe_links'] = false;
        }

        if ($softBreak !== "\n") {
            $config['renderer'] = [
                'soft_break' => $softBreak,
            ];
        }

        return new Environment($config);
    }
}
