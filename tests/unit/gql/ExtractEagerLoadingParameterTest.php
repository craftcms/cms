<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql;

use Codeception\Test\Unit;
use Craft;
use craft\fields\Assets;
use craft\fields\Entries;
use craft\fields\Matrix;
use craft\gql\ElementQueryConditionBuilder;
use craft\models\MatrixBlockType;
use crafttests\fixtures\GqlSchemasFixture;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

class ExtractEagerLoadingParameterTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
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
                        ])
                    ]),
                    $this->make(Entries::class, [
                        'handle' => 'entriesInMatrix',
                        'context' => 'matrix',
                        'getEagerLoadingGqlConditions' => []
                    ]),
                    $this->make(Entries::class, [
                        'handle' => 'linkedEntriesThroughMatrix',
                        'context' => 'global',
                        'getEagerLoadingGqlConditions' => []
                    ]),
                    $this->make(Assets::class, [
                        'handle' => 'image',
                        'context' => 'matrix',
                        'getEagerLoadingGqlConditions' => []
                    ]),
                    $this->make(Assets::class, [
                        'handle' => 'neverAllowed',
                        'context' => 'global',
                        'getEagerLoadingGqlConditions' => false
                    ]),
                ]
            ]
        );
    }

    public function _fixtures()
    {
        return [
            'gqlTokens' => [
                'class' => GqlSchemasFixture::class
            ],
        ];
    }

    protected function _after()
    {
    }

    /**
     * Test eager loading parameter extraction from a query string
     *
     * @param string $query The query string
     * @param array $variables Query variables
     * @param array $expectedParameters The expected eager-loading parameters.
     * @param string $returnType The return type of the GQL query
     *
     * @throws \GraphQL\Error\SyntaxError
     * @dataProvider eagerLoadingParameterExtractionProvider
     */
    public function testEagerLoadingParameterExtraction(string $query, array $variables, array $expectedParameters, $returnType)
    {
        $documentNode = Parser::parse(new Source($query ?: '', 'GraphQL'));
        $resolveInfo = $this->_buildResolveInfo($documentNode, $variables, $returnType);

        $conditionBuilder = Craft::createObject([
            'class' => ElementQueryConditionBuilder::class,
            'resolveInfo' => $resolveInfo
        ]);
        $extractedConditions = $conditionBuilder->extractQueryConditions();

        $this->assertEquals($expectedParameters, $extractedConditions);
    }

    public function eagerLoadingParameterExtractionProvider()
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
          text
        }
        ... on articleBody_imageBlock_BlockType {
          image (volumeId: 2) {
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
                ['neverAllowed', ['id' => 0]],
                'matrixField',
                ['matrixField.mockedBlockHandle:image', ['volumeId' => 2]],
                ['matrixField.mockedBlockHandle:entriesInMatrix', ['id' => 80]],
                ['matrixField.mockedBlockHandle:entriesInMatrix.linkedEntriesThroughMatrix', ['id' => 99]],
                ['entryField', ['sectionId' => [5], 'typeId' => [2]]],
                ['assetField', ['volumeId' => [5]]],
            ]
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
                [
                    'assetField', [
                    'withTransforms' => [
                        ['width' => 400, 'height' => 400],
                        ['width' => 400],
                        'whammy'
                    ],
                    'volumeId' => [5, 7]
                ]
                ]
            ]
        ];

        return [
            [
                '{ user { photo { id }}}',
                [],
                ['with' => ['photo']],
                'UserInterface'
            ],
            [
                '{ entry { assetField { localized { id }}}}',
                [],
                ['with' => [['assetField', ['volumeId' => [5, 7]]]]],
                'UserInterface'
            ],
            [
                '{ entry { entryField { photo }}}',
                [],
                ['with' => [['entryField', ['sectionId' => [5], 'typeId' => [2]]]]],
                'EntryInterface',
            ],
            [
                '{ entry { localized { title } alias: localized { title }}}',
                [],
                ['with' => ['localized', 'localized as alias']],
                'EntryInterface',
            ],
            [
                '{ user { ph: photo { id }}}',
                [],
                ['with' => ['photo']],
                '[UserInterface]'
            ],
            [
                '{entry { author { ph: photo { id }}}}',
                [],
                ['with' => ['author', 'author.photo']],
                'EntryInterface'
            ],
            [
                '{entry { author { photo { id }}}}',
                [],
                ['with' => ['author', 'author.photo']],
                'EntryInterface'
            ],
            [
                '{ entry { assetField (volumeId: 4) { filename }}}',
                [],
                ['with' => [['assetField', ['id' => 0]]]],
                'EntryInterface',
            ],
            [
                '{ entry { localized { id }}}',
                [],
                ['with' => ['localized']],
                'EntryInterface',
            ],
            [
                '{ entry { parent { id }}}',
                [],
                ['with' => ['parent']],
                'EntryInterface',
            ],
            [
                '{ entries { _count(field: "assetField") assetField { filename }}}',
                [],
                ['with' => [['assetField', ['volumeId' => [5, 7], 'count' => true]]]],
                '[EntryInterface]',
            ],
            [
                '{ entries { assetField { filename }}}',
                [],
                [
                    'with' => [['assetField', ['volumeId' => [5, 7]]]]
                ],
                '[EntryInterface]',
            ],
            [
                'query entries ($childSlug: [String]) {
                    entries  {
                        children(type: "child", slug: $childSlug) {
                            id
                            title
                            slug
                        }
                    }
                }',
                ['childSlug' => ['slugslug', 'slugger']],
                [
                    'with' => [['children', ['type' => 'child', 'slug' => ['slugslug', 'slugger']]]],
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
     * @param $returnType
     * @return object
     * @throws \Exception
     */
    private function _buildResolveInfo(DocumentNode $documentNode, array $variables, $returnType)
    {

        $fragments = [];

        foreach ($documentNode->definitions as $definition) {
            if ($definition->kind === NodeKind::FRAGMENT_DEFINITION) {
                $fragments[$definition->name->value] = $definition;
            }
        }

        $list = false;

        if (preg_match('/\[([a-z_]+)\]/i', $returnType, $matches)) {
            $returnType = $matches[1];
            $list = true;
        }

        $type = $this->make(ObjectType::class, [
            'name' => $returnType
        ]);

        return $this->make(ResolveInfo::class, [
            'fragments' => $fragments,
            'fieldNodes' => [
                $documentNode->definitions[0]->selectionSet->selections[0]
            ],
            'fieldName' => 'mockField',
            'variableValues' => $variables,
            'returnType' => $list ? Type::listOf($type) : $type,
        ]);
    }
}
