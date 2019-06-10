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
     * @param string $propertyName The propery being tested
     * @param mixed $result True for exact match, false for non-existing, an array for all other result fetching to mimick the `resolve` method.
     */
    public function testEntryFieldResolving(string $propertyName, $result)
    {
        $entry = EntryElement::findOne(['title' => 'Theories of matrix']);

        $resolveInfo = $this->make(ResolveInfo::class, ['fieldName' => $propertyName]);
        $resolvedValue = $this->make(EntryGqlType::class)->resolve($entry, [], null, $resolveInfo);

        if (is_array($result)) {
            $this->assertEquals($entry->{$result[0]}()->{$result[1]}, $resolvedValue);
        } else if ($result === true) {
            $this->assertEquals($entry->$propertyName, $resolvedValue);
        } else {
            $this->assertNull($resolvedValue);
        }

        // After that, create a type generator test
        // That one should register the interface and trigger making all types
        // Then it should instantiate a specific entry type and ensure content is available there.

        // Follow the same pattern for all elements
    }

    public function entryFieldTestDataProvider()
    {
        return [
            ['sectionId', true],
            ['sectionUid', ['getSection', 'uid']],
            ['sectionInvalid', false],
            ['typeId', true],
            ['typeUid', ['getType', 'uid']],
            ['typeInvalid', false],
            ['plainTextField', true],
            ['postDate', true],
        ];
    }
}