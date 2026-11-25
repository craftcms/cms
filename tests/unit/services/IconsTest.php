<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\fields\data\IconData;
use craft\fields\Icon;
use craft\helpers\Cp;
use craft\helpers\FileHelper;
use craft\services\Icons;
use craft\test\TestCase;

/**
 * Unit tests for the icons service and custom icon set support.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class IconsTest extends TestCase
{
    private string $iconDir;

    protected function _before(): void
    {
        parent::_before();

        $this->iconDir = Craft::$app->getPath()->getTempPath() . '/custom-icons-' . uniqid();
        FileHelper::createDirectory($this->iconDir);
        file_put_contents("$this->iconDir/Arrow Thing.svg", '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><title>Arrow</title><path id="arrow-path" d="M0 0h10v10z"/></svg>');
        file_put_contents("$this->iconDir/not-an-icon.txt", 'nope');

        Craft::$app->getConfig()->getGeneral()->customIconPath = $this->iconDir;
        Craft::$app->set('icons', new Icons());
    }

    protected function _after(): void
    {
        Craft::$app->getConfig()->getGeneral()->customIconPath = null;
        Craft::$app->set('icons', new Icons());
        FileHelper::removeDirectory($this->iconDir);

        parent::_after();
    }

    /**
     * @dataProvider parseDataProvider
     */
    public function testParse(?array $expected, string $value): void
    {
        self::assertSame($expected, Icons::parse($value));
    }

    public static function parseDataProvider(): array
    {
        return [
            [['custom', 'Arrow Thing'], 'custom:Arrow Thing'],
            [['my-set', 'arrow'], 'my-set:arrow'],
            [null, 'chevron-right'],
            [null, 'C:\\icons\\arrow.svg'],
            [null, 'C:/icons/arrow.svg'],
            [null, '@config/icons/arrow.svg'],
            [null, '<svg viewBox="0:0"></svg>'],
            [null, '1set:arrow'],
        ];
    }

    public function testCustomIconPath(): void
    {
        $iconSet = Craft::$app->getIcons()->getIconSetByHandle(Icons::CUSTOM);
        self::assertNotNull($iconSet);
        self::assertSame(['Arrow Thing'], $iconSet->getIcons());
        self::assertSame([
            Icons::FONT_AWESOME => 'Font Awesome',
            Icons::CUSTOM => 'Custom',
        ], Craft::$app->getIcons()->getIconSetNames());

        $svg = Cp::iconSvg('custom:Arrow Thing');
        self::assertStringContainsString('d="M0 0h10v10z"', $svg);
        self::assertStringNotContainsString('<title>', $svg);
        // IDs get namespaced, so they don't conflict when several custom icons are shown at once
        self::assertStringNotContainsString('id="arrow-path"', $svg);

        self::assertSame('', Cp::iconSvg('custom:missing'));
        self::assertSame('', Cp::iconSvg('unknown-set:Arrow Thing'));
    }

    public function testIconFieldValues(): void
    {
        $field = new Icon();

        $value = $field->normalizeValue('custom:Arrow Thing', null);
        self::assertInstanceOf(IconData::class, $value);
        self::assertSame('Arrow Thing', $value->name);
        self::assertSame('custom', $value->set);
        self::assertSame([], $value->styles);
        self::assertSame('custom:Arrow Thing', $field->serializeValue($value, null));

        $value = $field->normalizeValue('chevron-right', null);
        self::assertSame('chevron-right', $value->name);
        self::assertNull($value->set);
        self::assertContains('solid', $value->styles);
        self::assertSame('chevron-right', $field->serializeValue($value, null));
    }

    public function testIconDataSvgAndKeywords(): void
    {
        $value = new IconData('Arrow Thing', [], 'custom');
        self::assertStringContainsString('d="M0 0h10v10z"', $value->getSvg());
        self::assertSame([], $value->getKeywords());

        self::assertNull((new IconData('missing', [], 'custom'))->getSvg());
        self::assertNull((new IconData('Arrow Thing', [], 'unknown-set'))->getSvg());

        $value = new IconData('0', ['solid']);
        self::assertSame(['digit', 'zero', 'nada', 'none', 'zero', 'zilch'], $value->getKeywords());
    }

    public function testIconFieldIconSetsValidation(): void
    {
        $field = new Icon();
        self::assertSame([Icons::FONT_AWESOME], $field->iconSets);
        self::assertTrue($field->validate(['iconSets']));

        $field = new Icon(['iconSets' => [Icons::CUSTOM]]);
        self::assertTrue($field->validate(['iconSets']));

        // what an empty checkbox group posts
        $field = Craft::$app->getFields()->createField(['type' => Icon::class, 'iconSets' => '']);
        self::assertFalse($field->validate(['iconSets']));

        $field = new Icon(['iconSets' => ['nonexistent']]);
        self::assertFalse($field->validate(['iconSets']));
    }
}
