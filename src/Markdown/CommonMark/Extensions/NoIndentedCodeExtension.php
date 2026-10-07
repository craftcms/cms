<?php

declare(strict_types=1);

namespace CraftCms\Cms\Markdown\CommonMark\Extensions;

use CraftCms\Cms\Markdown\CommonMark\Parsers\NoIndentedCodeStartParser;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;

/**
 * Turns off indented code blocks, leaving fenced ones alone.
 *
 * For markup written as HTML rather than prose, where a four-space indent is
 * formatting. CommonMark ends an HTML block at the first blank line, so without
 * this the indented tags that follow one are escaped into a code block and the
 * markup they belonged to is left unclosed.
 *
 * @since 6.0.0
 */
class NoIndentedCodeExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addBlockStartParser(new NoIndentedCodeStartParser, -99);
    }
}
