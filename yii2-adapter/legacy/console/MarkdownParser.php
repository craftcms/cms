<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console;

use yii\console\Markdown;
use yii\helpers\Console;

/**
 * Markdown parser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.5
 */
class MarkdownParser extends Markdown
{
    protected function renderCode($block)
    {
        $lines = preg_split('/[\r\n]/', $block['content']);
        $maxLength = max(array_map(fn(string $line) => strlen($line), $lines));
        return implode("\n", array_map(fn(string $line) => Console::ansiFormat(str_pad($line, $maxLength), [Console::NEGATIVE]), $lines)) . "\n\n";
    }

    protected function renderInlineCode($element)
    {
        return Console::ansiFormat($element[1], [Console::FG_CYAN]);
    }
}
