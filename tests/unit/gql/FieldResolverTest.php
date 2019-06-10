<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql;

use Codeception\Test\Unit;
use craft\elements\Entry as EntryElement;
use craft\gql\types\Entry as EntryGqlType;
use crafttests\fixtures\EntryWithFieldsFixture;
use GraphQL\Type\Definition\ResolveInfo;

class FieldResolverTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    private static $_entry = null;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    public function _fixtures()
    {
        return [
            'entries' => [
                'class' => EntryWithFieldsFixture::class
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
    public function testEntryFieldResolving(callable $getElement, string $gqlTypeClass, string $propertyName, $result)
    {
        $entry = $getElement();

        $resolveInfo = $this->make(ResolveInfo::class, ['fieldName' => $propertyName]);
        $resolvedValue = $this->make($gqlTypeClass)->resolve($entry, [], null, $resolveInfo);

        if (is_array($result)) {
            $this->assertEquals($entry->{$result[0]}()->{$result[1]}, $resolvedValue);
        } else if ($result === true) {
            $this->assertEquals($entry->$propertyName, $resolvedValue);
        } else {
            $this->assertNull($resolvedValue);
        }
    }

    public function entryFieldTestDataProvider()
    {
        return [
            // Entry types
            [[$this, '_getEntry'], EntryGqlType::class, 'sectionId', true],
            [[$this, '_getEntry'], EntryGqlType::class, 'sectionUid', ['getSection', 'uid']],
            [[$this, '_getEntry'], EntryGqlType::class, 'sectionInvalid', false],
            [[$this, '_getEntry'], EntryGqlType::class, 'typeId', true],
            [[$this, '_getEntry'], EntryGqlType::class, 'typeUid', ['getType', 'uid']],
            [[$this, '_getEntry'], EntryGqlType::class, 'typeInvalid', false],
            [[$this, '_getEntry'], EntryGqlType::class, 'plainTextField', true],
            [[$this, '_getEntry'], EntryGqlType::class, 'postDate', true],
        ];
    }
    
    public function _getEntry() {
        if (!self::$_entry) {
            self::$_entry = EntryElement::findOne(['title' => 'Theories of matrix']);
        }

        return self::$_entry;
    }
}