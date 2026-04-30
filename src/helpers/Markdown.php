<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use cebe\markdown\Parser;
use yii\helpers\Markdown as YiiMarkdown;

/**
 * Class MailerHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.10.0
 */
class Markdown extends YiiMarkdown
{
    protected static function getParser($flavor): Parser
    {
        $parser = parent::getParser($flavor);

        if (property_exists($parser, 'keepListStartNumber')) {
            $parser->keepListStartNumber = true;
        }

        return $parser;
    }
}
