<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use craft\helpers\Markdown;
use craft\test\TestCase;

/**
 * Class NumberHelperTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.10.0
 */
class MarkdownHelperTest extends TestCase
{
    /**
     *
     */
    public function testListStartNumber(): void
    {
        $md = <<<MD
3. Three
4. Four
5. Five
MD;

        self::assertStringContainsString('<ol start="3">', Markdown::process($md));
    }
}
