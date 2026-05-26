<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Data\MarkdownData;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Markdown as MarkdownField;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Markdown\Flavors\GfmFlavor;
use CraftCms\Cms\Markdown\Markdown as MarkdownService;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Twig\TemplateRenderer;
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

function markdownFieldResolveInfo(string $fieldName): ResolveInfo
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
        [$fieldName],
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

it('registers as a native field type compatible with plain text', function () {
    $fields = app(Fields::class);

    expect($fields->getAllFieldTypes())->toContain(MarkdownField::class)
        ->and($fields->getCompatibleFieldTypes(new PlainText))->toContain(MarkdownField::class)
        ->and($fields->getCompatibleFieldTypes(new MarkdownField))->toContain(PlainText::class);
});

it('validates supported authoring flavors', function () {
    expect(Arr::pluck(MarkdownField::flavorOptions(), 'value'))->toContain(
        MarkdownService::FLAVOR_ORIGINAL,
        MarkdownService::FLAVOR_GFM,
        MarkdownService::FLAVOR_GFM_COMMENT,
        MarkdownService::FLAVOR_EXTRA,
    )->not()->toContain(MarkdownService::FLAVOR_PRE_ENCODED);

    $field = new MarkdownField(['flavor' => MarkdownService::FLAVOR_PRE_ENCODED]);

    expect($field->validate())->toBeFalse()
        ->and($field->errors()->has('flavor'))->toBeTrue();
});

it('allows extended markdown flavors in field settings', function () {
    app(MarkdownService::class)->extend('custom-field-flavor', new GfmFlavor("<br>\n"));

    $field = new MarkdownField([
        'name' => 'Body',
        'handle' => 'body',
        'flavor' => 'custom-field-flavor',
    ]);

    expect(Arr::pluck(MarkdownField::flavorOptions(), 'value'))->toContain('custom-field-flavor')
        ->and($field->validate())->toBeTrue()
        ->and($field->normalizeValue("one\ntwo", null)->getHtml())->toBe("<p>one<br>\ntwo</p>\n");
});

it('validates configurable toolbar buttons', function () {
    $toolbarOptions = MarkdownField::toolbarButtonOptions();

    expect(Arr::pluck($toolbarOptions, 'value'))->toContain(
        MarkdownField::TOOLBAR_STRIKETHROUGH,
        MarkdownField::TOOLBAR_HEADING_SMALLER,
        MarkdownField::TOOLBAR_HEADING_BIGGER,
        MarkdownField::TOOLBAR_HEADING_1,
        MarkdownField::TOOLBAR_HEADING_2,
        MarkdownField::TOOLBAR_HEADING_3,
        MarkdownField::TOOLBAR_CHECK_LIST,
        MarkdownField::TOOLBAR_CLEAN_BLOCK,
        MarkdownField::TOOLBAR_HORIZONTAL_RULE,
        MarkdownField::TOOLBAR_GUIDE,
        MarkdownField::TOOLBAR_UNDO,
        MarkdownField::TOOLBAR_REDO,
    )->not()->toContain('upload-image')
        ->and(Arr::pluck($toolbarOptions, 'icon'))->not()->toContain(null);

    $field = new MarkdownField([
        'toolbarButtons' => [
            MarkdownField::TOOLBAR_BOLD,
            'missing',
        ],
    ]);

    expect($field->validate())->toBeFalse()
        ->and($field->errors()->has('toolbarButtons'))->toBeTrue()
        ->and(new MarkdownField(['toolbarButtons' => ''])->toolbarButtons)->toBe([]);
});

it('normalizes values without trimming markdown-significant whitespace', function () {
    $field = new MarkdownField(['flavor' => MarkdownService::FLAVOR_GFM]);
    $value = $field->normalizeValue("  indented\ntrailing  ", null);

    expect($value)->toBeInstanceOf(MarkdownData::class)
        ->and($value->getRaw())->toBe("  indented\ntrailing  ")
        ->and($field->normalizeValue(" \n\t ", null))->toBeNull()
        ->and($field->normalizeValueFromRequest("line\r\nline", null)->getRaw())->toBe("line\nline");
});

it('serializes and searches raw markdown', function () {
    $field = new MarkdownField(['flavor' => MarkdownService::FLAVOR_GFM]);
    $value = new MarkdownData('**raw**', MarkdownService::FLAVOR_GFM);
    $searchKeywords = (fn (mixed $value, Entry $element): string => $this->searchKeywords($value, $element))->call($field, $value, new Entry);

    expect($field->serializeValue($value, null))->toBe('**raw**')
        ->and($field->serializeValue(null, null))->toBeNull()
        ->and($searchKeywords)->toBe('**raw**');
});

it('enforces char and byte limits against raw markdown', function (array $settings, string $value) {
    $result = EntryModel::factory()
        ->withField('body', MarkdownField::class, $settings, value: $value)
        ->createElementWithFields(save: false);

    $result->element->validate();

    expect($result->element->errors()->has('body'))->toBeTrue();
})->with([
    'char limit' => [['charLimit' => 5], 'abcdef'],
    'byte limit' => [['byteLimit' => 4], 'ééé'],
]);

it('saves and retrieves markdown field values as markdown data', function () {
    $result = EntryModel::factory()
        ->withField('body', MarkdownField::class, [
            'flavor' => MarkdownService::FLAVOR_GFM_COMMENT,
        ], value: "line one\nline two")
        ->createElementWithFields();

    $value = $result->element->getFieldValue('body');

    expect($value)->toBeInstanceOf(MarkdownData::class)
        ->and($value->getRaw())->toBe("line one\nline two")
        ->and($value->getHtml())->toBe("<p>line one<br>\nline two</p>\n");
});

it('renders as safe html in twig while raw markdown remains accessible', function () {
    $value = new MarkdownData('**bold**', MarkdownService::FLAVOR_GFM);
    $renderer = app(TemplateRenderer::class);

    expect($renderer->renderString('{{ body }}', ['body' => $value], escapeHtml: true))->toBe("<p><strong>bold</strong></p>\n")
        ->and($renderer->renderString('{{ body.raw }}', ['body' => $value], escapeHtml: true))->toBe('**bold**');
});

it('returns rendered html by default and raw markdown when requested through graphql', function () {
    $field = new MarkdownField([
        'handle' => 'body',
        'flavor' => MarkdownService::FLAVOR_GFM_COMMENT,
    ]);
    $type = $field->getContentGqlType();
    $source = new class
    {
        public function getFieldValue(string $fieldName): MarkdownData
        {
            expect($fieldName)->toBe('body');

            return new MarkdownData("line one\nline two", MarkdownService::FLAVOR_GFM_COMMENT);
        }
    };

    expect($type['args']['raw']['defaultValue'])->toBeFalse()
        ->and($type['resolve']($source, [], null, markdownFieldResolveInfo('body')))->toBe("<p>line one<br>\nline two</p>\n")
        ->and($type['resolve']($source, ['raw' => true], null, markdownFieldResolveInfo('body')))->toBe("line one\nline two");
});

it('uses a raw markdown string as its graphql mutation input', function () {
    $field = new MarkdownField(['handle' => 'body']);

    expect($field->getContentGqlMutationArgumentType())->toMatchArray([
        'name' => 'body',
        'type' => Type::string(),
    ]);
});
