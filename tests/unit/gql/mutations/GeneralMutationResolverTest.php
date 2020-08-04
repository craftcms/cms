<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql\mutations;

use Codeception\Stub\Expected;
use Craft;
use craft\base\Element;
use craft\elements\Entry;
use craft\fields\PlainText;
use craft\gql\base\ElementMutationResolver;
use craft\gql\resolvers\mutations\Entry as EntryMutationResolver;
use craft\helpers\StringHelper;
use craft\models\GqlSchema;
use craft\services\Elements;
use craft\test\TestCase;
use GraphQL\Error\Error;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\Type;

class GeneralMutationResolverTest extends TestCase
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    protected $resolver;

    protected function _before()
    {
        $this->resolver = new EntryMutationResolver();
    }

    protected function _after()
    {
    }

    /**
     * Test whether data and value normalizes is stored on the resolver correctly.
     *
     * @param $data
     */
    public function testStoringResolverData()
    {
        $testKey = 'someKey';
        $testString = StringHelper::randomString();
        $testData = [
            'one' => 'two',
            'three' => ['four', 'five']
        ];
        $valueNormalizers = [
            'reverseArgument' => function (string $value) {
                return strrev($value);
            },
            'allCaps' => function (string $value) {
                return strtoupper($value);
            }
        ];

        $this->resolver = new EntryMutationResolver($testData, $valueNormalizers);

        // Test constructor storage for values and normalizers
        foreach ($testData as $key => $value) {
            $this->assertSame($this->resolver->getResolutionData($key), $value);
        }

        foreach ($valueNormalizers as $argument => $valueNormalizer) {
            $this->assertSame($this->invokeMethod($this->resolver, 'normalizeValue', [$argument, $testString]), $valueNormalizer($testString));
        }

        // Test setting resolution data and normalizes after construction
        $this->resolver->setResolutionData($testKey, $testString);
        $this->assertSame($this->resolver->getResolutionData($testKey), $testString);
        $this->assertNull($this->resolver->getResolutionData(uniqid('test', true)));

        $normalizer = function ($value) {
            return strlen($value);
        };

        $this->resolver->setValueNormalizer($testKey, $normalizer);
        $this->assertSame($this->invokeMethod($this->resolver, 'normalizeValue', [$testKey, $testString]), $normalizer($testString));

        $this->resolver->setValueNormalizer($testKey, null);
        $this->assertNotSame($this->invokeMethod($this->resolver, 'normalizeValue', [$testKey, $testString]), $normalizer($testString));
    }

    /**
     * Test whether schemas are enforced correctly
     */
    public function testSchemaActionRequirements()
    {
        $this->resolver = new EntryMutationResolver();

        $this->expectExceptionMessage('Unable to perform the action.');
        $this->expectException(Error::class);
        $this->invokeMethod($this->resolver, 'requireSchemaAction', ['missingScope', 'implode']);

        $this->tester->mockCraftMethods('gql', [
            'getActiveSchema' => $this->make(GqlSchema::class, [
                'scope' => ['missingScope:implode']
            ])
        ]);
        $this->invokeMethod($this->resolver, 'requireSchemaAction', ['missingScope', 'implode']);
    }

    /**
     * Test whether populating an element with data behaves as expected.
     *
     * @param $contentFields
     * @param $arguments
     * @throws \ReflectionException
     * @dataProvider populatingElementWithDataProvider
     */
    public function testPopulatingElementWithData($contentFields, $arguments)
    {
        $entry = $this->make(Entry::class, [
            'setFieldValue' => Expected::exactly(count($contentFields))
        ]);

        $this->resolver->setResolutionData(ElementMutationResolver::CONTENT_FIELD_KEY, $contentFields);

        $this->invokeMethod($this->resolver, 'populateElementWithData', [$entry, $arguments]);

        foreach ($arguments as $argument => $value) {
            if (!array_key_exists($argument, $contentFields)) {
                $this->assertSame($value, $entry->{$argument});
            }
        }
    }

    /**
     * Tests whether immutable attributes are immutable indeed.
     *
     * @throws \ReflectionException
     */
    public function testImmutableAttributes()
    {
        $testId = random_int(1, 9999);
        $testUid = StringHelper::UUID();
        $testTitle = StringHelper::UUID();

        $entry = $this->make(Entry::class, [
            'id' => $testId,
            'uid' => $testUid,
            'title' => $testTitle
        ]);

        $arguments = [
            'id' => random_int(1, 9999),
            'uid' => StringHelper::UUID(),
            'title' => StringHelper::UUID()
        ];

        $this->setInaccessibleProperty($this->resolver, 'immutableAttributes', ['id', 'uid', 'title']);
        $this->invokeMethod($this->resolver, 'populateElementWithData', [$entry, $arguments]);

        $this->assertSame($entry->id, $testId);
        $this->assertSame($entry->uid, $testUid);
        $this->assertSame($entry->title, $testTitle);

        $this->assertNotSame($entry->id, $arguments['id']);
        $this->assertNotSame($entry->uid, $arguments['uid']);
        $this->assertNotSame($entry->title, $arguments['title']);
    }

    public function populatingElementWithDataProvider()
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
                    'title' => StringHelper::UUID()
                ]
            ],
            [
                [],
                [
                    'title' => StringHelper::UUID()
                ]
            ],
        ];
    }

    /**
     * Test whether saving an element with validation errors throws the right exception.
     *
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     */
    public function testSavingElementWithValidationError()
    {
        $elementService = $this->make(Elements::class, [
            'saveElement' => Expected::once(false)
        ]);
        Craft::$app->set('elements', $elementService);

        $validationError = 'There was an error saving the element';

        $entry = $this->make(Entry::class, [
            'hasErrors' => true,
            'getFirstErrors' => [$validationError]
        ]);

        $this->expectExceptionMessage($validationError);
        $this->expectException(UserError::class);

        $this->invokeMethod($this->resolver, 'saveElement', [$entry]);
    }

    /**
     * Test whether saving an element that is enabled correctly changes the scenario before saving.
     *
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     */
    public function testSavingElementWithoutValidationError()
    {
        $elementService = $this->make(Elements::class, [
            'saveElement' => false
        ]);
        Craft::$app->set('elements', $elementService);

        $entry = new Entry();

        $scenario = Element::SCENARIO_DEFAULT;
        $entry->setScenario($scenario);
        $entry->enabled = false;

        $this->invokeMethod($this->resolver, 'saveElement', [$entry]);

        // Ensure scenario unchanged for disabled elements
        $this->assertSame($scenario, $entry->getScenario());

        $entry->enabled = true;
        $this->invokeMethod($this->resolver, 'saveElement', [$entry]);

        // Ensure scenario changed for enabled elements with the default scenario
        $this->assertNotSame($scenario, $entry->getScenario());
    }
}
