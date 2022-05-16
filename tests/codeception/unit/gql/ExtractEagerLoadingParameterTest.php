<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\gql;

use Craft;
use craft\elements\db\EagerLoadPlan;
use craft\fields\Assets;
use craft\fields\Entries;
use craft\fields\Matrix;
use craft\gql\ArgumentManager;
use craft\gql\ElementQueryConditionBuilder;
use craft\models\MatrixBlockType;
use craft\test\TestCase;
use crafttests\fixtures\GqlSchemasFixture;
use Exception;
use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use UnitTester;

class ExtractEagerLoadingParameterTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    protected function _before(): void
    {
        $gqlService = Craft::$app->getGql();
        $schema = $gqlService->getSchemaById(1000);
        $gqlService->setActiveSchema($schema);

        $this->tester->mockMethods(
            Craft::$app,
            'fields',
            [
                'getAllFields' => [
                    $this->make(Entries::class, [
                        'handle' => 'entryField',
                        'context' => 'global',
                        'getEagerLoadingGqlConditions' => ['sectionId' => [5], 'typeId' => [2]],
                    ]),
                    $this->make(Assets::class, [
                        'handle' => 'assetField',
                        'context' => 'global',
                        'getEagerLoadingGqlConditions' => ['volumeId' => [5, 7]],
                    ]),
                    $this->make(Matrix::class, [
                        'handle' => 'matrixField',
                        'context' => 'global',
                        'getEagerLoadingGqlConditions' => [],
                        'getGqlFragmentEntityByName' => $this->make(MatrixBlockType::class, [
                            'getEagerLoadingPrefix' => 'mockedBlockHandle',
                            'getFieldContext' => 'matrix',
                        ]),
                    ]),
                    $this->make(Entries::class, [
                        'handle' => 'entriesInMatrix',
                        'context' => 'matrix',
                        'getEagerLoadingGqlConditions' => [],
                    ]),
                    $this->make(Entries::class, [
                        'handle' => 'linkedEntriesThroughMatrix',
                        'context' => 'global',
                        'getEagerLoadingGqlConditions' => [],
                    ]),
                    $this->make(Assets::class, [
                        'handle' => 'image',
                        'context' => 'matrix',
                        'getEagerLoadingGqlConditions' => [],
                    ]),
                    $this->make(Assets::class, [
                        'handle' => 'neverAllowed',
                        'context' => 'global',
                        'getEagerLoadingGqlConditions' => null,
                    ]),
                ],
            ]
        );
    }

    public function _fixtures(): array
    {
        return [
            'gqlTokens' => [
                'class' => GqlSchemasFixture::class,
            ],
        ];
    }

    protected function _after(): void
    {
    }

    /**
     * Test eager loading parameter extraction from a query string
     *
     * @param string $query The query string
     * @param array $variables Query variables
     * @param array $expectedParameters The expected eager-loading parameters.
     * @param string $returnType The return type of the GQL query
     * @throws SyntaxError
     * @dataProvider eagerLoadingParameterExtractionProvider
     */
    public function testEagerLoadingParameterExtraction(string $query, array $variables, array $expectedParameters, string $returnType): void
    {
        $documentNode = Parser::parse(new Source($query ?: '', 'GraphQL'));
        $resolveInfo = $this->_buildResolveInfo($documentNode, $variables, $returnType);

        $conditionBuilder = Craft::createObject([
            'class' => ElementQueryConditionBuilder::class,
            'resolveInfo' => $resolveInfo,
            'argumentManager' => new ArgumentManager(),
        ]);
        $extractedConditions = $conditionBuilder->extractQueryConditions();

        self::assertEquals($expectedParameters, $extractedConditions);
    }

    public function eagerLoadingParameterExtractionProvider(): array
    {
        $complexGql = <<<'GQL'
{
  entries {
    id
    title
    dateCreated
    neverAllowed {
      title
    }    
    ... on articles_articles_Entry {
      matrixField {
        ... on articleBody_quote_BlockType {
          quote
          author
        }
        ... on articleBody_articleSegment_BlockType {
          im: image (volumeId: 2) {
            filename
          }
          text
        }
        ... on articleBody_imageBlock_BlockType {
          im: image (volumeId: 2) {
            filename
          }
          caption
        }
        ... on articleBody_linkedEntries_BlockType {
          entriesInMatrix (id: 80) {
            title
            ... on articles_news_Entry {
                linkedEntriesThroughMatrix (id: 99) {
                    title
                }
            }
          }
        }
      }
      entryField {
        title
      }
      assetField (volumeId: [5, 1]) {
        title
      }
    }
  }
}
GQL;

        $complexResult = [
            'with' => [
                new EagerLoadPlan(['handle' => 'neverAllowed', 'alias' => 'neverAllowed', 'criteria' => ['id' => ['and', 1, 2]]]),
                new EagerLoadPlan([
                    'handle' => 'matrixField', 'alias' => 'matrixField', 'when' => function() {
                    }, 'nested' => [
                        new EagerLoadPlan([
                            'handle' => 'mockedBlockHandle:image', 'alias' => 'im', 'criteria' => ['volumeId' => 2], 'when' => function() {
                            },
                        ]),
                        new EagerLoadPlan([
                            'handle' => 'mockedBlockHandle:image', 'alias' => 'im', 'criteria' => ['volumeId' => 2], 'when' => function() {
                            },
                        ]),
                        new EagerLoadPlan([
                            'handle' => 'mockedBlockHandle:entriesInMatrix', 'alias' => 'mockedBlockHandle:entriesInMatrix', 'criteria' => ['id' => 80], 'when' => function() {
                            }, 'nested' => [
                                new EagerLoadPlan([
                                    'handle' => 'linkedEntriesThroughMatrix', 'alias' => 'linkedEntriesThroughMatrix', 'when' => function() {
                                    }, 'criteria' => ['id' => 99],
                                ]),
                            ],
                        ]),
                    ],
                ]),
                new EagerLoadPlan([
                    'handle' => 'entryField', 'alias' => 'entryField', 'when' => function() {
                    }, 'criteria' => ['sectionId' => [5], 'typeId' => [2]],
                ]),
                new EagerLoadPlan([
                    'handle' => 'assetField', 'alias' => 'assetField', 'when' => function() {
                    }, 'criteria' => ['volumeId' => [5]],
                ]),
            ],
        ];

        $assetGql = <<<'GQL'
{
    asset @transform(handle: "someHandle") {
        url @transform(handle: "twoHandles")
        width(handle: "threeHandles")
        some: url(height: 800)
        assetField @transform(width: 400, height: 400) {
            url (width: 400)
            width (handle: "whammy")
        }
    }
}
GQL;

        $assetResult = [
            'withTransforms' => [
                'someHandle',
                'twoHandles',
                'threeHandles',
                ['height' => 800],
            ],
            'with' => [
                new EagerLoadPlan([
                    'handle' => 'assetField', 'alias' => 'assetField', 'criteria' => [
                        'withTransforms' => [
                            ['width' => 400, 'height' => 400],
                            ['width' => 400],
                            'whammy',
                        ], 'volumeId' => [5, 7],
                    ],
                ]),
            ],
        ];

        return [
            [
                '{ user { photo { id }}}',
                [],
                ['with' => [new EagerLoadPlan(['handle' => 'photo', 'alias' => 'photo'])]],
                'UserInterface',
            ],
            [
                '{ entry { assetField { localized { id }}}}',
                [],
                ['with' => [new EagerLoadPlan(['handle' => 'assetField', 'alias' => 'assetField', 'criteria' => ['volumeId' => [5, 7]]])]],
                'UserInterface',
            ],
            [
                '{ entry { entryField { photo }}}',
                [],
                ['with' => [new EagerLoadPlan(['handle' => 'entryField', 'alias' => 'entryField', 'criteria' => ['sectionId' => [5], 'typeId' => [2]]])]],
                'EntryInterface',
            ],
            [
                '{ entry { localized { title } alias: localized { title }}}',
                [],
                ['with' => [new EagerLoadPlan(['handle' => 'localized', 'alias' => 'localized']), new EagerLoadPlan(['handle' => 'localized', 'alias' => 'alias'])]],
                'EntryInterface',
            ],
            [
                '{ user { ph: photo { id }}}',
                [],
                ['with' => [new EagerLoadPlan(['handle' => 'photo', 'alias' => 'photo'])]],
                '[UserInterface]',
            ],
            [
                '{entry { author { ph: photo { id }}}}',
                [],
                ['with' => [new EagerLoadPlan(['handle' => 'author', 'alias' => 'author', 'nested' => [new EagerLoadPlan(['handle' => 'photo', 'alias' => 'photo'])]])]],
                'EntryInterface',
            ],
            [
                '{entry { author { photo { id }}}}',
                [],
                ['with' => [new EagerLoadPlan(['handle' => 'author', 'alias' => 'author', 'nested' => [new EagerLoadPlan(['handle' => 'photo', 'alias' => 'photo'])]])]],
                'EntryInterface',
            ],
            [
                '{ entry { assetField (volumeId: 4) { filename }}}',
                [],
                ['with' => [new EagerLoadPlan(['handle' => 'assetField', 'alias' => 'assetField', 'criteria' => ['id' => ['and', 1, 2]]])]],
                'EntryInterface',
            ],
            [
                '{ entry { localized { id }}}',
                [],
                ['with' => [new EagerLoadPlan(['handle' => 'localized', 'alias' => 'localized'])]],
                'EntryInterface',
            ],
            [
                '{ entry { parent { id }}}',
                [],
                ['with' => [new EagerLoadPlan(['handle' => 'parent', 'alias' => 'parent'])]],
                'EntryInterface',
            ],
            [
                '{ entries { _count(field: "assetField") assetField { filename }}}',
                [],
                ['with' => [new EagerLoadPlan(['handle' => 'assetField', 'count' => true, 'alias' => 'assetField', 'criteria' => ['volumeId' => [5, 7]]])]],
                '[EntryInterface]',
            ],
            [
                '{ entries { assetField { filename }}}',
                [],
                [
                    'with' => [new EagerLoadPlan(['handle' => 'assetField', 'alias' => 'assetField', 'criteria' => ['volumeId' => [5, 7]]])],
                ],
                '[EntryInterface]',
            ],
            [
                'query entries ($childSlug: [String]) {
                    entries  {
                        drafts(orderBy: "desc") {
                            id
                        }
                        children(type: "child", slug: $childSlug) {
                            id
                            title
                            slug
                        }
                    }
                }',
                ['childSlug' => ['slugslug', 'slugger']],
                [
                    'with' => [new EagerLoadPlan(['handle' => 'drafts', 'alias' => 'drafts', 'criteria' => ['orderBy' => 'desc']]), new EagerLoadPlan(['handle' => 'children', 'alias' => 'children', 'criteria' => ['type' => 'child', 'slug' => ['slugslug', 'slugger']]])],
                ],
                '[EntryInterface]',
            ],
            [$complexGql, [], $complexResult, 'EntryInterface'],
            [$assetGql, [], $assetResult, 'AssetInterface'],
        ];
    }

    /**
     * Mock the ResolveInfo variable.
     *
     * @param DocumentNode $documentNode
     * @param array $variables
     * @param string $returnType
     * @return ResolveInfo
     * @throws Exception
     */
    private function _buildResolveInfo(DocumentNode $documentNode, array $variables, string $returnType): ResolveInfo
    {
        $fragments = [];

        foreach ($documentNode->definitions as $definition) {
            if ($definition->kind === NodeKind::FRAGMENT_DEFINITION) {
                /** @var FragmentDefinitionNode $definition */
                $fragments[$definition->name->value] = $definition;
            }
        }

        $list = false;

        if (preg_match('/\[([a-z_]+)\]/i', $returnType, $matches)) {
            $returnType = $matches[1];
            $list = true;
        }

        $type = $this->make(ObjectType::class, [
            'name' => $returnType,
        ]);

        /** @var OperationDefinitionNode|FragmentDefinitionNode $definition */
        $definition = $documentNode->definitions[0];

        return $this->make(ResolveInfo::class, [
            'fragments' => $fragments,
            'fieldNodes' => new \ArrayObject([$definition->selectionSet->selections[0]]),
            'fieldName' => 'mockField',
            'variableValues' => $variables,
            'returnType' => $list ? Type::listOf($type) : $type,
        ]);
    }
}
