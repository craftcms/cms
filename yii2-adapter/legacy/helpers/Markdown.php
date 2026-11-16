<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use cebe\markdown\Parser;
use craft\markdown\BaseMarkdownParser;
use yii\helpers\Markdown as YiiMarkdown;

/**
 * Class MailerHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.10.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Markdown\Markdown} instead.
 */
class Markdown extends YiiMarkdown
{
    protected static function getParser($flavor): BaseMarkdownParser|Parser
    {
        /** @var BaseMarkdownParser|Parser $parser */
        $parser = parent::getParser($flavor);

        if (property_exists($parser, 'keepListStartNumber')) {
            $parser->keepListStartNumber = true;
        }

        return $parser;
    }
}
