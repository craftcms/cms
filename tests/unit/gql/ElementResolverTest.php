<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql;

use Codeception\Test\Unit;
use Craft;
use craft\elements\Asset;
use craft\elements\db\AssetQuery;
use craft\gql\resolvers\elements\Asset as AssetResolver;
use craft\helpers\StringHelper;
use craft\models\GqlSchema;
use GraphQL\Type\Definition\ResolveInfo;

class ElementResolverTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        // Mock the GQL schema for the volumes below
        $this->tester->mockMethods(
            Craft::$app,
            'gql',
            [
                'getActiveSchema' => $this->make(GqlSchema::class, [
                    'scope' => [
                        'volumes.someUid:read',
                    ]
                ])
            ]
        );

    }

    protected function _after()
    {
    }

    /**
     * Test different query resolvers
     */
    public function testResolveOneAndCount()
    {
        $testUid = StringHelper::UUID();
        $testCount = random_int(1, 1000);

        // Mock the fetched Asset query
        $assetQuery = $this->make(AssetQuery::class, [
            'one' => new Asset(['uid' => $testUid]),
            'count' => $testCount
        ]);

        // The only way we can mock with a provided element query (to avoid using slow fixtures or DB data), is to provide
        // an imagined source, where the requested property already contains the element query.
        // This simulates a relational field scenario, where fields are element queries already.
        // One slight caveat, though - in real life usages resolveOnce will only be called on null source, but it's impossible
        /// to test that scenario, because static methods are impossible/very hard to test. ¯\_(ツ)_/¯
        $source = (object)['url' => $assetQuery];
        $resolveInfo = $this->make(ResolveInfo::class, ['fieldName' => 'url']);

        $this->assertSame($testUid, AssetResolver::resolveOne($source, [], null, $resolveInfo)->uid);
        $this->assertSame($testCount, AssetResolver::resolveCount($source, [], null, $resolveInfo));
    }
}
