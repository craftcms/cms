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
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\MatrixBlock;
use craft\elements\User;
use craft\gql\resolvers\elements\BaseElement as BaseResolver;
use craft\gql\resolvers\elements\Asset as AssetResolver;
use craft\gql\resolvers\elements\Entry as EntryResolver;
use craft\gql\resolvers\elements\GlobalSet as GlobalSetResolver;
use craft\gql\resolvers\elements\MatrixBlock as MatrixBlockResolver;
use craft\gql\resolvers\elements\User as UserResolver;
use craft\helpers\StringHelper;
use craft\test\mockclasses\elements\ExampleElement;
use crafttests\fixtures\AssetsFixture;
use crafttests\fixtures\EntryFixture;
use crafttests\fixtures\GlobalSetFixture;
use crafttests\fixtures\GqlTokensFixture;
use crafttests\fixtures\UsersFixture;
use GraphQL\Type\Definition\ResolveInfo;

class ArrayableParameterTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // Tests
    // =========================================================================

    /**
     * Test an arrayable string is split by comma
     *
     * @dataProvider arrayableDataProvider
     */
    public function testArrayableParameters($in, $out, $result)
    {
        if ($result) {
            $this->assertEquals(BaseResolver::prepareArguments($in), $out);
        } else {
            $this->assertNotEquals(BaseResolver::prepareArguments($in), $out);
        }
    }


    // Data Providers
    // =========================================================================

    public function arrayableDataProvider()
    {
        return [
            [['siteId' => '8, 12, 44'], ['siteId' => [8,12,44]], true],
            [['siteId' => '8, 12, 44'], ['siteId' => ['8','12','44']], true],
            [['siteId' => 'longstring'], ['siteId' => ['longstring']], false],
            [['siteId' => 'longstring'], ['siteId' => 'longstring'], true],
            [['siteId' => '*'], ['siteId' => '*'], true],
        ];
    }
}