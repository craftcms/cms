<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\gql;

use Craft;
use craft\elements\Asset as AssetElement;
use craft\elements\Category as CategoryElement;
use craft\elements\Entry as EntryElement;
use craft\elements\GlobalSet as GlobalSetElement;
use craft\elements\Tag as TagElement;
use craft\errors\GqlException;
use craft\fields\Matrix as MatrixField;
use craft\fields\PlainText;
use craft\fields\Table;
use craft\gql\base\SingularTypeInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\gql\interfaces\elements\Category as CategoryInterface;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\interfaces\elements\Tag as TagInterface;
use craft\gql\TypeLoader;
use craft\gql\types\generators\TableRowType;
use craft\models\CategoryGroup;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\GqlSchema;
use craft\models\MatrixBlockType;
use craft\models\Section;
use craft\models\TagGroup;
use craft\models\Volume;
use craft\test\TestCase;
use Exception;
use GraphQL\Type\Definition\ObjectType;
use UnitTester;
use yii\base\UnknownMethodException;

class InterfaceAndGeneratorTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    protected function _before(): void
    {
        // Mock the GQL token
        $this->tester->mockMethods(
            Craft::$app,
            'gql',
            [
                'getActiveSchema' => $this->make(GqlSchema::class, [
                    'scope' => [
                        'volumes.volume-uid-1:read',
                        'volumes.volume-uid-2:read',
                        'sections.section-uid-1:read',
                        'sections.section-uid-2:read',
                        'categorygroups.categoyGroup-uid-1:read',
                        'taggroups.tagGroup-uid-1:read',
                        'globalsets.globalset-uid-1:read',
                    ],
                ]),
            ]

        );

        // Fake out all the different entity fetches.
        $this->tester->mockMethods(
            Craft::$app,
            'volumes',
            [
                'getAllVolumes' => function() {
                    return $this->mockVolumes();
                },
            ]
        );

        $contexts = $this->mockEntryContexts();

        $this->tester->mockMethods(
            Craft::$app,
            'entries',
            [
                'getAllSections' => fn() => array_map(fn(array $context) => $context['section'], $contexts),
                'getAllEntryTypes' => fn() => array_map(fn(array $context) => $context['entryType'], $contexts),
            ],
        );

        $this->tester->mockMethods(
            Craft::$app,
            'globals',
            [
                'getAllSets' => function() {
                    return $this->mockGlobalSets();
                },
            ]
        );

        $this->tester->mockMethods(
            Craft::$app,
            'categories',
            [
                'getAllGroups' => function() {
                    return $this->mockCategoryGroups();
                },
            ]
        );

        $this->tester->mockMethods(
            Craft::$app,
            'tags',
            [
                'getAllTagGroups' => function() {
                    return $this->mockTagGroups();
                },
            ]
        );

        $this->tester->mockMethods(
            Craft::$app,
            'matrix',
            [
                'getAllBlockTypes' => function() {
                    return $this->mockMatrixBlocks();
                },
            ]
        );
    }

    protected function _after(): void
    {
        Craft::$app->getGql()->flushCaches();
    }

    /**
     * Test interfaces running type generators.
     *
     * @dataProvider interfaceDataProvider
     * @param string $gqlInterfaceClass The interface class being tested
     * @phpstan-param class-string<SingularTypeInterface> $gqlInterfaceClass
     * @param callable $getAllContexts The callback that provides an array of all contexts for generated types
     * @param callable $getTypeNameByContext The callback to generate the GQL type name by context
     */
    public function testInterfacesGeneratingTypes(string $gqlInterfaceClass, callable $getAllContexts, callable $getTypeNameByContext): void
    {
        /** @var string|SingularTypeInterface $gqlInterfaceClass */
        $gqlInterfaceClass::getType();

        foreach ($getAllContexts() as $context) {
            $typeName = $getTypeNameByContext($context);

            // Make sure the specific type entity exists and can be loaded.
            self::assertInstanceOf(ObjectType::class, TypeLoader::loadType($typeName));

            // Make sure the generated types are pre-loaded, when asserting valid.
            self::assertTrue(array_key_exists($typeName, Craft::$app->getGql()->getSchemaDef(null, true)->getTypeMap()));
        }
    }

    /**
     * Test table row generator
     *
     * @throws GqlException
     */
    public function testTableRowTypeGenerator(): void
    {
        $tableField = $this->make(Table::class, [
            'columns' => [
                'col1' => [
                    'heading' => 'What',
                    'handle' => 'one',
                    'type' => 'singleline',
                ],
                'col2' => [
                    'heading' => 'When',
                    'handle' => 'two',
                    'type' => 'date',
                ],
                'col3' => [
                    'heading' => 'How many',
                    'handle' => 'howMany',
                    'type' => 'number',
                ],
                'col4' => [
                    'heading' => 'Allow?',
                    'handle' => 'allow',
                    'type' => 'lightswitch',
                ],
            ],
        ]);
        TableRowType::generateTypes($tableField);
        $typeName = TableRowType::getName($tableField);
        self::assertNotFalse(GqlEntityRegistry::getEntity($typeName));
        self::assertInstanceOf(ObjectType::class, TypeLoader::loadType($typeName));
    }


    public function interfaceDataProvider(): array
    {
        return [
            [AssetInterface::class, [$this, 'mockVolumes'], [AssetElement::class, 'gqlTypeName']],
            [EntryInterface::class, fn() => array_map(fn(array $context) => $context['entryType'], $this->mockEntryContexts()), [EntryElement::class, 'gqlTypeName']],
            [CategoryInterface::class, [$this, 'mockCategoryGroups'], [CategoryElement::class, 'gqlTypeName']],
            [TagInterface::class, [$this, 'mockTagGroups'], [TagElement::class, 'gqlTypeName']],
        ];
    }

    /**
     * Mock the volumes for tests.
     *
     * @return array
     * @throws Exception
     */
    public function mockVolumes(): array
    {
        return [
            $this->make(Volume::class, [
                'uid' => 'volume-uid-1',
                'handle' => 'mockVolume1',
                '__call' => fn($name) => match ($name) {
                    'getCustomFields' => [],
                    default => throw new UnknownMethodException("Calling unknown method: $name()"),
                },
            ]),
            $this->make(Volume::class, [
                'uid' => 'volume-uid-2',
                'handle' => 'mockVolume2',
                '__call' => fn($name) => match ($name) {
                    'getCustomFields' => [
                        $this->make(PlainText::class, ['name' => 'Mock Field', 'handle' => 'mockField']),
                    ],
                    default => throw new UnknownMethodException("Calling unknown method: $name()"),
                },
            ]),
        ];
    }

    /**
     * Mock entry contexts for tests.
     *
     * @return array
     * @throws Exception
     */
    public function mockEntryContexts(): array
    {
        $typeA = $this->make(EntryType::class, [
            'uid' => 'entrytype-uid-1',
            'handle' => 'mockType1',
            '__call' => fn($name) => match ($name) {
                'getCustomFields' => [],
                'getFieldLayout' => $this->make(FieldLayout::class, [
                    'uid' => 'entrytype-fieldlayout-uid-1',
                    'getCustomFields' => [],
                ]),
                default => throw new UnknownMethodException("Calling unknown method: $name()"),
            },
        ]);

        $typeBCustomFields = [
            $this->make(PlainText::class, ['name' => 'Mock field', 'handle' => 'mockField']),
        ];
        $typeB = $this->make(EntryType::class, [
            'uid' => 'entrytype-uid-2',
            'handle' => 'mockType2',
            '__call' => fn($name) => match ($name) {
                'getCustomFields' => $typeBCustomFields,
                'getFieldLayout' => $this->make(FieldLayout::class, [
                    'uid' => 'entrytype-fieldlayout-uid-2',
                    'getCustomFields' => $typeBCustomFields,
                ]),
                default => throw new UnknownMethodException("Calling unknown method: $name()"),
            },
        ]);

        $sectionA = $this->make(Section::class, [
            'uid' => 'section-uid-1',
            'handle' => 'mockSection1',
            'getEntryTypes' => [
                $typeA,
            ],
        ]);

        $sectionB = $this->make(Section::class, [
            'uid' => 'section-uid-2',
            'handle' => 'mockSection2',
            'getEntryTypes' => [
                $typeB,
            ],
        ]);

        return [
            [
                'section' => $sectionA,
                'entryType' => $typeA,
            ],
            [
                'section' => $sectionB,
                'entryType' => $typeB,
            ],
        ];
    }

    /**
     * Mock the global sets for tests.
     *
     * @return array
     * @throws Exception
     */
    public function mockGlobalSets(): array
    {
        return [
            $this->make(GlobalSetElement::class, [
                'uid' => 'globalset-uid-1',
                'handle' => 'mockGlobal',
                '__call' => fn($name) => match ($name) {
                    'getCustomFields' => [
                        $this->make(PlainText::class, ['name' => 'Mock Field', 'handle' => 'mockField']),
                    ],
                    default => throw new UnknownMethodException("Calling unknown method: $name()"),
                },
            ]),
        ];
    }

    /**
     * Mock a category group for tests.
     *
     * @return array
     * @throws Exception
     */
    public function mockCategoryGroups(): array
    {
        return [
            $this->make(CategoryGroup::class, [
                'uid' => 'categoyGroup-uid-1',
                'handle' => 'mockCategoryGroup',
                '__call' => fn($name) => match ($name) {
                    'getCustomFields' => [
                        $this->make(PlainText::class, ['name' => 'Mock Field', 'handle' => 'mockField']),
                    ],
                    default => throw new UnknownMethodException("Calling unknown method: $name()"),
                },
            ]),
        ];
    }

    /**
     * Mock a tag group for tests.
     *
     * @return array
     * @throws Exception
     */
    public function mockTagGroups(): array
    {
        return [
            $this->make(TagGroup::class, [
                'uid' => 'tagGroup-uid-1',
                'handle' => 'mockTagGroup',
                '__call' => fn($name) => match ($name) {
                    'getCustomFields' => [
                        $this->make(PlainText::class, ['name' => 'Mock Field', 'handle' => 'mockField']),
                    ],
                    default => throw new UnknownMethodException("Calling unknown method: $name()"),
                },
            ]),
        ];
    }

    /**
     * Mock matrix blocks.
     *
     * @return array
     * @throws Exception
     */
    public function mockMatrixBlocks(): array
    {
        return [
            $this->make(MatrixBlockType::class, [
                'uid' => 'matrixBlock-uid-1',
                'handle' => 'mockMatrixBlock',
                '__call' => fn($name) => match ($name) {
                    'getCustomFields' => [
                        $this->make(PlainText::class, ['name' => 'Mock Field', 'handle' => 'mockField']),
                    ],
                    default => throw new UnknownMethodException("Calling unknown method: $name()"),
                },
                'getField' => $this->makeEmpty(MatrixField::class, ['handle' => 'matrixField']),
            ]),
        ];
    }
}
