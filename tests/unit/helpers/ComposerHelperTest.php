<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use Craft;
use craft\helpers\Composer;
use craft\helpers\FileHelper;
use craft\test\TestCase;
use yii\base\InvalidArgumentException;

/**
 * Unit tests for the Composer helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class ComposerHelperTest extends TestCase
{
    private const CRAFT_COMPOSER = '@craftcms/composer.json';

    public function testAutoloadConfigFromFile(): void
    {
        $autoload = Composer::autoloadConfigFromFile(self::CRAFT_COMPOSER);

        self::assertArrayHasKey('craft\\', $autoload);
        self::assertSame('src/', $autoload['craft\\']);

        $this->expectException(InvalidArgumentException::class);
        Composer::autoloadConfigFromFile('/nonexistent/composer.json');
    }

    /**
     * @dataProvider couldAutoloadDataProvider
     */
    public function testCouldAutoload(
        bool $expectedResult,
        ?array $expectedExistingRoot,
        string $dir,
        string $composerFile,
    ): void {
        self::assertSame($expectedResult, Composer::couldAutoload($dir, $composerFile, $existingRoot));
        self::assertSame($expectedExistingRoot, $existingRoot);
    }

    public function couldAutoloadDataProvider(): array
    {
        $expectedSrcPath = FileHelper::absolutePath(Craft::getAlias('@craft'), ds: '/');

        return [
            'src-root' => [true, ['craft\\', $expectedSrcPath], '@craft', self::CRAFT_COMPOSER],
            'src-subpath' => [true, ['craft\\', $expectedSrcPath], '@craft/foo/bar', self::CRAFT_COMPOSER],
            'new-root' => [true, null, '@craftcms/foo/bar', self::CRAFT_COMPOSER],
            'invalid-namespace' => [false, ['craft\\', $expectedSrcPath], '@craft/foo-bar', self::CRAFT_COMPOSER],
            'uncontained-dir' => [false, null, '/nonexistent/foo/bar', self::CRAFT_COMPOSER],
        ];
    }
}
