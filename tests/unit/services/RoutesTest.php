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
    // Public Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var Routes
     */
    protected $routes;

    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================


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

    // Data Providers
    // =========================================================================

    /**
     * @return array
     */
    public function saveRouteDataProvider(): array
    {
        return [
            [
                [
                    'template' => '_test',
                    'uriPattern' => '',
                    'sortOrder' => 1,
                    'siteUid' => null
                ],
                [], '_test'
            ],
            [
                [
                    'template' => '_test',
                    'uriParts' => ['test1', 'test2'],
                    'uriPattern' => 'test1test2',
                    'sortOrder' => 1,
                    'siteUid' => null
                ],
                ['test1', 'test2'], '_test'
            ],
            [
                [
                    'template' => '_test',
                    'uriParts' => [['validHandle', 'date'], ['someHandle', 'slug']],
                    'uriPattern' => '<validHandle:date><someHandle:slug>',
                    'sortOrder' => 1,
                    'siteUid' => null
                ],
                [['validHandle', 'date'], ['someHandle', 'slug']], '_test'
            ],
            [
                [
                    'template' => '_test',
                    'uriParts' => [['validHandle', 'date'], ['!@#$%^&*(', 'validHandle'], ['validHandle', '!@#$%^&*(']],
                    'uriPattern' => '<validHandle:date><any:validHandle><validHandle2:!@#$%^&*(>',
                    'sortOrder' => 1,
                    'siteUid' => null
                ],
                [['validHandle', 'date'], ['!@#$%^&*(', 'validHandle'], ['validHandle', '!@#$%^&*(']], '_test'
            ],
            [
                [
                    'template' => '_test',
                    'uriParts' => [['validHandle', 'date', 'extraParamThatIsntUsed'], ['!@#$%^&*(', 'validHandle']],
                    'uriPattern' => '<validHandle:date><any:validHandle>',
                    'sortOrder' => 1,
                    'siteUid' => null
                ],
                [['validHandle', 'date', 'extraParamThatIsntUsed'], ['!@#$%^&*(', 'validHandle']], '_test'
            ],
            [
                [
                    'template' => '_test',
                    'uriParts' => [['validHandle', 'date'], 'noArray'],
                    'uriPattern' => '<validHandle:date>noArray',
                    'sortOrder' => 1,
                    'siteUid' => null
                ],
                [['validHandle', 'date'], 'noArray'], '_test'
            ],

            // TODO: Well more a question. Shouldn't emojis (UTF-8) be allowed in routes?
            [
                [
                    'template' => '_test',
                    'uriParts' => [['😎', 'date'], ['😎', 'emoji']],
                    'uriPattern' => '<any:date><any2:emoji>',
                    'sortOrder' => 1,
                    'siteUid' => null
                ],
                [['😎', 'date'], ['😎', 'emoji']], '_test'
            ],
        ];
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function _before()
    {
        parent::_before();
        $this->routes = Craft::$app->getRoutes();
    }
}
