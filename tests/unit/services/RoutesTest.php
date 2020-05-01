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
use craft\services\Routes;
use UnitTester;

/**
 * Unit tests for routes service.
 *
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class RoutesTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var Routes
     */
    protected $routes;


    /**
     * @dataProvider saveRouteDataProvider
     *
     * @param $result
     * @param array $uriParts
     * @param string $template
     * @param string|null $siteUid
     * @param string|null $routeUid
     */
    public function testSaveRoute($result, array $uriParts, string $template, string $siteUid = null, string $routeUid = null)
    {
        $routeUUID = $this->routes->saveRoute($uriParts, $template, $siteUid, $routeUid);

        $this->assertSame(
            $result,
            Craft::$app->getProjectConfig()->get(Routes::CONFIG_ROUTES_KEY . '.' . $routeUUID)
        );

        $this->assertTrue(StringHelper::isUUID($routeUUID));
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
                [], '_test'
            ],
            [
                [
                    'siteUid' => null,
                    'sortOrder' => 1,
                    'template' => '_test',
                    'uriParts' => ['test1', 'test2'],
                    'uriPattern' => 'test1test2',
                ],
                ['test1', 'test2'], '_test'
            ],
            [
                [
                    'siteUid' => null,
                    'sortOrder' => 1,
                    'template' => '_test',
                    'uriParts' => [['validHandle', 'date'], ['someHandle', 'slug']],
                    'uriPattern' => '<validHandle:date><someHandle:slug>',
                ],
                [['validHandle', 'date'], ['someHandle', 'slug']], '_test'
            ],
            [
                [
                    'siteUid' => null,
                    'sortOrder' => 1,
                    'template' => '_test',
                    'uriParts' => [['validHandle', 'date'], ['!@#$%^&*(', 'validHandle'], ['validHandle', '!@#$%^&*(']],
                    'uriPattern' => '<validHandle:date><any:validHandle><validHandle2:!@#$%^&*(>',
                ],
                [['validHandle', 'date'], ['!@#$%^&*(', 'validHandle'], ['validHandle', '!@#$%^&*(']], '_test'
            ],
            [
                [
                    'siteUid' => null,
                    'sortOrder' => 1,
                    'template' => '_test',
                    'uriParts' => [['validHandle', 'date', 'extraParamThatIsntUsed'], ['!@#$%^&*(', 'validHandle']],
                    'uriPattern' => '<validHandle:date><any:validHandle>',
                ],
                [['validHandle', 'date', 'extraParamThatIsntUsed'], ['!@#$%^&*(', 'validHandle']], '_test'
            ],
            [
                [
                    'siteUid' => null,
                    'sortOrder' => 1,
                    'template' => '_test',
                    'uriParts' => [['validHandle', 'date'], 'noArray'],
                    'uriPattern' => '<validHandle:date>noArray',
                ],
                [['validHandle', 'date'], 'noArray'], '_test'
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
                [['😎', 'date'], ['😎', 'emoji']], '_test'
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before()
    {
        parent::_before();
        $this->routes = Craft::$app->getRoutes();
    }
}
