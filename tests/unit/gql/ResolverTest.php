<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql;

use Codeception\Test\Unit;
use Craft;
use craft\elements\Asset as AssetElement;
use craft\elements\db\ElementQuery;
use craft\elements\Entry as EntryElement;
use craft\elements\GlobalSet as GlobalSetElement;
use craft\elements\MatrixBlock as MatrixBlockElement;
use craft\elements\User as UserElement;
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

class ResolverTest extends Unit
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
     * @param array $parameterSet Querying parameters to use
     * @param callable $queryingCallback Alias for {ElementType}::find()
     * @param callable $resolverCallback Alias for {ResolverType}::resolve()
     * @param boolean $testInequality Whether inequality should be tested instead
     */
    public function testRelationshsipFieldResolving(array $parameterSet, callable $queryingCallback, callable $resolverCallback, bool $testInequality = false)
    {
        // Create the `find()` method.
        $elementQuery = $queryingCallback();
        /** @var ElementQuery $elementQuery */

        // Populate with the provided parameters
        $elementQuery = Craft::configure($elementQuery, $parameterSet);
        $ids = (clone $elementQuery)->ids();

        // Create a new element that has a relational field set on it
        $sourceElement = new ExampleElement();
        // And set an element query with pre-loaded ids on it
        $sourceElement->relatedElements = $queryingCallback()->id($ids);

        $elementResults = $elementQuery->all();
        // Populate the resolver info object and call the resolver function

        $filterParameters = [];

        // If we have more than two results, pick one at random
        if (count($elementResults) > 2) {
            $randomEntry = $elementResults[array_rand($elementResults, 1)];
            $targetId = $randomEntry->id;
            $filterParameters = ['id' => $targetId];
            $elementResults = $queryingCallback()->id($targetId)->all();
        }

        $resolveInfo = $this->make(ResolveInfo::class, ['fieldName' => 'relatedElements']);
        $resolvedField = $resolverCallback($sourceElement, $filterParameters, null, $resolveInfo);

        // Make sure that the resolver returns the expected result
        if ($testInequality) {
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
        $data = [];

        $parameters = [
            ['title' => 'Theories of life'],
            ['title' => StringHelper::randomString(128)],
            ['authorId' => [1]],
        ];

        foreach ($parameters as $parameterSet) {
            // Provide the query parameter set, callback to the element finder method and the resolver function we're testing
            $data[] = [$parameterSet, EntryElement::class . '::find', EntryResolver::class . '::resolve'];
        }

        $parameters = [
            ['filename' => 'product.jpg'],
            ['folderId' => 1000],
            ['folderId' => 1],
            ['filename' => StringHelper::randomString(128)]
        ];

        foreach ($parameters as $parameterSet) {
            $data[] = [$parameterSet, AssetElement::class . '::find', AssetResolver::class . '::resolve'];
        }

        $parameters = [
            ['username' => 'user1'],
            ['username' => ['user1', 'admin']],
            ['username' => ['user1', 'admin', 'user2', 'user3']],
            ['username' => StringHelper::randomString(128)],
        ];

        foreach ($parameters as $parameterSet) {
            $userData[] = [$parameterSet, UserElement::class . '::find', UserResolver::class . '::resolve'];
        }

        $parameters = [
            ['handle' => 'aGlobalSet'],
            ['handle' => ['aGlobalSet', 'aDifferentGlobalSet']],
            ['handle' => 'aDeletedGlobalSet'],
            ['handle' => StringHelper::randomString(128)],
        ];

        foreach ($parameters as $parameterSet) {
            // Test for inequality for global sets, because it's impossible to have global sets as relations and the resolver
            // must always return all the global sets
            $data[] = [$parameterSet, GlobalSetElement::class . '::find', GlobalSetResolver::class . '::resolve', true];
        }

        return $data;
    }
}