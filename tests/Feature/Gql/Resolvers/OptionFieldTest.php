<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Data\MultiOptionsFieldData;
use CraftCms\Cms\Field\Data\OptionData;
use CraftCms\Cms\Field\Data\SingleOptionFieldData;
use CraftCms\Cms\Gql\Resolvers\OptionField;
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

function createResolveInfoForOptionField(string $fieldName): ResolveInfo
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

it('resolves single option field value', function () {
    $source = new stdClass;
    $source->myField = new SingleOptionFieldData(
        label: 'My Label',
        value: 'my_value',
        selected: true,
    );

    $resolveInfo = createResolveInfoForOptionField('myField');
    $result = OptionField::resolve($source, [], null, $resolveInfo);

    expect($result)->toBe('my_value');
});

it('resolves single option field label when label argument is true', function () {
    $source = new stdClass;
    $source->myField = new SingleOptionFieldData(
        label: 'My Label',
        value: 'my_value',
        selected: true,
    );

    $resolveInfo = createResolveInfoForOptionField('myField');
    $result = OptionField::resolve($source, ['label' => true], null, $resolveInfo);

    expect($result)->toBe('My Label');
});

it('resolves multi option field values', function () {
    $source = new stdClass;
    $source->myField = new MultiOptionsFieldData([
        new OptionData(label: 'Label A', value: 'value_a', selected: true),
        new OptionData(label: 'Label B', value: 'value_b', selected: true),
    ]);

    $resolveInfo = createResolveInfoForOptionField('myField');
    $result = OptionField::resolve($source, [], null, $resolveInfo);

    expect($result)->toBe(['value_a', 'value_b']);
});

it('resolves multi option field labels when label argument is true', function () {
    $source = new stdClass;
    $source->myField = new MultiOptionsFieldData([
        new OptionData(label: 'Label A', value: 'value_a', selected: true),
        new OptionData(label: 'Label B', value: 'value_b', selected: true),
    ]);

    $resolveInfo = createResolveInfoForOptionField('myField');
    $result = OptionField::resolve($source, ['label' => true], null, $resolveInfo);

    expect($result)->toBe(['Label A', 'Label B']);
});

it('returns empty string when field data is neither single nor multi option', function () {
    $source = new stdClass;
    $source->myField = 'plain string';

    $resolveInfo = createResolveInfoForOptionField('myField');
    $result = OptionField::resolve($source, [], null, $resolveInfo);

    expect($result)->toBe('');
});

it('returns empty array for empty multi option field', function () {
    $source = new stdClass;
    $source->myField = new MultiOptionsFieldData([]);

    $resolveInfo = createResolveInfoForOptionField('myField');
    $result = OptionField::resolve($source, [], null, $resolveInfo);

    expect($result)->toBe([]);
});

it('resolves single option with null value', function () {
    $source = new stdClass;
    $source->myField = new SingleOptionFieldData(
        label: null,
        value: null,
        selected: false,
    );

    $resolveInfo = createResolveInfoForOptionField('myField');

    expect(OptionField::resolve($source, [], null, $resolveInfo))->toBeNull()
        ->and(OptionField::resolve($source, ['label' => true], null, $resolveInfo))->toBeNull();
});
