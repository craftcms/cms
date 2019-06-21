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
use craft\errors\GqlException;
use craft\gql\types\Asset as AssetGqlType;
use craft\gql\types\Entry as EntryGqlType;
use craft\gql\types\GlobalSet as GlobalSetGqlType;
use craft\gql\types\MatrixBlock as MatrixBlockGqlType;
use craft\gql\types\User as UserGqlType;
use craft\helpers\Json;
use crafttests\fixtures\AssetsFixture;
use crafttests\fixtures\EntryWithFieldsFixture;
use crafttests\fixtures\GlobalSetFixture;
use crafttests\fixtures\UsersFixture;
use GraphQL\Type\Definition\ResolveInfo;

class ElementFieldResolverTest extends Unit
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

    public function _fixtures()
    {
        return [
            'assets' => [
                'class' => AssetsFixture::class
            ],
            'entries' => [
                'class' => EntryWithFieldsFixture::class
            ],
            'globalSets' => [
                'class' => GlobalSetFixture::class
            ],
            'users' => [
                'class' => UsersFixture::class
            ],
        ];
    }

    // Tests
    // =========================================================================

    /**
     * Test resolving fields on entries.
     * @group gql
     *
     * @dataProvider entryFieldTestDataProvider
     *
     * @param string $gqlTypeClass The Gql type class
     * @param string $propertyName The propery being tested
     * @param mixed $result True for exact match, false for non-existing or a callback for fetching the data
     */
    public function testEntryFieldResolving(string $gqlTypeClass, string $propertyName, $result)
    {
        $this->_runTest(EntryElement::findOne(['title' => 'Theories of matrix']), $gqlTypeClass, $propertyName, $result);
    }

    /**
     * Test resolving fields on assets.
     * @group gql
     *
     * @dataProvider assetFieldTestDataProvider
     *
     * @param string $gqlTypeClass The Gql type class
     * @param string $propertyName The propery being tested
     * @param mixed $result True for exact match, false for non-existing or a callback for fetching the data
     */
    public function testAssetFieldResolving(string $gqlTypeClass, string $propertyName, $result)
    {
        $this->_runTest(AssetElement::findOne(['filename' => 'product.jpg']), $gqlTypeClass, $propertyName, $result);
    }

    /**
     * Test resolving fields on global sets.
     * @group gql
     *
     * @dataProvider globalSetFieldTestDataProvider
     *
     * @param string $gqlTypeClass The Gql type class
     * @param string $propertyName The propery being tested
     * @param mixed $result True for exact match, false for non-existing or a callback for fetching the data
     */
    public function testGlobalSetFieldResolving(string $gqlTypeClass, string $propertyName, $result)
    {
        $this->_runTest(GlobalSetElement::findOne(['handle' => 'aGlobalSet']), $gqlTypeClass, $propertyName, $result);
    }

    /**
     * Test resolving fields on matrix blocks.
     * @group gql
     *
     * @dataProvider matrixBlockFieldTestDataProvider
     *
     * @param string $gqlTypeClass The Gql type class
     * @param string $propertyName The propery being tested
     * @param mixed $result True for exact match, false for non-existing or a callback for fetching the data
     */
    public function testMatrixBlockFieldResolving(string $gqlTypeClass, string $propertyName, $result)
    {
        $field = Craft::$app->getFields()->getFieldByHandle('matrixFirst');
        $this->_runTest(MatrixBlockElement::findOne(['type' => 'aBlock', 'fieldId' => $field->id]), $gqlTypeClass, $propertyName, $result);
    }

    /**
     * Test resolving fields on users.
     * @group gql
     *
     * @dataProvider userFieldTestDataProvider
     *
     * @param string $gqlTypeClass The Gql type class
     * @param string $propertyName The propery being tested
     * @param mixed $result True for exact match, false for non-existing or a callback for fetching the data
     */
    public function testUserFieldResolving(string $gqlTypeClass, string $propertyName, $result)
    {
        $this->_runTest(UserElement::findOne(['username' => 'user1']), $gqlTypeClass, $propertyName, $result);
    }

    /**
     * Run the test on an element for a type class with the property name.
     * @group gql
     *
     * @param string $gqlTypeClass The Gql type class
     * @param string $propertyName The propery being tested
     * @param mixed $result True for exact match, false for non-existing or a callback for fetching the data
     */
    public function _runTest($element, string $gqlTypeClass, string $propertyName, $result)
    {
        $resolveInfo = $this->make(ResolveInfo::class, ['fieldName' => $propertyName]);
        $resolve = function () use ($gqlTypeClass, $element, $resolveInfo) { return $this->make($gqlTypeClass)->resolveWithDirectives($element, [], null, $resolveInfo);};

        if (is_callable($result)) {
            $this->assertEquals($result($element), $resolve());
        } else if ($result === true) {
            $this->assertEquals($element->$propertyName, $resolve());
            $this->assertNotNull($element->$propertyName);
        } else {
            $this->tester->expectException(GqlException::class, $resolve);
        }
    }
    
    // Data providers
    // =========================================================================

    public function entryFieldTestDataProvider(): array
    {
        return [
            // Entries
            [EntryGqlType::class, 'sectionUid', function ($source) { return $source->getSection()->uid;}],
            [EntryGqlType::class, 'typeUid', function ($source) { return $source->getType()->uid;}],
            [EntryGqlType::class, 'missingProperty', false],
            [EntryGqlType::class, 'typeInvalid', false],
            [EntryGqlType::class, 'plainTextField', true],
            [EntryGqlType::class, 'postDate', true],
        ];
    }

    public function assetFieldTestDataProvider(): array
    {
        return [
            [AssetGqlType::class, 'volumeUid', function ($source) { return $source->getVolume()->uid;}],
            [AssetGqlType::class, 'missingProperty', false],
            [AssetGqlType::class, 'folderUid', function ($source) { return $source->getFolder()->uid;}],
            [AssetGqlType::class, 'imageDescription', true],
            // TODO this test fails because the resolver looks for "andMass" property on the volume object.
            // The best clean way is to add all the structure entities as types and just nest the queries.
//            [AssetGqlType::class, 'volumeAndMass', true],
        ];
    }

    public function globalSetFieldTestDataProvider(): array
    {
        return [
            [GlobalSetGqlType::class, 'missingProperty', false],
            [GlobalSetGqlType::class, 'plainTextField', true],
            [GlobalSetGqlType::class, 'handle', true],
        ];
    }

    public function matrixBlockFieldTestDataProvider(): array
    {
        return [
            [MatrixBlockGqlType::class, 'missingProperty', false],
            [MatrixBlockGqlType::class, 'firstSubfield', true],
            [MatrixBlockGqlType::class, 'fieldId', true],
            [MatrixBlockGqlType::class, 'fieldInvalid', false],
            [MatrixBlockGqlType::class, 'fieldUid', function ($source) { return $source->getField()->uid;}],
            [MatrixBlockGqlType::class, 'ownerSiteId', function ($source) { return $source->getOwner()->getSite()->id;}],
            [MatrixBlockGqlType::class, 'ownerUid', function ($source) { return $source->getOwner()->uid;}],
            [MatrixBlockGqlType::class, 'typeUid', function ($source) { return $source->getType()->uid;}],
        ];
    }

    public function userFieldTestDataProvider(): array
    {
        return [
            [UserGqlType::class, 'missingProperty', false],
            [UserGqlType::class, 'shortBio', true],
            [UserGqlType::class, 'username', true],
            [UserGqlType::class, 'preferences', function ($source) { return Json::encode($source->preferences);}],
            // TODO figure out user groups and fixtures
            [UserGqlType::class, 'groupHandles', function ($source) {return array_map(function ($userGroup) { return $userGroup->handle;}, $source->getGroups());}],
        ];
    }
}
