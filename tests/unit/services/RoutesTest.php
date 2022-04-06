<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Codeception\Test\Unit;
use Craft;
use craft\helpers\StringHelper;
use craft\services\ProjectConfig;
use craft\services\Routes;
use craft\test\TestCase;

/**
 * Unit tests for routes service.
 *
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class RoutesTest extends TestCase
{
    /**
     * @var Routes
     */
    protected Routes $routes;

    /**
     * @dataProvider saveRouteDataProvider
     * @param array $expected
     * @param array $uriParts
     * @param string $template
     * @param string|null $siteUid
     * @param string|null $routeUid
     */
    public function testSaveRoute(array $expected, array $uriParts, string $template, ?string $siteUid = null, ?string $routeUid = null): void
    {
        $uid = $this->routes->saveRoute($uriParts, $template, $siteUid, $routeUid);
        self::assertTrue(StringHelper::isUUID($uid));
        self::assertSame($expected, Craft::$app->getProjectConfig()->get(ProjectConfig::PATH_ROUTES . '.' . $uid));
    }

    /**
     * @return array
     */
    public function saveRouteDataProvider(): array
    {
        return [
            [
                [
                    'siteUid' => null,
                    'sortOrder' => 1,
                    'template' => '_test',
                    'uriPattern' => '',
                ],
                [], '_test',
            ],
            [
                [
                    'siteUid' => null,
                    'sortOrder' => 1,
                    'template' => '_test',
                    'uriParts' => ['test1', 'test2'],
                    'uriPattern' => 'test1test2',
                ],
                ['test1', 'test2'], '_test',
            ],
            [
                [
                    'siteUid' => null,
                    'sortOrder' => 1,
                    'template' => '_test',
                    'uriParts' => [['validHandle', 'date'], ['someHandle', 'slug']],
                    'uriPattern' => '<validHandle:date><someHandle:slug>',
                ],
                [['validHandle', 'date'], ['someHandle', 'slug']], '_test',
            ],
            [
                [
                    'siteUid' => null,
                    'sortOrder' => 1,
                    'template' => '_test',
                    'uriParts' => [['validHandle', 'date'], ['!@#$%^&*(', 'validHandle'], ['validHandle', '!@#$%^&*(']],
                    'uriPattern' => '<validHandle:date><any:validHandle><validHandle2:!@#$%^&*(>',
                ],
                [['validHandle', 'date'], ['!@#$%^&*(', 'validHandle'], ['validHandle', '!@#$%^&*(']], '_test',
            ],
            [
                [
                    'siteUid' => null,
                    'sortOrder' => 1,
                    'template' => '_test',
                    'uriParts' => [['validHandle', 'date', 'extraParamThatIsntUsed'], ['!@#$%^&*(', 'validHandle']],
                    'uriPattern' => '<validHandle:date><any:validHandle>',
                ],
                [['validHandle', 'date', 'extraParamThatIsntUsed'], ['!@#$%^&*(', 'validHandle']], '_test',
            ],
            [
                [
                    'siteUid' => null,
                    'sortOrder' => 1,
                    'template' => '_test',
                    'uriParts' => [['validHandle', 'date'], 'noArray'],
                    'uriPattern' => '<validHandle:date>noArray',
                ],
                [['validHandle', 'date'], 'noArray'], '_test',
            ],

            // TODO: Well more a question. Shouldn't emojis (UTF-8) be allowed in routes?
            [
                [
                    'siteUid' => null,
                    'sortOrder' => 1,
                    'template' => '_test',
                    'uriParts' => [['😎', 'date'], ['😎', 'emoji']],
                    'uriPattern' => '<any:date><any2:emoji>',
                ],
                [['😎', 'date'], ['😎', 'emoji']], '_test',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        parent::_before();
        $this->routes = Craft::$app->getRoutes();
    }
}
