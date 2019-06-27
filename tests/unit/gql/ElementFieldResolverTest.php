<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql;

use Codeception\Test\Unit;
use Craft;
use craft\base\Volume;
use craft\elements\Asset as AssetElement;
use craft\elements\Entry as EntryElement;
use craft\elements\GlobalSet as GlobalSetElement;
use craft\elements\MatrixBlock as MatrixBlockElement;
use craft\elements\User as UserElement;
use craft\errors\GqlException;
use craft\fields\Matrix;
use craft\gql\types\Asset as AssetGqlType;
use craft\gql\types\Entry as EntryGqlType;
use craft\gql\types\GlobalSet as GlobalSetGqlType;
use craft\gql\types\MatrixBlock as MatrixBlockGqlType;
use craft\gql\types\User as UserGqlType;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\models\MatrixBlockType;
use craft\models\Section;
use craft\models\Site;
use craft\models\VolumeFolder;
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

    // Tests
    // =========================================================================

    /**
     * Test resolving fields on entries.
     *
     * @dataProvider entryFieldTestDataProvider
     *
     * @param string $gqlTypeClass The Gql type class
     * @param string $propertyName The propery being tested
     * @param mixed $result True for exact match, false for non-existing or a callback for fetching the data
     */
    public function testEntryFieldResolving(string $gqlTypeClass, string $propertyName, $result)
    {
        $sectionUid = StringHelper::UUID();
        $typeUid = StringHelper::UUID();
        $mockElement = $this->make(
            EntryElement::class, [
                'postDate' => new \DateTime(),
                '__get' => function ($property) {
                    // Assume a content field named 'plainTextField'
                    return $property == 'plainTextField' ? 'ok' : $this->$property;
                },
                'getSection' => function () use ($sectionUid) {
                    return $this->make(Section::class, ['uid' => $sectionUid]);
                },
                'getType' => function () use ($typeUid) {
                    return $this->make(EntryType::class, ['uid' => $typeUid]);
                }
            ]
        );

        $this->_runTest($mockElement, $gqlTypeClass, $propertyName, $result);
    }

    /**
     * Test resolving fields on assets.
     *
     * @dataProvider assetFieldTestDataProvider
     *
     * @param string $gqlTypeClass The Gql type class
     * @param string $propertyName The propery being tested
     * @param mixed $result True for exact match, false for non-existing or a callback for fetching the data
     */
    public function testAssetFieldResolving(string $gqlTypeClass, string $propertyName, $result)
    {
        $volumeUid = StringHelper::UUID();
        $folderUid = StringHelper::UUID();
        $mockElement = $this->make(
            AssetElement::class, [
                '__get' => function ($property) {
                    // Assume a content field named 'plainTextField'
                    return $property == 'imageDescription' ? 'ok' : $this->$property;
                },
                'getVolume' => function () use ($volumeUid) {
                    return $this->make(Volume::class, ['uid' => $volumeUid]);
                },
                'getFolder' => function () use ($folderUid) {
                    return $this->make(VolumeFolder::class, ['uid' => $folderUid]);
                }
            ]
        );

        $this->_runTest($mockElement, $gqlTypeClass, $propertyName, $result);
    }

    /**
     * Test resolving fields on global sets.
     *
     * @dataProvider globalSetFieldTestDataProvider
     *
     * @param string $gqlTypeClass The Gql type class
     * @param string $propertyName The propery being tested
     * @param mixed $result True for exact match, false for non-existing or a callback for fetching the data
     */
    public function testGlobalSetFieldResolving(string $gqlTypeClass, string $propertyName, $result)
    {
        $mockElement = $this->make(
            GlobalSetElement::class, [
                '__get' => function ($property) {
                    // Assume a content field named 'plainTextField'
                    return $property == 'plainTextField' ? 'ok' : $this->$property;
                },
                'handle' => 'aHandle'
            ]
        );

        $this->_runTest($mockElement, $gqlTypeClass, $propertyName, $result);
    }

    /**
     * Test resolving fields on matrix blocks.
     *
     * @dataProvider matrixBlockFieldTestDataProvider
     *
     * @param string $gqlTypeClass The Gql type class
     * @param string $propertyName The propery being tested
     * @param mixed $result True for exact match, false for non-existing or a callback for fetching the data
     */
    public function testMatrixBlockFieldResolving(string $gqlTypeClass, string $propertyName, $result)
    {
        $fieldUid = StringHelper::UUID();
        $typeUid = StringHelper::UUID();
        $ownerUid = StringHelper::UUID();

        $mockElement = $this->make(
            MatrixBlockElement::class, [
                '__get' => function ($property) {
                    // Assume a content field named 'plainTextField'
                    return $property == 'firstSubfield' ? 'ok' : $this->$property;
                },
                'fieldId' => 1000,
                'getField' => function () use ($fieldUid) {
                    return $this->make(Matrix::class, ['uid' => $fieldUid]);
                },
                'getOwner' => function () use ($ownerUid) {
                    return $this->make(EntryElement::class, [
                        'uid' => $ownerUid,
                        'getSite' => function () {
                            return $this->make(Site::class, ['id' => 1000]);
                        },
                        'siteId' => 1000,
                    ]);
                },
                'getType' => function () use ($typeUid) {
                    return $this->make(MatrixBlockType::class, ['uid' => $typeUid]);
                }
            ]
        );

        $this->_runTest($mockElement, $gqlTypeClass, $propertyName, $result);
    }

    /**
     * Test resolving fields on users.
     *
     * @dataProvider userFieldTestDataProvider
     *
     * @param string $gqlTypeClass The Gql type class
     * @param string $propertyName The propery being tested
     * @param mixed $result True for exact match, false for non-existing or a callback for fetching the data
     */
    public function testUserFieldResolving(string $gqlTypeClass, string $propertyName, $result)
    {
        $mockElement = $this->make(
            UserElement::class, [
                '__get' => function ($property) {
                    // Assume a content field named 'plainTextField'
                    return $property == 'shortBio' ? 'ok' : $this->$property;
                },
                'username' => 'admin',
                'getPreferences' => function () {
                    return [
                        'aPreference' => 'value',
                        'timeZone' => 'Fiji'
                    ];
                },
            ]
        );

        $this->_runTest($mockElement, $gqlTypeClass, $propertyName, $result);
    }

    /**
     * Run the test on an element for a type class with the property name.
     *
     * @param string $gqlTypeClass The Gql type class
     * @param string $propertyName The propery being tested
     * @param mixed $result True for exact match, false for non-existing or a callback for fetching the data
     */
    public function _runTest($element, string $gqlTypeClass, string $propertyName, $result)
    {
        $resolveInfo = $this->make(ResolveInfo::class, ['fieldName' => $propertyName]);
        $resolve = function () use ($gqlTypeClass, $element, $resolveInfo) {
            return $this->make($gqlTypeClass)->resolveWithDirectives($element, [], null, $resolveInfo);
        };

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
            [UserGqlType::class, 'preferences', function ($source) { return Json::encode($source->getPreferences());}],
        ];
    }
}
