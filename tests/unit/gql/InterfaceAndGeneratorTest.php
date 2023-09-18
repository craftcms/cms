<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\gql;

use Codeception\Stub;
use Craft;
use craft\elements\Asset as AssetElement;
use craft\elements\Category as CategoryElement;
use craft\elements\Entry as EntryElement;
use craft\elements\GlobalSet as GlobalSetElement;
use craft\elements\Tag as TagElement;
use craft\errors\GqlException;
use craft\fields\PlainText;
use craft\fields\Table;
use craft\gql\base\SingularTypeInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\gql\interfaces\elements\Category as CategoryInterface;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\interfaces\elements\Tag as TagInterface;
use craft\gql\TypeLoader;
use craft\gql\types\generators\EntryType as EntryTypeGenerator;
use craft\gql\types\generators\TableRowType;
use craft\models\CategoryGroup;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\GqlSchema;
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
                'getAllVolumes' => fn() => static::mockVolumes(),
            ]
        );

        $contexts = static::mockEntryContexts();

        $this->tester->mockMethods(
            Craft::$app,
            'entries',
            [
                'getAllSections' => fn() => array_filter(array_map(fn(array $context) => $context['section'], $contexts)),
                'getAllEntryTypes' => fn() => array_filter(array_map(fn(array $context) => $context['entryType'], $contexts)),
            ],
        );

        $this->tester->mockMethods(
            Craft::$app,
            'globals',
            [
                'getAllSets' => function() {
                    return static::mockGlobalSets();
                },
            ]
        );

        $this->tester->mockMethods(
            Craft::$app,
            'categories',
            [
                'getAllGroups' => function() {
                    return static::mockCategoryGroups();
                },
            ]
        );

        $this->tester->mockMethods(
            Craft::$app,
            'tags',
            [
                'getAllTagGroups' => function() {
                    return static::mockTagGroups();
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
     * @param callable|null $generator The type generator that should be called
     * @param bool $preloaded Whether the type sholud be preloaded
     */
    public function testInterfacesGeneratingTypes(
        string $gqlInterfaceClass,
        callable $getAllContexts,
        callable $getTypeNameByContext,
        ?callable $generator = null,
        bool $preloaded = true,
    ): void {
        /** @var string|SingularTypeInterface $gqlInterfaceClass */
        $gqlInterfaceClass::getType();

        foreach ($getAllContexts() as $context) {
            if ($generator) {
                $generator($context);
            }

            $typeName = $getTypeNameByContext($context);

            // Make sure the specific type entity exists and can be loaded.
            self::assertInstanceOf(ObjectType::class, TypeLoader::loadType($typeName));

            // Make sure the generated types are pre-loaded, when asserting valid.
            if ($preloaded) {
                self::assertTrue(array_key_exists($typeName, Craft::$app->getGql()->getSchemaDef(null, true)->getTypeMap()));
            }
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


    public static function interfaceDataProvider(): array
    {
        return [
            [AssetInterface::class, fn() => static::mockVolumes(), [AssetElement::class, 'gqlTypeName']],
            [
                EntryInterface::class,
                fn() => array_filter(array_map(fn(array $context) => $context['entryType'], static::mockEntryContexts())),
                [EntryElement::class, 'gqlTypeName'],
                [EntryTypeGenerator::class, 'generateType'],
                false,
            ],
            [CategoryInterface::class, fn() => static::mockCategoryGroups(), [CategoryElement::class, 'gqlTypeName']],
            [TagInterface::class, fn() => static::mockTagGroups(), [TagElement::class, 'gqlTypeName']],
        ];
    }

    /**
     * Mock the volumes for tests.
     *
     * @return array
     * @throws Exception
     */
    public static function mockVolumes(): array
    {
        return [
            Stub::make(Volume::class, [
                'uid' => 'volume-uid-1',
                'handle' => 'mockVolume1',
                '__call' => fn($name) => match ($name) {
                    'getCustomFields' => [],
                    default => throw new UnknownMethodException("Calling unknown method: $name()"),
                },
            ]),
            Stub::make(Volume::class, [
                'uid' => 'volume-uid-2',
                'handle' => 'mockVolume2',
                '__call' => fn($name) => match ($name) {
                    'getCustomFields' => [
                        Stub::make(PlainText::class, ['name' => 'Mock Field', 'handle' => 'mockField']),
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
    public static function mockEntryContexts(): array
    {
        $typeA = Stub::make(EntryType::class, [
            'uid' => 'entrytype-uid-1',
            'handle' => 'mockType1',
            '__call' => fn($name) => match ($name) {
                'getCustomFields' => [],
                'getFieldLayout' => Stub::make(FieldLayout::class, [
                    'uid' => 'entrytype-fieldlayout-uid-1',
                    'getCustomFields' => [],
                ]),
                default => throw new UnknownMethodException("Calling unknown method: $name()"),
            },
        ]);

        $typeBCustomFields = [
            Stub::make(PlainText::class, ['name' => 'Mock field', 'handle' => 'mockField']),
        ];
        $typeB = Stub::make(EntryType::class, [
            'uid' => 'entrytype-uid-2',
            'handle' => 'mockType2',
            '__call' => fn($name) => match ($name) {
                'getCustomFields' => $typeBCustomFields,
                'getFieldLayout' => Stub::make(FieldLayout::class, [
                    'uid' => 'entrytype-fieldlayout-uid-2',
                    'getCustomFields' => $typeBCustomFields,
                ]),
                default => throw new UnknownMethodException("Calling unknown method: $name()"),
            },
        ]);

        $typeCCustomFields = [
            Stub::make(PlainText::class, ['name' => 'Mock Field', 'handle' => 'mockField']),
        ];
        $typeC = Stub::make(EntryType::class, [
            'uid' => 'matrixEntry-uid-1',
            'handle' => 'mockMatrixEntry',
            '__call' => fn($name) => match ($name) {
                'getCustomFields' => $typeCCustomFields,
                'getFieldLayout' => Stub::make(FieldLayout::class, [
                    'uid' => 'entrytype-fieldlayout-uid-3',
                    'getCustomFields' => $typeCCustomFields,
                ]),
                default => throw new UnknownMethodException("Calling unknown method: $name()"),
            },
        ]);

        $sectionA = Stub::make(Section::class, [
            'uid' => 'section-uid-1',
            'handle' => 'mockSection1',
            'getEntryTypes' => [
                $typeA,
            ],
        ]);

        $sectionB = Stub::make(Section::class, [
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
            [
                'section' => null,
                'entryType' => $typeC,
            ],
        ];
    }

    /**
     * Mock the global sets for tests.
     *
     * @return array
     * @throws Exception
     */
    public static function mockGlobalSets(): array
    {
        return [
            Stub::make(GlobalSetElement::class, [
                'uid' => 'globalset-uid-1',
                'handle' => 'mockGlobal',
                '__call' => fn($name) => match ($name) {
                    'getCustomFields' => [
                        Stub::make(PlainText::class, ['name' => 'Mock Field', 'handle' => 'mockField']),
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
    public static function mockCategoryGroups(): array
    {
        return [
            Stub::make(CategoryGroup::class, [
                'uid' => 'categoyGroup-uid-1',
                'handle' => 'mockCategoryGroup',
                '__call' => fn($name) => match ($name) {
                    'getCustomFields' => [
                        Stub::make(PlainText::class, ['name' => 'Mock Field', 'handle' => 'mockField']),
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
    public static function mockTagGroups(): array
    {
        return [
            Stub::make(TagGroup::class, [
                'uid' => 'tagGroup-uid-1',
                'handle' => 'mockTagGroup',
                '__call' => fn($name) => match ($name) {
                    'getCustomFields' => [
                        Stub::make(PlainText::class, ['name' => 'Mock Field', 'handle' => 'mockField']),
                    ],
                    default => throw new UnknownMethodException("Calling unknown method: $name()"),
                },
            ]),
        ];
    }
}
