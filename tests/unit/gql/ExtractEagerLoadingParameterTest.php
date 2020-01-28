<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql;

use Codeception\Test\Unit;
use Craft;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\fields\Assets;
use craft\fields\Entries;
use craft\fields\Matrix;
use craft\gql\resolvers\elements\Entry as EntryResolver;
use craft\models\MatrixBlockType;
use crafttests\fixtures\GqlSchemasFixture;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use GraphQL\Type\Definition\ResolveInfo;

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
     * @param array $expectedParameters The expected eager-loading parameters.
     *
     * @dataProvider eagerLoadingParameterExtractionProvider
     */
    public function testEagerLoadingParameterExtraction(string $query, array $expectedParameters)
    {
        $documentNode = Parser::parse(new Source($query ?: '', 'GraphQL'));
        $resolveInfo = $this->_buildResolveInfo($documentNode);

        $resolvedConditions = [];

        $mockEntry = $this->make(Entry::class, [
            '__get' => function($property) use (&$resolvedConditions) {
                return $this->make(EntryQuery::class, [
                    'with' => function($eagerLoadConditions) use (&$resolvedConditions) {
                        $resolvedConditions = $eagerLoadConditions;
                        return Entry::find();
                    }
                ]);
            }
        ]);
        EntryResolver::resolve($mockEntry, [], null, $resolveInfo);

        $this->assertEquals($expectedParameters, $resolvedConditions);
    }

    public function eagerLoadingParameterExtractionProvider()
    {
        $gql = <<<'GQL'
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
        $result = [
            ['neverAllowed', ['id' => 0]],
            'matrixField',
            ['matrixField.mockedBlockHandle:image', ['volumeId' => 2]],
            ['matrixField.mockedBlockHandle:entriesInMatrix', ['id' => 80]],
            ['entryField', ['sectionId' => [5], 'typeId' => [2]]],
            ['assetField', ['volumeId' => [5]]],
        ];

        return [
            [
                '{ entries { assetField (volumeId: 4) { filename }}}',
                [
                    ['assetField', ['id' => 0]]
                ],
            ],
            [
                '{ entries { _count(field: "assetField") assetField { filename }}}',
                [
                    ['assetField', ['volumeId' => [5, 7], 'count' => true]]
                ],
            ],
            [
                '{ entries { assetField { filename }}}',
                [
                    ['assetField', ['volumeId' => [5, 7]]]
                ],
            ],
            [$gql, $result]
        ];
    }

    /**
     * Mock the ResolveInfo variable.
     *
     * @param DocumentNode $documentNode
     * @return object
     * @throws \Exception
     */
    private function _buildResolveInfo(DocumentNode $documentNode)
    {

        $fragments = [];

        foreach ($documentNode->definitions as $definition) {
            if ($definition->kind === NodeKind::FRAGMENT_DEFINITION) {
                $fragments[$definition->name->value] = $definition;
            }
        }

        return $this->make(ResolveInfo::class, [
            'fragments' => $fragments,
            'fieldNodes' => [
                $documentNode->definitions[0]->selectionSet->selections[0]
            ],
            'fieldName' => 'mockField'
        ]);
    }
}
