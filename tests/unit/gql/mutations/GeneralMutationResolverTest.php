<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\gql\mutations;

use Codeception\Stub\Expected;
use Craft;
use craft\base\Element;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\fields\Matrix;
use craft\gql\base\ElementMutationResolver;
use craft\gql\base\Mutation;
use craft\gql\base\MutationResolver;
use craft\gql\GqlEntityRegistry;
use craft\gql\resolvers\mutations\Entry as EntryMutationResolver;
use craft\helpers\StringHelper;
use craft\models\GqlSchema;
use craft\services\Elements;
use craft\test\TestCase;
use GraphQL\Error\Error;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use ReflectionException;
use UnitTester;
use yii\base\InvalidConfigException;

class GeneralMutationResolverTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @var MutationResolver
     */
    protected MutationResolver $resolver;

    protected function _before(): void
    {
        $this->resolver = new EntryMutationResolver();
    }

    protected function _after(): void
    {
    }

    /**
     * Test whether data and value normalizes is stored on the resolver correctly.
     */
    public function testStoringResolverData(): void
    {
        $testKey = 'someKey';
        $testString = StringHelper::randomString();
        $testData = [
            'one' => 'two',
            'three' => ['four', 'five'],
        ];
        $valueNormalizers = [
            'reverseArgument' => function(string $value) {
                return strrev($value);
            },
            'allCaps' => function(string $value) {
                return strtoupper($value);
            },
        ];

        $this->resolver = new EntryMutationResolver($testData, $valueNormalizers);

        // Test constructor storage for values and normalizers
        foreach ($testData as $key => $value) {
            self::assertSame($this->resolver->getResolutionData($key), $value);
        }

        foreach ($valueNormalizers as $argument => $valueNormalizer) {
            self::assertSame($this->invokeMethod($this->resolver, 'normalizeValue', [$argument, $testString]), $valueNormalizer($testString));
        }

        // Test setting resolution data and normalizes after construction
        $this->resolver->setResolutionData($testKey, $testString);
        self::assertSame($this->resolver->getResolutionData($testKey), $testString);
        self::assertNull($this->resolver->getResolutionData(uniqid('test', true)));

        $normalizer = function($value) {
            return strlen($value);
        };

        $this->resolver->setValueNormalizer($testKey, $normalizer);
        self::assertSame($this->invokeMethod($this->resolver, 'normalizeValue', [$testKey, $testString]), $normalizer($testString));

        $this->resolver->setValueNormalizer($testKey, null);
        self::assertNotSame($this->invokeMethod($this->resolver, 'normalizeValue', [$testKey, $testString]), $normalizer($testString));
    }

    /**
     * Test whether schemas are enforced correctly
     */
    public function testSchemaActionRequirements(): void
    {
        $this->resolver = new EntryMutationResolver();

        $this->expectExceptionMessage('Unable to perform the action.');
        $this->expectException(Error::class);
        $this->invokeMethod($this->resolver, 'requireSchemaAction', ['missingScope', 'implode']);

        $this->tester->mockCraftMethods('gql', [
            'getActiveSchema' => $this->make(GqlSchema::class, [
                'scope' => ['missingScope:implode'],
            ]),
        ]);
        $this->invokeMethod($this->resolver, 'requireSchemaAction', ['missingScope', 'implode']);
    }

    /**
     * Test whether populating an element with data behaves as expected.
     *
     * @param array $contentFields
     * @param array $arguments
     * @throws ReflectionException
     * @dataProvider populatingElementWithDataProvider
     */
    public function testPopulatingElementWithData(array $contentFields, array $arguments): void
    {
        $entry = $this->make(Entry::class, [
            'setFieldValue' => Expected::exactly(count($contentFields)),
        ]);

        $this->resolver->setResolutionData(ElementMutationResolver::CONTENT_FIELD_KEY, $contentFields);

        $this->invokeMethod($this->resolver, 'populateElementWithData', [$entry, $arguments]);

        foreach ($arguments as $argument => $value) {
            if (!array_key_exists($argument, $contentFields)) {
                self::assertSame($value, $entry->{$argument});
            }
        }
    }

    /**
     * Tests whether immutable attributes are immutable indeed.
     *
     * @throws ReflectionException
     */
    public function testImmutableAttributes(): void
    {
        $testId = random_int(1, 9999);
        $testUid = StringHelper::UUID();
        $testTitle = StringHelper::UUID();

        $entry = $this->make(Entry::class, [
            'id' => $testId,
            'uid' => $testUid,
            'title' => $testTitle,
        ]);

        $arguments = [
            'id' => random_int(1, 9999),
            'uid' => StringHelper::UUID(),
            'title' => StringHelper::UUID(),
        ];

        $this->setInaccessibleProperty($this->resolver, 'immutableAttributes', ['id', 'uid', 'title']);
        $this->invokeMethod($this->resolver, 'populateElementWithData', [$entry, $arguments]);

        self::assertSame($entry->id, $testId);
        self::assertSame($entry->uid, $testUid);
        self::assertSame($entry->title, $testTitle);

        self::assertNotSame($entry->id, $arguments['id']);
        self::assertNotSame($entry->uid, $arguments['uid']);
        self::assertNotSame($entry->title, $arguments['title']);
    }

    public function populatingElementWithDataProvider(): array
    {
        return [
            [
                [
                    'someField' => Type::string(),
                    'otherField' => Type::string(),
                ],
                [
                    'someField' => StringHelper::UUID(),
                    'otherField' => StringHelper::UUID(),
                    'title' => StringHelper::UUID(),
                ],
            ],
            [
                [],
                [
                    'title' => StringHelper::UUID(),
                ],
            ],
        ];
    }

    /**
     * Test whether saving an element with validation errors throws the right exception.
     *
     * @throws ReflectionException
     * @throws InvalidConfigException
     */
    public function testSavingElementWithValidationError(): void
    {
        $elementService = $this->make(Elements::class, [
            'saveElement' => Expected::once(false),
        ]);
        Craft::$app->set('elements', $elementService);

        $validationError = 'There was an error saving the element';

        $entry = $this->make(Entry::class, [
            'hasErrors' => true,
            'getFirstErrors' => [$validationError],
        ]);

        $this->expectExceptionMessage($validationError);
        $this->expectException(UserError::class);

        $this->invokeMethod($this->resolver, 'saveElement', [$entry]);
    }

    /**
     * Test whether saving an element that is enabled correctly changes the scenario before saving.
     *
     * @throws ReflectionException
     * @throws InvalidConfigException
     */
    public function testSavingElementWithoutValidationError(): void
    {
        $elementService = $this->make(Elements::class, [
            'saveElement' => false,
        ]);
        Craft::$app->set('elements', $elementService);

        $entry = new Entry();

        $scenario = Element::SCENARIO_DEFAULT;
        $entry->setScenario($scenario);
        $entry->enabled = false;

        $this->invokeMethod($this->resolver, 'saveElement', [$entry]);

        // Ensure scenario unchanged for disabled elements
        self::assertSame($scenario, $entry->getScenario());

        $entry->enabled = true;
        $this->invokeMethod($this->resolver, 'saveElement', [$entry]);

        // Ensure scenario changed for enabled elements with the default scenario
        self::assertNotSame($scenario, $entry->getScenario());
    }

    public function testNestedNormalizers(): void
    {
        $values = [];

        /// Setting values on an entry will store this for us.
        $entry = $this->make(Entry::class, [
            'setFieldValue' => function($name, $value) use (&$values) {
                $values[$name] = $value;
            },
        ]);

        // Set up the normalizer to make some measurable impact
        $normalizer = function($value) {
            $value['normalized'] = true;
            return $value;
        };

        // Create both input types
        $nestedObjectType = GqlEntityRegistry::createEntity('nestedType', new InputObjectType([
            'name' => 'nestedType',
            'fields' => [
                'nestedValue' => [
                    'name' => 'nestedValue',
                    'type' => Type::string(),
                ],
            ],
            'normalizeValue' => $normalizer,
        ]));

        $parentObjectType = GqlEntityRegistry::createEntity('parentType', new InputObjectType([
            'name' => 'parentType',
            'fields' => [
                'nested' => [
                    'name' => 'nested',
                    'type' => $nestedObjectType,
                ],
            ],
            'normalizeValue' => $normalizer,
        ]));

        $query = $this->make(EntryQuery::class, [
            'one' => $entry,
        ]);

        Craft::$app->set('elements', $this->make(Elements::class, [
            'saveElement' => true,
            'createElementQuery' => $query,
        ]));

        // Set up the mutation resolve to return our mock entry and pretend to save the entry, when asked to
        // Also mock our input type definitions
        $mutationResolver = $this->make(EntryMutationResolver::class, [
            'getEntryElement' => $entry,
            'saveElement' => function($entry) {
                return $entry;
            },
            'performStructureOperations' => true,
            'argumentTypeDefsByName' => [
                'parentField' => $parentObjectType,
            ],
            'identifyEntry' => $query,
        ]);

        // Finish setting up for the test
        $contentFields = [
            $this->make(Matrix::class, [
                'handle' => 'parentField',
                'getContentGqlMutationArgumentType' => $parentObjectType,
            ]),
        ];
        $this->invokeStaticMethod(Mutation::class, 'prepareResolver', [$mutationResolver, $contentFields]);

        $resolveInfo = $this->make(ResolveInfo::class);

        $arguments = [
            'parentField' => [
                'nested' => [
                    'nestedValue' => 'foo',
                ],
            ],
        ];

        // Finally, do that ONE thing
        $mutationResolver->saveEntry(null, $arguments, null, $resolveInfo);

        $expected = $arguments['parentField'];
        $expected['nested'] = $normalizer($expected['nested']);
        $expected = $normalizer($expected);

        // And validate all the normalizers have executed
        self::assertEquals($expected, $values['parentField']);
    }
}
