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
use craft\elements\db\ElementQuery;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\MatrixBlock as MatrixBlockElement;
use craft\elements\User;
use craft\gql\resolvers\elements\BaseElement as BaseResolver;
use craft\gql\resolvers\elements\Asset as AssetResolver;
use craft\gql\resolvers\elements\Entry as EntryResolver;
use craft\gql\resolvers\elements\GlobalSet as GlobalSetResolver;
use craft\gql\resolvers\elements\MatrixBlock as MatrixBlockResolver;
use craft\gql\resolvers\elements\User as UserResolver;
use craft\helpers\StringHelper;
use craft\test\mockclasses\elements\ExampleElement;
use craftunit\fixtures\AssetsFixture;
use craftunit\fixtures\EntryFixture;
use craftunit\fixtures\GlobalSetFixture;
use craftunit\fixtures\UsersFixture;
use GraphQL\Type\Definition\ResolveInfo;

class FieldResolverTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        Craft::$app->getGql()->flushCaches();
    }

    protected function _after()
    {
    }

    public function _fixtures()
    {
        return [
            'entries' => [
                'class' => EntryFixture::class
            ],
            'assets' => [
                'class' => AssetsFixture::class
            ],
            'users' => [
                'class' => UsersFixture::class
            ],
            'globalSets' => [
                'class' => GlobalSetFixture::class
            ]
        ];
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

    /**
     * Test resolving a related element.
     *
     * @dataProvider resolverDataProvider
     *
     * @param string $elementType The element class providing the elements
     * @param array $parameterSet Querying parameters to use
     * @param string $resolverClass The resolver class being tested
     * @param boolean $mustNotBeSame Whether the results should differ instead
     */
    public function testRunGraphQlResolveTest(string $elementType, array $params, string $resolverClass, bool $mustNotBeSame = false)
    {
        $elementQuery = Craft::configure($elementType::find(), $params);

        // Get the ids and elements.
        $ids = $elementQuery->ids();
        $elementResults = $elementQuery->all();

        $sourceElement = new ExampleElement();
        $sourceElement->relatedElements = $elementType::find()->id($ids);

        $filterParameters = [];

        // If we have more than two results, pick one at random
        if (count($elementResults) > 2) {
            $randomEntry = $elementResults[array_rand($elementResults, 1)];
            $targetId = $randomEntry->id;
            $filterParameters = ['id' => $targetId];
            $elementResults = $elementType::find()->id($targetId)->all();
        }

        $resolveInfo = $this->make(ResolveInfo::class, ['fieldName' => 'relatedElements']);

        $resolvedField = $resolverClass::resolve($sourceElement, $filterParameters, null, $resolveInfo);

        if ($mustNotBeSame) {
            $this->assertNotEquals($resolvedField, $elementResults);
        } else {
            $this->assertEquals($resolvedField, $elementResults);
        }
    }

    // Todo
    // Matrix Blocks

    // Data Providers
    // =========================================================================

    public function arrayableDataProvider()
    {
        return [
            [['siteId' => '8, 12, 44'], ['siteId' => [8,12,44]], true],
            [['siteId' => '8, 12, 44'], ['siteId' => ['8','12','44']], true],
            [['siteId' => 'longstring'], ['siteId' => ['longstring']], false],
            [['siteId' => 'longstring'], ['siteId' => 'longstring'], true],
        ];
    }

    public function resolverDataProvider()
    {
        return [
            // Assets
            [Asset::class, ['filename' => 'product.jpg'], AssetResolver::class],
            [Asset::class, ['folderId' => 1000], AssetResolver::class],
            [Asset::class, ['folderId' => 1], AssetResolver::class],
            [Asset::class, ['filename' => StringHelper::randomString(128)], AssetResolver::class],

            // Entries
            [Entry::class, ['title' => 'Theories of life'], EntryResolver::class],
            [Entry::class, ['title' => StringHelper::randomString(128)], EntryResolver::class],
            [Entry::class, ['authorId' => [1]], EntryResolver::class],

            // Globals
            [GlobalSet::class, ['handle' => 'aGlobalSet'], GlobalSetResolver::class, true],
            [GlobalSet::class, ['handle' => ['aGlobalSet', 'aDifferentGlobalSet']], GlobalSetResolver::class, true],
            [GlobalSet::class, ['handle' => 'aDeletedGlobalSet'], GlobalSetResolver::class, true],
            [GlobalSet::class, ['handle' => StringHelper::randomString(128)], GlobalSetResolver::class, true],

            // Users
            [User::class, ['username' => 'user1'], UserResolver::class],
            [User::class, ['username' => ['user1', 'admin']], UserResolver::class],
            [User::class, ['username' => ['user1', 'admin', 'user2', 'user3']], UserResolver::class],
            [User::class, ['username' => StringHelper::randomString(128)], UserResolver::class],
        ];
    }
}