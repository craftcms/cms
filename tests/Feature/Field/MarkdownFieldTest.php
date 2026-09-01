<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Volumes as VolumesService;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Data\MarkdownData;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\Markdown as MarkdownField;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Markdown\Flavors\GfmFlavor;
use CraftCms\Cms\Markdown\Markdown as MarkdownService;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\HtmlSanitizers;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\View\TemplateManager;
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
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

function markdownFieldResolveInfo(string $fieldName): ResolveInfo
{
    $parentType = new ObjectType(['name' => 'Test', 'fields' => []]);

    $fieldDefinition = new FieldDefinition([
        'name' => $fieldName,
        'type' => Type::string(),
    ]);

    $fieldNode = new FieldNode([
        'name' => new NameNode(['value' => $fieldName]),
        'directives' => new NodeList([]),
    ]);

    return new ResolveInfo(
        $fieldDefinition,
        new ArrayObject($fieldNode),
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

    $field = new MarkdownField([
        'name' => 'Body',
        'handle' => 'body',
        'flavor' => 'missing',
    ]);

    expect($field->validate())->toBeFalse()
        ->and($field->errors()->has('flavor'))->toBeTrue();
});

it('encodes markdown when configured to encode html', function () {
    $field = new MarkdownField([
        'name' => 'Body',
        'handle' => 'body',
        'encode' => true,
    ]);

    expect($field->validate())->toBeTrue()
        ->and($field->normalizeValue('<b>**bold**</b>', null)->getHtml())->toBe("<p>&lt;b&gt;<strong>bold</strong>&lt;/b&gt;</p>\n");
});

it('can render inline-only markdown', function () {
    $field = new MarkdownField([
        'name' => 'Body',
        'handle' => 'body',
        'inlineOnly' => true,
    ]);

    expect($field->validate())->toBeTrue()
        ->and($field->normalizeValue("**bold**\nline", null)->getHtml())->toBe("<strong>bold</strong>\nline");
});

it('encodes markdown in field previews', function () {
    $field = new MarkdownField([
        'encode' => true,
    ]);

    expect($field->getPreviewHtml(new MarkdownData('<b>**bold**</b>', MarkdownService::FLAVOR_GFM), new Entry))
        ->toBe("<div class=\"markdown-field-preview\"><p>&lt;b&gt;<strong>bold</strong>&lt;/b&gt;</p>\n</div>");
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
        ->and($field->normalizeValue("one\ntwo", null)->getHtml())->toBe("<p>one<br />\ntwo</p>\n");
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

    expect($field->validate())->toBeTrue()
        ->and($field->formControl(new FieldContext('body'))->props()['showToolbar'])->toBeFalse();
});

it('defaults markdown link settings to link field defaults', function () {
    $field = new MarkdownField([
        'name' => 'Body',
        'handle' => 'body',
    ]);

    expect($field->linkSettingsTypes)->toBe((new Link)->types)
        ->and($field->linkSettingsShowLabelField)->toBeFalse()
        ->and($field->linkSettingsAdvancedFields)->toBe([]);
});

it('validates markdown link settings', function () {
    $field = new MarkdownField([
        'name' => 'Body',
        'handle' => 'body',
        'linkSettingsTypes' => ['entry', 'url'],
        'linkSettingsAdvancedFields' => ['urlSuffix', 'title'],
    ]);
    $invalidTypeField = new MarkdownField([
        'name' => 'Body',
        'handle' => 'body',
        'linkSettingsTypes' => ['missing'],
    ]);
    $invalidAdvancedField = new MarkdownField([
        'name' => 'Body',
        'handle' => 'body',
        'linkSettingsAdvancedFields' => ['target'],
    ]);

    expect($field->validate())->toBeTrue()
        ->and($invalidTypeField->validate())->toBeFalse()
        ->and($invalidTypeField->errors()->has('linkSettingsTypes'))->toBeTrue()
        ->and($invalidAdvancedField->validate())->toBeFalse()
        ->and($invalidAdvancedField->errors()->has('linkSettingsAdvancedFields'))->toBeTrue();
});

it('maps shared link settings to markdown link settings', function () {
    $field = new MarkdownField([
        'name' => 'Body',
        'handle' => 'body',
        'linkSettings' => [
            'types' => ['entry'],
            'typeSettings' => [
                'entry' => ['sources' => ['section:news']],
            ],
            'showLabelField' => true,
            'advancedFields' => ['title'],
        ],
    ]);

    expect($field->linkSettingsTypes)->toBe(['entry'])
        ->and($field->linkSettingsTypeSettings)->toBe([
            'entry' => ['sources' => ['section:news']],
        ])
        ->and($field->linkSettingsShowLabelField)->toBeTrue()
        ->and($field->linkSettingsAdvancedFields)->toBe(['title']);
});

it('prefers submitted nested link settings over persisted flat settings', function () {
    $field = new MarkdownField([
        'linkSettingsTypes' => ['url'],
        'linkSettingsShowLabelField' => false,
        'linkSettings' => [
            'types' => ['entry'],
            'showLabelField' => true,
        ],
    ]);

    expect($field->linkSettingsTypes)->toBe(['entry'])
        ->and($field->linkSettingsShowLabelField)->toBeTrue();
});

it('keeps supported markdown link advanced field settings', function () {
    $field = new MarkdownField([
        'name' => 'Body',
        'handle' => 'body',
        'linkSettingsAdvancedFields' => ['urlSuffix', 'title'],
    ]);

    expect($field->linkSettingsAdvancedFields)->toBe(['urlSuffix', 'title']);
});

it('can show editor stats', function () {
    $field = new MarkdownField([
        'name' => 'Body',
        'handle' => 'body',
        'showStats' => true,
    ]);

    expect($field->validate())->toBeTrue()
        ->and($field->showStats)->toBeTrue();
});

it('sanitizes rendered html with the configured html sanitizer', function () {
    HtmlSanitizers::extend('paragraphs-only', new HtmlSanitizer(
        (new HtmlSanitizerConfig)->allowElement('p')
    ));

    $field = new MarkdownField([
        'name' => 'Body',
        'handle' => 'body',
        'sanitizeHtml' => true,
        'htmlSanitizer' => 'paragraphs-only',
    ]);

    $value = $field->normalizeValueFromRequest('<p onclick="bad()">Hi</p><h1>Heading</h1>', null);
    $preview = new Crawler($field->getPreviewHtml($value, new Entry));
    expect($value->getHtml())->toBe("<p>Hi</p>\n")
        ->and($preview->filter('.markdown-field-preview > p')->text())->toBe('Hi')
        ->and($preview->filter('[onclick], h1')->count())->toBe(0);
});

it('preserves raw markdown syntax when html sanitization is enabled', function () {
    $field = new MarkdownField([
        'name' => 'Body',
        'handle' => 'body',
        'sanitizeHtml' => true,
    ]);

    $markdown = implode("\n", [
        '[Global Transit Overhaul Widens Washington]({entry:925@1:url})',
        '[Global Transit Overhaul Widens Washington]({entry:925@1:url}?foo "Foo")',
        '[Example.com](<https://example.com>)',
        '[Example.com](<https://example.com> "Example")',
        '[Foo](/some/url?foo="bar")',
        '[Foo](/some/url "A title")',
        "[Foo](/some/url 'A title')",
        '`inline code`',
        '```php',
        'echo "Hello world";',
        '```',
        '> A quote',
        '[Unknown]({unknown:925@1:url})',
        '[Unknown]({unknown:925@1:url}?foo "Foo")',
    ]);
    $value = $field->normalizeValueFromRequest($markdown, null);

    expect($field->validate())->toBeTrue()
        ->and($value->getRaw())->toBe($markdown);
});

it('can disable rendered html sanitization', function () {
    $field = new MarkdownField([
        'name' => 'Body',
        'handle' => 'body',
        'sanitizeHtml' => false,
    ]);
    $value = $field->normalizeValueFromRequest('<script>alert(1)</script>**bold**', null);

    expect($field->validate())->toBeTrue()
        ->and($value->getRaw())->toBe('<script>alert(1)</script>**bold**')
        ->and($value->getHtml())->toContain('<script>alert(1)</script>');
});

it('validates html sanitizer settings', function () {
    HtmlSanitizers::extend('paragraphs-only', new HtmlSanitizer(
        (new HtmlSanitizerConfig)->allowElement('p')
    ));

    $field = new MarkdownField([
        'name' => 'Body',
        'handle' => 'body',
        'htmlSanitizer' => 'paragraphs-only',
    ]);
    $invalidField = new MarkdownField([
        'name' => 'Body',
        'handle' => 'body',
        'htmlSanitizer' => 'missing',
    ]);
    $disabledField = new MarkdownField([
        'name' => 'Body',
        'handle' => 'body',
        'sanitizeHtml' => false,
        'htmlSanitizer' => 'missing',
    ]);
    expect($field->validate())->toBeTrue()
        ->and($invalidField->validate())->toBeFalse()
        ->and($invalidField->errors()->has('htmlSanitizer'))->toBeTrue()
        ->and($disabledField->validate())->toBeTrue();
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
    Volumes::shouldReceive('getAllVolumeIds')
        ->andReturn(collect());

    $field = new MarkdownField([
        'name' => 'Body',
        'handle' => 'body',
        'toolbarButtons' => [MarkdownField::TOOLBAR_ASSET],
    ]);
    $assetSourceKeys = fn (): array => $this->assetSourceKeys();

    expect($assetSourceKeys->call($field))->toBe([])
        ->and($field->validate())->toBeTrue();
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
    $searchKeywords = ($this->searchKeywords(...))->call($field, $value, new Entry);

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
        ->and($value->getHtml())->toBe("<p>line one<br />\nline two</p>\n");
});

it('renders as safe html in twig while raw markdown remains accessible', function () {
    $value = new MarkdownData('**bold**', MarkdownService::FLAVOR_GFM);
    $manager = app(TemplateManager::class);

    expect($manager->renderTwigString('{{ body }}', ['body' => $value], escapeHtml: true))->toBe("<p><strong>bold</strong></p>\n")
        ->and($manager->renderTwigString('{{ body.raw }}', ['body' => $value], escapeHtml: true))->toBe('**bold**');
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
        ->and($type['resolve']($source, [], null, markdownFieldResolveInfo('body')))->toBe("<p>line one<br />\nline two</p>\n")
        ->and($type['resolve']($source, ['raw' => true], null, markdownFieldResolveInfo('body')))->toBe("line one\nline two");
});

it('encodes markdown before resolving graphql html', function () {
    $field = new MarkdownField([
        'handle' => 'body',
        'encode' => true,
    ]);
    $type = $field->getContentGqlType();
    $source = ['body' => '<b>**bold**</b>'];

    expect($type['resolve']($source, [], null, markdownFieldResolveInfo('body')))->toBe("<p>&lt;b&gt;<strong>bold</strong>&lt;/b&gt;</p>\n")
        ->and($type['resolve']($source, ['raw' => true], null, markdownFieldResolveInfo('body')))->toBe('<b>**bold**</b>');
});

it('resolves inline-only markdown through graphql', function () {
    $field = new MarkdownField([
        'handle' => 'body',
        'inlineOnly' => true,
    ]);
    $type = $field->getContentGqlType();
    $source = ['body' => '**bold**'];

    expect($type['resolve']($source, [], null, markdownFieldResolveInfo('body')))->toBe('<strong>bold</strong>');
});

it('uses a raw markdown string as its graphql mutation input', function () {
    $field = new MarkdownField(['handle' => 'body']);

    expect($field->getContentGqlMutationArgumentType())->toMatchArray([
        'name' => 'body',
        'type' => Type::string(),
    ]);
});
