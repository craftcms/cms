<?php

declare(strict_types=1);

use CraftCms\Cms\Gql\ArgumentManager;
use CraftCms\Cms\Gql\ElementQueryConditionBuilder;
use CraftCms\Cms\Support\Facades\Fields;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;

it('decodes standard GraphQL values in eager-loading conditions', function () {
    Fields::shouldReceive('getAllFields')->once()->with(false)->andReturn(collect());

    $document = Parser::parse(<<<'GRAPHQL'
query Entries($criteria: [String]) {
  entries {
    children(
      slug: $criteria
      status: null
      customEnum: DRAFT
      relatedTo: [{ source: "news", nested: { enabled: true } }]
      limit: 2
    ) {
      id
    }
  }
}
GRAPHQL);
    $operation = $document->definitions[0];
    expect($operation)->toBeInstanceOf(OperationDefinitionNode::class);

    $fieldNode = $operation->selectionSet->selections[0];
    $returnType = new ObjectType(['name' => 'EntryInterface', 'fields' => []]);
    $resolveInfo = new ResolveInfo(
        new FieldDefinition(['name' => 'entries', 'type' => Type::listOf($returnType)]),
        new ArrayObject([$fieldNode]),
        new ObjectType(['name' => 'Query', 'fields' => []]),
        ['entries'],
        new Schema([]),
        [],
        null,
        $operation,
        ['criteria' => ['one', 'two']],
    );

    $builder = new ElementQueryConditionBuilder(['resolveInfo' => $resolveInfo]);
    $builder->setArgumentManager(new ArgumentManager);
    $conditions = $builder->extractQueryConditions();

    expect($conditions['with'][0]->criteria)->toBe([
        'slug' => ['one', 'two'],
        'status' => null,
        'customEnum' => 'DRAFT',
        'relatedTo' => [
            [
                'source' => 'news',
                'nested' => ['enabled' => true],
            ],
        ],
        'limit' => 2,
    ]);
});
