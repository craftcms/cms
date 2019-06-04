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
     */
    public function testArrayableParametersSplitByComma()
    {
        $arguments = BaseResolver::getArrayableArguments();
        $testArray = [];
        $expectedResults = [];

        foreach ($arguments as $argument) {
            $values = range(0, 3);
            $testArray[$argument] = implode(', ', $values);
            $expectedResults[$argument] = $values;
        }

        foreach (BaseResolver::prepareArguments($testArray) as $argument => $values) {
            $this->assertEquals($values, $expectedResults[$argument]);
        }
    }

    /**
     * Test an arrayable string is not converted to array if it's a single element
     */
    public function testArrayableParametersDontSplitIfSingleElement()
    {
        $arguments = BaseResolver::getArrayableArguments();
        $testArray = [];
        $expectedResults = [];

        foreach ($arguments as $argument) {
            $testArray[$argument] = $expectedResults[$argument] = '*';
        }

        foreach (BaseResolver::prepareArguments($testArray) as $argument => $values) {
            $this->assertEquals($values, $expectedResults[$argument]);
        }
    }

    /**
     * Test resolving a related entry.
     */
    public function testEntryFieldResolving()
    {
        $sourceElement = new ExampleElement();

        $entryTitle = 'Theories of life';
        $fieldName = 'relatedElements';
        $elementQuery = EntryElement::find()->title($entryTitle);
        $relatedEntry = clone $elementQuery;
        $relatedEntry = $relatedEntry->one();

        $sourceElement->$fieldName = EntryElement::find()->id($relatedEntry->id);
        $resolveInfo = $this->make(ResolveInfo::class, ['fieldName' => $fieldName]);

        $resolvedField = EntryResolver::resolve($sourceElement, [], null, $resolveInfo);

        $this->assertEquals($resolvedField, $elementQuery->all());
    }

    /**
     * Test resolving a related entry.
     */
    public function testAssetFieldResolving()
    {
        $sourceElement = new ExampleElement();

        $assetFilename = 'product.jpg';
        $folderId = 1000;
        $assetQuery = AssetElement::find()->filename($assetFilename)->folderId($folderId);
        $relatedAsset = clone $assetQuery;
        $relatedAsset = $relatedAsset->one();

        $fieldName = 'relatedElements';
        $sourceElement->$fieldName = AssetElement::find()->id($relatedAsset->id);
        $resolveInfo = $this->make(ResolveInfo::class, ['fieldName' => $fieldName]);

        $resolvedField = AssetResolver::resolve($sourceElement, [], null, $resolveInfo);

        $this->assertEquals($resolvedField, $assetQuery->all());
    }

    /**
     * Test resolving a related user.
     */
    public function testUserResolving()
    {
        $sourceElement = new ExampleElement();

        $username = 'user1';
        $userQuery = UserElement::find()->username($username);
        $relatedUser = clone $userQuery;
        $relatedUser = $relatedUser->one();

        $fieldName = 'relatedElements';
        $sourceElement->$fieldName = UserElement::find()->id($relatedUser->id);
        $resolveInfo = $this->make(ResolveInfo::class, ['fieldName' => $fieldName]);

        $resolvedField = UserResolver::resolve($sourceElement, [], null, $resolveInfo);

        $this->assertEquals($resolvedField, $userQuery->all());
    }

    /**
     * Test resolving a global set.
     */
    public function testGlobalSetResolving()
    {
        $sourceElement = new ExampleElement();

        $handle = 'aGlobalSet';
        $globalSetQuery = GlobalSetElement::find()->handle($handle);
        $relatedSet = clone $globalSetQuery;
        $relatedSet = $relatedSet->one();

        $fieldName = 'relatedElements';
        $sourceElement->$fieldName = GlobalSetElement::find()->id($relatedSet->id);
        $resolveInfo = $this->make(ResolveInfo::class, ['fieldName' => $fieldName]);

        $resolvedField = GlobalSetResolver::resolve($sourceElement, [], null, $resolveInfo);

        // Global sets can't be used in relational fields, so these must not be equal
        $this->assertNotEquals($resolvedField, $globalSetQuery->all());
    }

    // Todo
    // Matrix Blocks
}