<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Volumes as VolumesService;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Data\MarkdownData;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Markdown as MarkdownField;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Markdown\Flavors\GfmFlavor;
use CraftCms\Cms\Markdown\Markdown as MarkdownService;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Volumes;
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
        MarkdownField::TOOLBAR_BOLD,
        MarkdownField::TOOLBAR_ITALIC,
        MarkdownField::TOOLBAR_STRIKETHROUGH,
        MarkdownField::TOOLBAR_CODE,
        MarkdownField::TOOLBAR_HEADING_1,
        MarkdownField::TOOLBAR_HEADING_2,
        MarkdownField::TOOLBAR_HEADING_3,
        MarkdownField::TOOLBAR_HEADING_4,
        MarkdownField::TOOLBAR_HEADING_5,
        MarkdownField::TOOLBAR_HEADING_6,
        MarkdownField::TOOLBAR_QUOTE,
        MarkdownField::TOOLBAR_UNORDERED_LIST,
        MarkdownField::TOOLBAR_ORDERED_LIST,
        MarkdownField::TOOLBAR_CHECK_LIST,
        MarkdownField::TOOLBAR_LINK,
        MarkdownField::TOOLBAR_ASSET,
        MarkdownField::TOOLBAR_PREVIEW,
        MarkdownField::TOOLBAR_GUIDE,
    )->not()->toContain('upload-image')
        ->and(Arr::pluck($toolbarOptions, 'value'))->not()->toContain(
            'heading',
            'heading-smaller',
            'heading-bigger',
            'clean-block',
            'image',
            'table',
            'horizontal-rule',
            'side-by-side',
            'fullscreen',
            'undo',
            'redo',
        )
        ->and(Arr::pluck($toolbarOptions, 'icon'))->not()->toContain(null);

    expect(MarkdownField::DEFAULT_TOOLBAR_BUTTONS)->not()->toContain(
        MarkdownField::TOOLBAR_HEADING_4,
        MarkdownField::TOOLBAR_HEADING_5,
        MarkdownField::TOOLBAR_HEADING_6,
    );

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

it('can disable the editor toolbar', function () {
    $field = new MarkdownField([
        'name' => 'Body',
        'handle' => 'body',
        'showToolbar' => false,
    ]);

    $html = $field->getInputHtml('Initial **markdown**', null);

    expect($field->validate())->toBeTrue()
        ->and($html)->not()->toContain('show-toolbar');
});

it('can show editor stats', function () {
    $field = new MarkdownField([
        'name' => 'Body',
        'handle' => 'body',
        'showStats' => true,
    ]);

    $html = $field->getInputHtml('Initial **markdown**', null);

    expect($field->validate())->toBeTrue()
        ->and($html)->toContain('show-stats')
        ->and($field->getSettingsHtml())->toContain('name="showStats"');
});

it('only shows toolbar button settings when the toolbar is enabled', function () {
    $settingsHtml = new MarkdownField(['showToolbar' => false])->getSettingsHtml();
    $enabledSettingsHtml = new MarkdownField(['showToolbar' => true])->getSettingsHtml();

    expect($settingsHtml)->toContain('data-target="toolbar-button-settings"')
        ->and($settingsHtml)->toContain('id="toolbar-button-settings" class="hidden"')
        ->and($enabledSettingsHtml)->not()->toContain('id="toolbar-button-settings" class="hidden"');
});

it('validates and applies asset selector volume settings', function () {
    $volume = Volume::factory()->create([
        'name' => 'Images',
        'fs' => 'disk:test-disk',
    ]);
    app()->forgetInstance(VolumesService::class);

    $field = new MarkdownField([
        'name' => 'Body',
        'handle' => 'body',
        'availableVolumes' => [$volume->uid],
        'showUnpermittedVolumes' => true,
        'showUnpermittedFiles' => true,
    ]);

    $assetSourceKeys = fn (): array => $this->assetSourceKeys();
    $assetSelectionCriteria = fn (): array => $this->assetSelectionCriteria();

    $field->validate();

    expect($field->errors()->toArray())->toBe([])
        ->and(Arr::pluck($field->volumeOptions(), 'value'))->toContain($volume->uid)
        ->and($assetSourceKeys->call($field))->toBe(["volume:$volume->uid"])
        ->and($assetSelectionCriteria->call($field))->toBe(['uploaderId' => null]);

    $invalidField = new MarkdownField([
        'name' => 'Body',
        'handle' => 'body',
        'availableVolumes' => ['missing'],
    ]);

    expect($invalidField->validate())->toBeFalse()
        ->and($invalidField->errors()->has('availableVolumes'))->toBeTrue();
});

it('warns and resolves no asset sources when no volumes exist', function () {
    Volumes::shouldReceive('getAllVolumes')
        ->andReturn(collect());

    $field = new MarkdownField([
        'name' => 'Body',
        'handle' => 'body',
        'toolbarButtons' => [MarkdownField::TOOLBAR_ASSET],
    ]);
    $assetSourceKeys = fn (): array => $this->assetSourceKeys();

    expect($assetSourceKeys->call($field))->toBe([])
        ->and($field->getSettingsHtml())->toContain('No volumes exist yet.');
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
