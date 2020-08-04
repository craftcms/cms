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
use crafttests\fixtures\GqlSchemasFixture;
use crafttests\fixtures\UsersFixture;
use GraphQL\Type\Definition\ResolveInfo;

class TypeResolverTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $gqlService = Craft::$app->getGql();
        $schema = $gqlService->getSchemaById(1000);
        $gqlService->setActiveSchema($schema);
    }

    protected function _after()
    {
        Craft::$app->getGql()->flushCaches();
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
            ],
            'gqlSchemas' => [
                'class' => GqlSchemasFixture::class
            ],
        ];
    }

    /**
     * Test resolving a related element.
     **/
    public function testRunGqlResolveTest()
    {
        // Not using a data provider for this because of fixture load/unload on *every* iteration.
        $data = [
            // Assets
            [Asset::class, ['filename' => 'product.jpg'], AssetResolver::class],
            [Asset::class, ['folderId' => 1000], AssetResolver::class],
            [Asset::class, ['filename' => StringHelper::randomString(128)], AssetResolver::class],

            // Entries
            [Entry::class, ['title' => 'Theories of life'], EntryResolver::class],
            [Entry::class, ['title' => StringHelper::randomString(128)], EntryResolver::class],

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

            // Matrix Blocks
            [MatrixBlock::class, ['type' => 'aBlock'], MatrixBlockResolver::class],
            [MatrixBlock::class, ['site' => 'testSite1'], MatrixBlockResolver::class],
            [MatrixBlock::class, ['type' => 'MISSING'], MatrixBlockResolver::class],
        ];

        foreach ($data as $testData) {
            $this->_runResolverTest(... $testData);
        }
    }

    /**
     * Run the test.
     *
     * @param string $elementType The element class providing the elements
     * @param array $parameterSet Querying parameters to use
     * @param string $resolverClass The resolver class being tested
     * @param boolean $mustNotBeSame Whether the results should differ instead
     * @throws \Exception
     */
    public function _runResolverTest(string $elementType, array $params, string $resolverClass, bool $mustNotBeSame = false)
    {
        $elementQuery = Craft::configure($elementType::find(), $params);

        // Get the ids and elements.
        $ids = $elementQuery->ids();
        $elementResults = $elementQuery->all();

        $sourceElement = new ExampleElement();
        $sourceElement->someField = $elementType::find()->id($ids);

        $filterParameters = [];

        $resolveInfo = $this->make(ResolveInfo::class, ['fieldName' => 'someField']);

        $resolvedField = $resolverClass::resolve($sourceElement, $filterParameters, null, $resolveInfo);

        if ($mustNotBeSame) {
            $this->assertNotEquals($resolvedField, $elementResults);
        } else {
            $this->assertEquals($resolvedField, $elementResults);
        }
    }
}
