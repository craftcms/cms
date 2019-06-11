<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql;

use Codeception\Test\Unit;
use craft\elements\Asset as AssetElement;
use craft\elements\Entry as EntryElement;
use craft\elements\GlobalSet as GlobalSetElement;
use craft\elements\User as UserElement;
use craft\gql\types\Asset as AssetGqlType;
use craft\gql\types\Entry as EntryGqlType;
use craft\gql\types\User as UserGqlType;
use craft\gql\types\GlobalSet as GlobalSetGqlType;
use craft\helpers\Json;
use crafttests\fixtures\AssetsFixture;
use crafttests\fixtures\EntryWithFieldsFixture;
use crafttests\fixtures\GlobalSetFixture;
use crafttests\fixtures\UsersFixture;
use GraphQL\Type\Definition\ResolveInfo;

class FieldResolverTest extends Unit
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
     * Test resolving fields on elements.
     *
     * @dataProvider entryFieldTestDataProvider, maybe?
     *
     * @param callable $getElement The callback which returns the element for testing
     * @param string $gqlTypeClass The Gql type class
     * @param string $propertyName The propery being tested
     * @param mixed $result True for exact match, false for non-existing or a callback for fetching the data
     */
    public function testElementFieldResolving(callable $getElement, string $gqlTypeClass, string $propertyName, $result)
    {
        $element = $getElement();

        $resolveInfo = $this->make(ResolveInfo::class, ['fieldName' => $propertyName]);
        $resolvedValue = $this->make($gqlTypeClass)->resolve($element, [], null, $resolveInfo);

        if (is_callable($result)) {
            $this->assertEquals($result($element), $resolvedValue);
        } else if ($result === true) {
            $this->assertEquals($element->$propertyName, $resolvedValue);
            $this->assertNotNull($element->$propertyName);
        } else {
            $this->assertNull($resolvedValue);
        }
    }

    public function entryFieldTestDataProvider()
    {
        return [
            // Entries
            [[$this, '_getEntry'], EntryGqlType::class, 'sectionId', true],
            [[$this, '_getEntry'], EntryGqlType::class, 'sectionUid', function ($source) { return $source->getSection()->uid;}],
            [[$this, '_getEntry'], EntryGqlType::class, 'sectionInvalid', false],
            [[$this, '_getEntry'], EntryGqlType::class, 'missingProperty', false],
            [[$this, '_getEntry'], EntryGqlType::class, 'typeId', true],
            [[$this, '_getEntry'], EntryGqlType::class, 'typeUid', function ($source) { return $source->getType()->uid;}],
            [[$this, '_getEntry'], EntryGqlType::class, 'plainTextField', true],
            [[$this, '_getEntry'], EntryGqlType::class, 'postDate', true],

            // Assets
            [[$this, '_getAsset'], AssetGqlType::class, 'volumeId', true],
            [[$this, '_getAsset'], AssetGqlType::class, 'volumeUid', function ($source) { return $source->getVolume()->uid;}],
            [[$this, '_getAsset'], AssetGqlType::class, 'volumeInvalid', false],
            [[$this, '_getAsset'], AssetGqlType::class, 'missingProperty', false],
            [[$this, '_getAsset'], AssetGqlType::class, 'folderId', true],
            [[$this, '_getAsset'], AssetGqlType::class, 'folderUid', function ($source) { return $source->getFolder()->uid;}],
            [[$this, '_getAsset'], AssetGqlType::class, 'imageDescription', true],
            [[$this, '_getAsset'], AssetGqlType::class, 'filename', true],

            // Global Set
            [[$this, '_getGlobalSet'], GlobalSetGqlType::class, 'missingProperty', false],
            [[$this, '_getGlobalSet'], GlobalSetGqlType::class, 'plainTextField', true],
            [[$this, '_getGlobalSet'], GlobalSetGqlType::class, 'handle', true],

            // User
            [[$this, '_getUser'], UserGqlType::class, 'missingProperty', false],
            [[$this, '_getUser'], UserGqlType::class, 'shortBio', true],
            [[$this, '_getUser'], UserGqlType::class, 'username', true],
            [[$this, '_getUser'], UserGqlType::class, 'preferences', function ($source) { return Json::encode($source->preferences);}],
            // TODO make sure this test works also when we have user groups in fixtures
            [[$this, '_getUser'], UserGqlType::class, 'groupHandles', function ($source) {return array_map(function ($userGroup) { return $userGroup->handle;}, $source->getGroups());}],

        ];
    }

    public function _getEntry() {
        return EntryElement::findOne(['title' => 'Theories of matrix']);
    }

    public function _getAsset() {
        return AssetElement::findOne(['filename' => 'product.jpg']);
    }

    public function _getGlobalSet() {
        return GlobalSetElement::findOne(['handle' => 'aGlobalSet']);
    }

    public function _getUser() {
        return UserElement::findOne(['username' => 'user1']);
    }
}
