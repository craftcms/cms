<?php

declare(strict_types=1);

namespace CraftCms\Cms\Markdown\CommonMark\Parsers;

use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

/**
 * Claims indented lines before CommonMark can read them as a code block.
 *
 * Registered above `IndentedCodeStartParser` and matching its conditions, so an
 * indented line becomes text rather than code and nothing else changes.
 *
 * @since 6.0.0
 */
final class NoIndentedCodeStartParser implements BlockStartParserInterface
{
    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        if (
            ! $cursor->isIndented()
            || $cursor->isBlank()
            || $parserState->getActiveBlockParser()->getBlock() instanceof Paragraph
        ) {
            return BlockStart::none();
        }

        return BlockStart::abort();
    }
}
