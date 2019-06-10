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
use craft\gql\types\Asset as AssetGqlType;
use craft\gql\types\Entry as EntryGqlType;
use craft\gql\types\GlobalSet as GlobalSetGqlType;
use crafttests\fixtures\AssetWithFieldsFixture;
use crafttests\fixtures\EntryWithFieldsFixture;
use crafttests\fixtures\GlobalSetFixture;
use GraphQL\Type\Definition\ResolveInfo;

class FieldResolverTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    private static $_entry = null;

    private static $_asset = null;

    private static $_globalSet = null;

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
                'class' => AssetWithFieldsFixture::class
            ],
            'entries' => [
                'class' => EntryWithFieldsFixture::class
            ],
            'globalSets' => [
                'class' => GlobalSetFixture::class
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
     * @param mixed $result True for exact match, false for non-existing, an array for all other result fetching to mimick the `resolve` method.
     */
    public function testElementFieldResolving(callable $getElement, string $gqlTypeClass, string $propertyName, $result)
    {
        $element = $getElement();

        $resolveInfo = $this->make(ResolveInfo::class, ['fieldName' => $propertyName]);
        $resolvedValue = $this->make($gqlTypeClass)->resolve($element, [], null, $resolveInfo);

        if (is_array($result)) {
            $this->assertEquals($element->{$result[0]}()->{$result[1]}, $resolvedValue);
        } else if ($result === true) {
            $this->assertEquals($element->$propertyName, $resolvedValue);
        } else {
            $this->assertNull($resolvedValue);
        }
    }

    public function entryFieldTestDataProvider()
    {
        return [
            // Entries
            [[$this, '_getEntry'], EntryGqlType::class, 'sectionId', true],
            [[$this, '_getEntry'], EntryGqlType::class, 'sectionUid', ['getSection', 'uid']],
            [[$this, '_getEntry'], EntryGqlType::class, 'sectionInvalid', false],
            [[$this, '_getEntry'], EntryGqlType::class, 'missingProperty', false],
            [[$this, '_getEntry'], EntryGqlType::class, 'typeId', true],
            [[$this, '_getEntry'], EntryGqlType::class, 'typeUid', ['getType', 'uid']],
            [[$this, '_getEntry'], EntryGqlType::class, 'plainTextField', true],
            [[$this, '_getEntry'], EntryGqlType::class, 'postDate', true],

            // Assets
            [[$this, '_getAsset'], AssetGqlType::class, 'volumeId', true],
            [[$this, '_getAsset'], AssetGqlType::class, 'volumeUid', ['getVolume', 'uid']],
            [[$this, '_getAsset'], AssetGqlType::class, 'volumeInvalid', false],
            [[$this, '_getAsset'], AssetGqlType::class, 'missingProperty', false],
            [[$this, '_getAsset'], AssetGqlType::class, 'folderId', true],
            [[$this, '_getAsset'], AssetGqlType::class, 'folderUid', ['getFolder', 'uid']],
            [[$this, '_getAsset'], AssetGqlType::class, 'plainTextField', true],
            [[$this, '_getAsset'], AssetGqlType::class, 'filename', true],

            // Global Set
            [[$this, '_getGlobalSet'], GlobalSetGqlType::class, 'missingProperty', false],
            [[$this, '_getGlobalSet'], GlobalSetGqlType::class, 'plainTextField', true],
            [[$this, '_getGlobalSet'], GlobalSetGqlType::class, 'handle', true],

        ];
    }

    public function _getEntry() {
        if (!self::$_entry) {
            self::$_entry = EntryElement::findOne(['title' => 'Theories of matrix']);
        }

        return self::$_entry;
    }

    public function _getAsset() {
        if (!self::$_asset) {
            self::$_asset = AssetElement::findOne(['filename' => 'product.jpg']);
        }

        return self::$_asset;
    }

    public function _getGlobalSet() {
        if (!self::$_globalSet) {
            self::$_globalSet = GlobalSetElement::findOne(['handle' => 'aGlobalSet']);
        }

        return self::$_globalSet;
    }
}
