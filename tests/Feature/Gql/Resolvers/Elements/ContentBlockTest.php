<?php

declare(strict_types=1);

use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Gql\Resolvers\Elements\ContentBlock;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\NameNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;

function createResolveInfoForContentBlock(string $fieldName): ResolveInfo
{
    $parentType = new ObjectType(['name' => 'Test', 'fields' => []]);

    $fieldDefinition = FieldDefinition::create([
        'name' => $fieldName,
        'type' => Type::string(),
    ]);

    $fieldNode = new FieldNode([
        'name' => new NameNode(['value' => $fieldName]),
        'directives' => new NodeList([]),
    ]);

    return new ResolveInfo(
        $fieldDefinition,
        [$fieldNode],
        $parentType,
        ['test', $fieldName],
        new Schema([]),
        [],
        null,
        new OperationDefinitionNode([
            'operation' => 'query',
            'selectionSet' => new SelectionSetNode([]),
        ]),
        [],
    );
}

it('resolves field value from source by field name', function () {
    $source = new stdClass;
    $source->myBlocks = new ElementCollection([
        (object) ['id' => 1, 'title' => 'Block 1'],
        (object) ['id' => 2, 'title' => 'Block 2'],
    ]);

    $resolveInfo = createResolveInfoForContentBlock('myBlocks');
    $result = ContentBlock::resolve($source, [], null, $resolveInfo);

    expect($result)->toBe($source->myBlocks);
});

it('returns null when field value is null', function () {
    $source = new stdClass;
    $source->myBlocks = null;

    $resolveInfo = createResolveInfoForContentBlock('myBlocks');
    $result = ContentBlock::resolve($source, [], null, $resolveInfo);

    expect($result)->toBeNull();
});

it('returns empty collection when field is empty', function () {
    $source = new stdClass;
    $source->myBlocks = new ElementCollection;

    $resolveInfo = createResolveInfoForContentBlock('myBlocks');
    $result = ContentBlock::resolve($source, [], null, $resolveInfo);

    expect($result)->toBeEmpty();
});
