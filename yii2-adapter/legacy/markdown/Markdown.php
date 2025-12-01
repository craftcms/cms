<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\markdown;

use cebe\markdown\Markdown as BaseMarkdown;

/**
 * Markdown parser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.2
 */
class Markdown extends BaseMarkdown
{
    use SafeLinkTrait;
}
