<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\HtmlPurifier;
use craft\test\TestCase;
use HTMLPurifier_Config;
use HTMLPurifier_HTMLDefinition;

/**
 * Class HtmlPurifierTest.
 *
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class HtmlPurifierTest extends TestCase
{
    /**
     * @dataProvider cleanUtf8DataProvider
     * @param string $expected
     * @param string $string
     */
    public function testCleanUtf8(string $expected, string $string): void
    {
        self::assertSame($expected, HtmlPurifier::cleanUtf8($string));
    }

    /**
     *
     */
    public function testConfigure(): void
    {
        $config = HTMLPurifier_Config::createDefault();
        HtmlPurifier::configure($config);
        self::assertNull($config->get('HTML.DefinitionID'));
        self::assertSame('', $config->get('Attr.DefaultImageAlt'));
        self::assertSame('', $config->get('Attr.DefaultInvalidImageAlt'));
    }

    public function testProcessCachesHtmlDefinition(): void
    {
        $cachePath = Craft::$app->getRuntimePath() . DIRECTORY_SEPARATOR . 'HTML';
        FileHelper::removeDirectory($cachePath);
        $html = '<figure><a href="https://example.com" download="file.pdf">Link</a><figcaption>Caption</figcaption></figure>';

        // Configs that disable caching are respected
        self::assertSame($html, HtmlPurifier::process($html, ['Cache.DefinitionImpl' => null]));
        self::assertDirectoryDoesNotExist($cachePath);

        // Run each config twice, so the second call is served from the definition cache
        for ($i = 0; $i < 2; $i++) {
            self::assertSame($html, HtmlPurifier::process($html));
            self::assertSame('<a href="https://example.com">Link</a>Caption', HtmlPurifier::process($html, ['HTML.Allowed' => 'a[href]']));
        }

        $cacheFiles = glob($cachePath . DIRECTORY_SEPARATOR . '*.ser');
        self::assertCount(2, $cacheFiles);
        self::assertCount(2, array_diff(scandir($cachePath), ['.', '..']), 'No temp files are left behind');

        // A cache file that's still being written is treated as a cache miss, and replaced
        foreach ($cacheFiles as $file) {
            $contents = file_get_contents($file);
            file_put_contents($file, substr($contents, 0, intdiv(strlen($contents), 2)));
        }
        self::assertSame($html, HtmlPurifier::process($html));
        self::assertSame('<a href="https://example.com">Link</a>Caption', HtmlPurifier::process($html, ['HTML.Allowed' => 'a[href]']));
        foreach ($cacheFiles as $file) {
            self::assertInstanceOf(HTMLPurifier_HTMLDefinition::class, unserialize(file_get_contents($file)));
        }
    }

    public function testProcessIgnoresCacheCleanupFailures(): void
    {
        $cachePath = Craft::$app->getRuntimePath() . DIRECTORY_SEPARATOR . 'HTML';
        FileHelper::removeDirectory($cachePath);
        // An old cache entry that can't be removed
        FileHelper::createDirectory($cachePath . DIRECTORY_SEPARATOR . '1.0.0,abc,1.ser');

        self::assertSame('<p><mark>Hi</mark></p>', HtmlPurifier::process('<p><mark>Hi</mark></p>'));
    }

    public function testProcessWithConfigObjectSettingDefinitionId(): void
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->autoFinalize = false;
        $config->set('HTML.DefinitionID', 'my-plugin');
        @$config->getHTMLDefinition(true)->addAttribute('p', 'data-foo', 'Text');

        self::assertSame('<p data-foo="bar">Hi</p>', HtmlPurifier::process('<p data-foo="bar">Hi</p>', $config));
    }

    public function testProcessWithSubclassCanEditHtmlDefinition(): void
    {
        $purifier = new class() extends HtmlPurifier {
            public static function configure($config): void
            {
                parent::configure($config);
                $config->getHTMLDefinition(true)->addAttribute('p', 'data-foo', 'Text');
            }
        };

        self::assertSame('<p data-foo="bar">Hi</p>', $purifier::process('<p data-foo="bar">Hi</p>'));
    }

    public function testProcessWithClosureCanEditHtmlDefinition(): void
    {
        for ($i = 0; $i < 2; $i++) {
            $output = HtmlPurifier::process('<p data-foo="bar">Hi</p>', function(HTMLPurifier_Config $config) {
                $config->getHTMLDefinition(true)->addAttribute('p', 'data-foo', 'Text');
            });
            self::assertSame('<p data-foo="bar">Hi</p>', $output);
        }
    }

    /**
     * @return array
     */
    public static function cleanUtf8DataProvider(): array
    {
        // https://github.com/ezyang/htmlpurifier/blob/master/tests/HTMLPurifier/EncoderTest.php#L21
        return [
            ['test', 'test'],
            ['null byte: ', "null byte: \0"],
            ['あ（い）う（え）お', "あ（い）う（え）お\0"],
            ['', "\1\2\3\4\5\6\7"],
            ['', "\x7F"],
            ['', "\xC2\x80"],
            ['', "\xDF\xFF"],
            ["\xF3\xBF\xBF\xBF", "\xF3\xBF\xBF\xBF"],
            ['', "\xED\xB0\x80"],
            ['😀😘', '😀😘'],
        ];
    }
}
