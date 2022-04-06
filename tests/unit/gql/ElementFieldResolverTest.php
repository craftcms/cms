<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\gql;

use Craft;
use craft\base\Fs;
use craft\elements\Asset;
use craft\elements\Asset as AssetElement;
use craft\elements\Category as CategoryElement;
use craft\elements\Entry as EntryElement;
use craft\elements\GlobalSet as GlobalSetElement;
use craft\elements\MatrixBlock as MatrixBlockElement;
use craft\elements\User as UserElement;
use craft\errors\GqlException;
use craft\gql\base\ObjectType;
use craft\gql\types\elements\Asset as AssetGqlType;
use craft\gql\types\elements\Category as CategoryGqlType;
use craft\gql\types\elements\Entry as EntryGqlType;
use craft\gql\types\elements\GlobalSet as GlobalSetGqlType;
use craft\gql\types\elements\MatrixBlock as MatrixBlockGqlType;
use craft\gql\types\elements\Tag as TagGqlType;
use craft\gql\types\elements\User as UserGqlType;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\imagetransforms\ImageTransformer;
use craft\models\CategoryGroup;
use craft\models\EntryType;
use craft\models\GqlSchema;
use craft\models\ImageTransform;
use craft\models\MatrixBlockType;
use craft\models\Section;
use craft\models\UserGroup;
use craft\models\Volume;
use craft\services\Assets;
use craft\services\ImageTransforms;
use craft\test\TestCase;
use DateTime;
use GraphQL\Type\Definition\ResolveInfo;
use UnitTester;

class ElementFieldResolverTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    protected function _before(): void
    {
        // Mock the GQL schema for the volumes below
        $this->tester->mockMethods(
            Craft::$app,
            'gql',
            [
                'getActiveSchema' => $this->make(GqlSchema::class, [
                    'scope' => [
                        'usergroups.group-1-uid:read',
                        'usergroups.group-2-uid:read',
                    ],
                ]),
            ]
        );
    }

    protected function _after(): void
    {
    }

    /**
     * Test resolving fields on entries.
     *
     * @dataProvider entryFieldTestDataProvider
     * @param string $gqlTypeClass The Gql type class
     * @phpstan-param class-string $gqlTypeClass
     * @param string $propertyName The property being tested
     * @param mixed $result True for exact match, false for non-existing or a callback for fetching the data
     */
    public function testEntryFieldResolving(string $gqlTypeClass, string $propertyName, mixed $result): void
    {
        $sectionHandle = StringHelper::UUID();
        $typeHandle = StringHelper::UUID();

        $mockElement = $this->make(
            EntryElement::class, [
                'postDate' => new DateTime(),
                '__get' => function($property) {
                    // Assume fields 'plainTextField' and 'typeface'
                    return in_array($property, ['plainTextField', 'typeface'], false) ? 'ok' : $this->$property;
                },
                'getSection' => function() use ($sectionHandle) {
                    return $this->make(Section::class, ['handle' => $sectionHandle]);
                },
                'getType' => function() use ($typeHandle) {
                    return $this->make(EntryType::class, ['handle' => $typeHandle]);
                },
            ]
        );

        $this->_runTest($mockElement, $gqlTypeClass, $propertyName, $result);
    }

    /**
     * Test resolving fields on assets.
     *
     * @dataProvider assetFieldTestDataProvider
     * @param string $gqlTypeClass The Gql type class
     * @phpstan-param class-string $gqlTypeClass
     * @param string $propertyName The property being tested
     * @param mixed $result True for exact match, false for non-existing or a callback for fetching the data
     */
    public function testAssetFieldResolving(string $gqlTypeClass, string $propertyName, mixed $result): void
    {
        $mockElement = $this->make(
            AssetElement::class, [
                '__get' => function($property) {
                    // Assume a content field named 'plainTextField'
                    return in_array($property, ['imageDescription', 'volumeAndMass'], false) ? 'ok' : $this->$property;
                },
            ]
        );

        $this->_runTest($mockElement, $gqlTypeClass, $propertyName, $result);
    }

    /**
     * Test resolving fields on global sets.
     *
     * @dataProvider globalSetFieldTestDataProvider
     * @param string $gqlTypeClass The Gql type class
     * @phpstan-param class-string $gqlTypeClass
     * @param string $propertyName The property being tested
     * @param mixed $result True for exact match, false for non-existing or a callback for fetching the data
     */
    public function testGlobalSetFieldResolving(string $gqlTypeClass, string $propertyName, mixed $result): void
    {
        $mockElement = $this->make(
            GlobalSetElement::class, [
                '__get' => function($property) {
                    // Assume a content field named 'plainTextField'
                    return $property == 'plainTextField' ? 'ok' : $this->$property;
                },
                'handle' => 'aHandle',
            ]
        );

        $this->_runTest($mockElement, $gqlTypeClass, $propertyName, $result);
    }

    /**
     * Test resolving fields on categories
     *
     * @dataProvider categoryFieldTestDataProvider
     * @param string $gqlTypeClass The Gql type class
     * @phpstan-param class-string $gqlTypeClass
     * @param string $propertyName The property being tested
     * @param mixed $result True for exact match, false for non-existing or a callback for fetching the data
     */
    public function testCategoryFieldResolving(string $gqlTypeClass, string $propertyName, mixed $result): void
    {
        $groupHandle = StringHelper::UUID();

        $mockElement = $this->make(
            CategoryElement::class, [
                '__get' => function($property) {
                    // Assume a content field named 'plainTextField'
                    return $property == 'plainTextField' ? 'ok' : $this->$property;
                },
                'getGroup' => function() use ($groupHandle) {
                    return $this->make(CategoryGroup::class, ['handle' => $groupHandle]);
                },
            ]
        );

        $this->_runTest($mockElement, $gqlTypeClass, $propertyName, $result);
    }

    /**
     * Test resolving fields on tags
     *
     * @dataProvider tagFieldTestDataProvider
     * @param string $gqlTypeClass The Gql type class
     * @phpstan-param class-string $gqlTypeClass
     * @param string $propertyName The property being tested
     * @param mixed $result True for exact match, false for non-existing or a callback for fetching the data
     */
    public function testTagFieldResolving(string $gqlTypeClass, string $propertyName, mixed $result): void
    {
        $groupHandle = StringHelper::UUID();

        $mockElement = $this->make(
            CategoryElement::class, [
                '__get' => function($property) {
                    // Assume a content field named 'plainTextField'
                    return $property == 'plainTextField' ? 'ok' : $this->$property;
                },
                'getGroup' => function() use ($groupHandle) {
                    return $this->make(CategoryGroup::class, ['handle' => $groupHandle]);
                },
            ]
        );

        $this->_runTest($mockElement, $gqlTypeClass, $propertyName, $result);
    }

    /**
     * Test resolving fields on matrix blocks.
     *
     * @dataProvider matrixBlockFieldTestDataProvider
     * @param string $gqlTypeClass The Gql type class
     * @phpstan-param class-string $gqlTypeClass
     * @param string $propertyName The property being tested
     * @param mixed $result True for exact match, false for non-existing or a callback for fetching the data
     */
    public function testMatrixBlockFieldResolving(string $gqlTypeClass, string $propertyName, mixed $result): void
    {
        $typeHandle = StringHelper::UUID();

        $mockElement = $this->make(
            MatrixBlockElement::class, [
                '__get' => function($property) {
                    // Assume a content field named 'plainTextField'
                    return $property == 'firstSubfield' ? 'ok' : $this->$property;
                },
                'fieldId' => 1000,
                'ownerId' => 80,
                'typeId' => 99,
                'getType' => function() use ($typeHandle) {
                    return $this->make(MatrixBlockType::class, ['handle' => $typeHandle]);
                },
            ]
        );

        $this->_runTest($mockElement, $gqlTypeClass, $propertyName, $result);
    }

    /**
     * Test resolving fields on users.
     *
     * @dataProvider userFieldTestDataProvider
     * @param string $gqlTypeClass The Gql type class
     * @phpstan-param class-string $gqlTypeClass
     * @param string $propertyName The property being tested
     * @param mixed $result True for exact match, false for non-existing or a callback for fetching the data
     */
    public function testUserFieldResolving(string $gqlTypeClass, string $propertyName, mixed $result): void
    {
        $mockElement = $this->make(
            UserElement::class, [
                '__get' => function($property) {
                    // Assume a content field named 'plainTextField'
                    return $property == 'shortBio' ? 'ok' : $this->$property;
                },
                'username' => 'admin',
                'getPreferences' => function() {
                    return [
                        'aPreference' => 'value',
                        'timeZone' => 'Fiji',
                    ];
                },
                'getGroups' => function() {
                    return [
                        new UserGroup(['uid' => 'group-1-uid', 'handle' => 'Group 1']),
                        new UserGroup(['uid' => 'group-2-uid', 'handle' => 'Group 2']),
                        new UserGroup(['uid' => 'group-3-uid', 'handle' => 'Group 3']),
                    ];
                },
            ]
        );

        $this->_runTest($mockElement, $gqlTypeClass, $propertyName, $result);
    }

    /**
     * Test whether url transform properties are correctly passed on when transforming
     *
     * @param array $fieldArguments
     * @param mixed $expectedArguments
     * @dataProvider assetTransformDataProvider
     */
    public function testAssetUrlTransform(array $fieldArguments, mixed $expectedArguments): void
    {
        $imageTransformService = $this->make(ImageTransforms::class, [
            'getImageTransformer' => $this->make(ImageTransformer::class, [
                'getTransformUrl' => function($asset, ImageTransform $imageTransform) use ($expectedArguments): string {
                    self::assertEquals($expectedArguments, $imageTransform->toArray(array_keys($expectedArguments)));
                    return 'ok';
                },
            ]),
            'getTransformByHandle' => function($handle): ImageTransform {
                return new ImageTransform(['handle' => $handle]);
            },
        ]);

        Craft::$app->set('imageTransforms', $imageTransformService);

        $asset = $this->make(Asset::class, [
            'getVolume' => $this->make(Volume::class, [
                'getFs' => $this->make(Fs::class, [
                    'hasUrls' => true,
                ]),
                'getTransformFs' => $this->make(Fs::class, [
                    'hasUrls' => true,
                ]),
            ]),
            'folderId' => 2,
            'filename' => 'foo.jpg',
        ]);
        $resolveInfo = $this->make(ResolveInfo::class, ['fieldName' => 'url']);


        $this->make(AssetGqlType::class)->resolveWithDirectives($asset, $fieldArguments, null, $resolveInfo);
    }

    /**
     * Run the test on an element for a type class with the property name.
     *
     * @param mixed $element
     * @param string $gqlTypeClass The Gql type class
     * @phpstan-param class-string<ObjectType> $gqlTypeClass
     * @param string $propertyName The property being tested
     * @param mixed $result True for exact match, false for non-existing or a callback for fetching the data
     */
    public function _runTest(mixed $element, string $gqlTypeClass, string $propertyName, mixed $result)
    {
        $resolveInfo = $this->make(ResolveInfo::class, ['fieldName' => $propertyName]);
        $resolve = function() use ($gqlTypeClass, $element, $resolveInfo) {
            /** @var ObjectType $type */
            $type = $this->make($gqlTypeClass);
            return $type->resolveWithDirectives($element, [], null, $resolveInfo);
        };

        if (is_callable($result)) {
            self::assertEquals($result($element), $resolve());
        } elseif ($result === true) {
            self::assertEquals($element->$propertyName, $resolve());
            self::assertNotNull($element->$propertyName);
        } else {
            $this->tester->expectThrowable(GqlException::class, $resolve);
        }
    }

    public function entryFieldTestDataProvider(): array
    {
        return [
            // Entries
            [
                EntryGqlType::class, 'sectionHandle', function($source) {
                    return $source->getSection()->handle;
                },
            ],
            [
                EntryGqlType::class, 'typeHandle', function($source) {
                    return $source->getType()->handle;
                },
            ],
            [EntryGqlType::class, 'typeface', true],
            [EntryGqlType::class, 'missingProperty', false],
            [EntryGqlType::class, 'typeInvalid', false],
            [EntryGqlType::class, 'plainTextField', true],
            [EntryGqlType::class, 'postDate', true],
        ];
    }

    public function assetFieldTestDataProvider(): array
    {
        return [
            [AssetGqlType::class, 'missingProperty', false],
            [AssetGqlType::class, 'imageDescription', true],
            [AssetGqlType::class, 'volumeAndMass', true],
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

    public function categoryFieldTestDataProvider(): array
    {
        return [
            [CategoryGqlType::class, 'missingProperty', false],
            [CategoryGqlType::class, 'plainTextField', true],
            [
                CategoryGqlType::class, 'groupHandle', function($source) {
                    return $source->getGroup()->handle;
                },
            ],
        ];
    }

    public function tagFieldTestDataProvider(): array
    {
        return [
            [TagGqlType::class, 'missingProperty', false],
            [TagGqlType::class, 'plainTextField', true],
            [
                TagGqlType::class, 'groupHandle', function($source) {
                    return $source->getGroup()->handle;
                },
            ],
        ];
    }

    public function matrixBlockFieldTestDataProvider(): array
    {
        return [
            [MatrixBlockGqlType::class, 'missingProperty', false],
            [MatrixBlockGqlType::class, 'firstSubfield', true],
            [MatrixBlockGqlType::class, 'fieldId', true],
            [MatrixBlockGqlType::class, 'typeInvalid', false],
            [MatrixBlockGqlType::class, 'ownerId', true],
            [MatrixBlockGqlType::class, 'typeId', true],
            [
                MatrixBlockGqlType::class, 'typeHandle', function($source) {
                    return $source->getType()->handle;
                },
            ],
        ];
    }

    public function userFieldTestDataProvider(): array
    {
        return [
            [UserGqlType::class, 'missingProperty', false],
            [UserGqlType::class, 'shortBio', true],
            [UserGqlType::class, 'username', true],
            [
                UserGqlType::class, 'preferences', function($source) {
                    return Json::encode($source->getPreferences());
                },
            ],
        ];
    }

    public function assetTransformDataProvider(): array
    {
        return [
            [['width' => 200, 'height' => 200], ['width' => 200, 'height' => 200]],
            [['width' => 400, 'height' => 200], ['width' => 400, 'height' => 200]],
            [['width' => 200, 'height' => 500], ['width' => 200, 'height' => 500]],
            [['width' => 200, 'height' => 200, 'handle' => 'testHandle'], ['handle' => 'testHandle']],
            [['width' => 200, 'height' => 200, 'transform' => 'testHandle2'], ['handle' => 'testHandle2']],
        ];
    }
}
