<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\mail;

use craft\mail\transportadapters\Sendmail;
use craft\test\TestCase;

/**
 * Unit tests for the Sendmail transport adapter’s command validation.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.1
 */
class SendmailTest extends TestCase
{
    /**
     * @dataProvider commandDataProvider
     */
    public function testCommandValidation(string $command, bool $expected): void
    {
        $adapter = new Sendmail(['command' => $command]);
        self::assertSame($expected, $adapter->validate());
    }

    public static function commandDataProvider(): array
    {
        return [
            'default command' => [Sendmail::DEFAULT_COMMAND, true],
            'env var' => ['$PATH', true],
            'braced env var' => ['${PATH}', true],
            'unlisted command' => ['/usr/sbin/sendmail -bs; id', false],
            'braced env var with a suffix' => ['${PATH}; id', false],
            'env var in a path' => ['/$PATH/x; id', false],
            'alias' => ['@root/x; id', false],
        ];
    }
}
