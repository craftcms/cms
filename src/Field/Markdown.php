<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Closure;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Cp\FormDefinitions\Condition;
use CraftCms\Cms\Cp\FormDefinitions\Elements\CheckboxSelectInput;
use CraftCms\Cms\Cp\FormDefinitions\Elements\Group;
use CraftCms\Cms\Cp\FormDefinitions\Elements\LightswitchInput;
use CraftCms\Cms\Cp\FormDefinitions\Elements\NumberInput;
use CraftCms\Cms\Cp\FormDefinitions\Elements\SelectInput;
use CraftCms\Cms\Cp\FormDefinitions\Elements\TextInput;
use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Concerns\ProvidesLinkField;
use CraftCms\Cms\Field\Concerns\TracksReferences;
use CraftCms\Cms\Field\Conditions\TextFieldConditionRule;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Field\Contracts\TracksReferencesFieldInterface;
use CraftCms\Cms\Field\Data\MarkdownData;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Markdown\Markdown as MarkdownService;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\Facades\HtmlSanitizers;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Override;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class Markdown extends Field implements CrossSiteCopyableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface, SortableFieldInterface, TracksReferencesFieldInterface
{
    use ProvidesLinkField;
    use TracksReferences;

    public const string TOOLBAR_BOLD = 'bold';

    public const string TOOLBAR_ITALIC = 'italic';

    public const string TOOLBAR_STRIKETHROUGH = 'strikethrough';

    public const string TOOLBAR_HEADING_1 = 'h1';

    public const string TOOLBAR_HEADING_2 = 'h2';

    public const string TOOLBAR_HEADING_3 = 'h3';

    public const string TOOLBAR_HEADING_4 = 'h4';

    public const string TOOLBAR_HEADING_5 = 'h5';

    public const string TOOLBAR_HEADING_6 = 'h6';

    public const string TOOLBAR_QUOTE = 'quote';

    public const string TOOLBAR_CODE = 'code';

    public const string TOOLBAR_UNORDERED_LIST = 'unordered-list';

    public const string TOOLBAR_ORDERED_LIST = 'ordered-list';

    public const string TOOLBAR_CHECK_LIST = 'check-list';

    public const string TOOLBAR_LINK = 'link';

    public const string TOOLBAR_ASSET = 'asset';

    public const string TOOLBAR_PREVIEW = 'preview';

    public const string TOOLBAR_GUIDE = 'guide';

    public const array DEFAULT_TOOLBAR_BUTTONS = [
        self::TOOLBAR_BOLD,
        self::TOOLBAR_ITALIC,
        self::TOOLBAR_STRIKETHROUGH,
        self::TOOLBAR_CODE,
        self::TOOLBAR_HEADING_1,
        self::TOOLBAR_HEADING_2,
        self::TOOLBAR_HEADING_3,
        self::TOOLBAR_QUOTE,
        self::TOOLBAR_UNORDERED_LIST,
        self::TOOLBAR_ORDERED_LIST,
        self::TOOLBAR_CHECK_LIST,
        self::TOOLBAR_LINK,
        self::TOOLBAR_ASSET,
        self::TOOLBAR_PREVIEW,
        self::TOOLBAR_GUIDE,
    ];

    public const array DEFAULT_LINK_TYPES = [
        'entry',
        'url',
    ];

    public const array SUPPORTED_LINK_ADVANCED_FIELDS = [
        'urlSuffix',
        'title',
    ];

    public string $flavor = MarkdownService::FLAVOR_GFM;

    public bool $encode = false;

    public bool $inlineOnly = false;

    public bool $showToolbar = true;

    public bool $showStats = false;

    public bool $sanitizeHtml = true;

    public ?string $htmlSanitizer = null;

    public array $toolbarButtons = self::DEFAULT_TOOLBAR_BUTTONS;

    public array $linkSettingsTypes = self::DEFAULT_LINK_TYPES;

    public array $linkSettingsTypeSettings = [];

    public bool $linkSettingsShowLabelField = false;

    public array $linkSettingsAdvancedFields = [];

    public ?string $placeholder = null;

    public int $initialRows = 8;

    public ?int $charLimit = null;

    public ?int $byteLimit = null;

    public array|string $availableVolumes = '*';

    public ?string $uploadVolume = null;

    public bool $showUnpermittedVolumes = false;

    public bool $showUnpermittedFiles = false;

    public function __construct(array $config = [])
    {
        if (isset($config['limitUnit'], $config['fieldLimit'])) {
            if ($config['limitUnit'] === 'chars') {
                $config['charLimit'] = (int) $config['fieldLimit'] ?: null;
            } else {
                $config['byteLimit'] = (int) $config['fieldLimit'] ?: null;
            }
        }

        if (array_key_exists('toolbarButtons', $config)) {
            $config['toolbarButtons'] = match (true) {
                $config['toolbarButtons'] === null,
                $config['toolbarButtons'] === '' => [],
                is_array($config['toolbarButtons']) => $config['toolbarButtons'],
                default => [$config['toolbarButtons']],
            };
        }

        $config = $this->prepareLinkSettingsConfig($config);

        if (array_key_exists('availableVolumes', $config)) {
            $config['availableVolumes'] = match (true) {
                $config['availableVolumes'] === '*',
                is_array($config['availableVolumes']) => $config['availableVolumes'],
                $config['availableVolumes'] === null,
                $config['availableVolumes'] === '' => [],
                default => [$config['availableVolumes']],
            };
        }

        if (($config['uploadVolume'] ?? null) === '') {
            $config['uploadVolume'] = null;
        }

        if (($config['htmlSanitizer'] ?? null) === '') {
            $config['htmlSanitizer'] = null;
        }

        unset(
            $config['limitUnit'],
            $config['fieldLimit'],
            $config['maxLengthUnit'],
            $config['columnType'],
        );

        parent::__construct($config);

        if (isset($this->placeholder)) {
            $this->placeholder = Str::shortcodesToEmoji($this->placeholder);
        }
    }

    #[Override]
    public static function displayName(): string
    {
        return t('Markdown');
    }

    #[Override]
    public static function icon(): string
    {
        return 'markdown';
    }

    #[Override]
    public static function phpType(): string
    {
        return sprintf('\\%s|null', MarkdownData::class);
    }

    public static function toolbarButtonOptions(): array
    {
        return [
            ['label' => t('Bold'), 'value' => self::TOOLBAR_BOLD, 'icon' => 'bold'],
            ['label' => t('Italic'), 'value' => self::TOOLBAR_ITALIC, 'icon' => 'italic'],
            ['label' => t('Strikethrough'), 'value' => self::TOOLBAR_STRIKETHROUGH, 'icon' => 'strikethrough'],
            ['label' => t('Code'), 'value' => self::TOOLBAR_CODE, 'icon' => 'code'],
            ['label' => t('Heading 1'), 'value' => self::TOOLBAR_HEADING_1, 'icon' => 'h1'],
            ['label' => t('Heading 2'), 'value' => self::TOOLBAR_HEADING_2, 'icon' => 'h2'],
            ['label' => t('Heading 3'), 'value' => self::TOOLBAR_HEADING_3, 'icon' => 'h3'],
            ['label' => t('Heading 4'), 'value' => self::TOOLBAR_HEADING_4, 'icon' => 'h4'],
            ['label' => t('Heading 5'), 'value' => self::TOOLBAR_HEADING_5, 'icon' => 'h5'],
            ['label' => t('Heading 6'), 'value' => self::TOOLBAR_HEADING_6, 'icon' => 'h6'],
            ['label' => t('Quote'), 'value' => self::TOOLBAR_QUOTE, 'icon' => 'quotes-left'],
            ['label' => t('Bulleted List'), 'value' => self::TOOLBAR_UNORDERED_LIST, 'icon' => 'list-ul'],
            ['label' => t('Numbered List'), 'value' => self::TOOLBAR_ORDERED_LIST, 'icon' => 'list-ol'],
            ['label' => t('Check List'), 'value' => self::TOOLBAR_CHECK_LIST, 'icon' => 'list-check'],
            ['label' => t('Link'), 'value' => self::TOOLBAR_LINK, 'icon' => 'link'],
            ['label' => t('Asset'), 'value' => self::TOOLBAR_ASSET, 'icon' => 'paperclip'],
            ['label' => t('Preview'), 'value' => self::TOOLBAR_PREVIEW, 'icon' => 'eye'],
            ['label' => t('Markdown Guide'), 'value' => self::TOOLBAR_GUIDE, 'icon' => 'circle-question'],
        ];
    }

    public static function flavorOptions(): array
    {
        $labels = [
            MarkdownService::FLAVOR_ORIGINAL => t('Original'),
            MarkdownService::FLAVOR_GFM => t('GitHub-Flavored Markdown'),
            MarkdownService::FLAVOR_GFM_COMMENT => t('GitHub-Flavored Markdown Comment'),
            MarkdownService::FLAVOR_EXTRA => t('Markdown Extra'),
        ];

        return collect(app(MarkdownService::class)->flavors())
            ->reject(fn (string $flavor) => $flavor === MarkdownService::FLAVOR_PRE_ENCODED)
            ->map(fn (string $flavor) => [
                'label' => $labels[$flavor] ?? $flavor,
                'value' => $flavor,
            ])
            ->values()
            ->all();
    }

    public function volumeOptions(): array
    {
        return Volumes::getAllVolumes()
            ->map(fn (Volume $volume) => [
                'label' => $volume->name,
                'value' => $volume->uid,
            ])
            ->values()
            ->all();
    }

    #[Override]
    public function getSettings(): array
    {
        $settings = parent::getSettings();
        if (isset($settings['placeholder']) && ! DB::supportsMb4()) {
            $settings['placeholder'] = Str::emojiToShortcodes($settings['placeholder']);
        }

        return $settings;
    }

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'flavor' => ['required', Rule::in(Arr::pluck(self::flavorOptions(), 'value'))],
            'encode' => ['boolean'],
            'inlineOnly' => ['boolean'],
            'showToolbar' => ['boolean'],
            'showStats' => ['boolean'],
            'sanitizeHtml' => ['boolean'],
            'htmlSanitizer' => [
                'nullable',
                function ($attribute, mixed $value, Closure $fail) {
                    if (! $this->sanitizeHtml || $value === null || in_array($value, $this->htmlSanitizerOptions()->pluck('value')->all(), true)) {
                        return;
                    }

                    $fail(t('Invalid HTML sanitizer.'));
                },
            ],
            'toolbarButtons' => [
                'array',
                function ($attribute, mixed $value, Closure $fail) {
                    foreach ($value as $button) {
                        if (! is_string($button) || ! in_array($button, Arr::pluck(self::toolbarButtonOptions(), 'value'), true)) {
                            $fail(t('Invalid toolbar button.'));

                            return;
                        }
                    }
                },
            ],
            'initialRows' => ['nullable', 'integer', 'min:1'],
            'charLimit' => ['nullable', 'integer', 'min:1'],
            'byteLimit' => ['nullable', 'integer', 'min:1'],
            'uploadVolume' => ['nullable', Rule::in(Arr::pluck($this->volumeOptions(), 'value'))],
            'availableVolumes' => [
                function ($attribute, mixed $value, Closure $fail) {
                    if ($value === '*') {
                        return;
                    }

                    if (! is_array($value)) {
                        $fail(t('Invalid volumes.'));

                        return;
                    }

                    foreach ($value as $volumeUid) {
                        if (! is_string($volumeUid) || ! in_array($volumeUid, Arr::pluck($this->volumeOptions(), 'value'), true)) {
                            $fail(t('Invalid volumes.'));

                            return;
                        }
                    }
                },
            ],
            'showUnpermittedVolumes' => ['boolean'],
            'showUnpermittedFiles' => ['boolean'],
        ], $this->linkSettingsRules());
    }

    public function getSettingsHtml(): string
    {
        return $this->settingsHtml(false);
    }

    #[Override]
    public function getReadOnlySettingsHtml(): string
    {
        return $this->settingsHtml(true);
    }

    #[Override]
    public function getSettingsFormDefinition(bool $readOnly): FormDefinition
    {
        $volumeOptions = $this->volumeOptions();
        $availableVolumeOptions = $volumeOptions;
        $availableVolumes = CheckboxSelectInput::make('availableVolumes')
            ->label(t('Available Volumes'))
            ->instructions(t('The volumes that should be available when selecting assets.'))
            ->readOnly($readOnly);

        if ($availableVolumeOptions === []) {
            $availableVolumes
                ->warning(t('No volumes exist yet.'))
                ->readOnly();
        } else {
            array_unshift($availableVolumeOptions, ['label' => t('All'), 'value' => '*']);
            $availableVolumes
                ->options($availableVolumeOptions)
                ->allOption('*');
        }

        return FormDefinition::make([
            SelectInput::make('flavor')
                ->label(t('Markdown Flavor'))
                ->instructions(t('The Markdown flavor that should be used when rendering this field.'))
                ->options(self::flavorOptions())
                ->visibleWhen(Condition::equals('encode', false))
                ->readOnly($readOnly),
            LightswitchInput::make('inlineOnly')
                ->label(t('Inline Only'))
                ->instructions(t('Whether the field should only render inline Markdown, without wrapping paragraphs.'))
                ->readOnly($readOnly),
            LightswitchInput::make('showToolbar')
                ->label(t('Show Toolbar'))
                ->instructions(t('Whether the editor toolbar should be visible.'))
                ->readOnly($readOnly),
            CheckboxSelectInput::make('toolbarButtons')
                ->label(t('Toolbar Buttons'))
                ->instructions(t('Choose which buttons should be available in the editor toolbar.'))
                ->options(self::toolbarButtonOptions())
                ->visibleWhen(Condition::equals('showToolbar', true))
                ->readOnly($readOnly),
            LightswitchInput::make('showStats')
                ->label(t('Show Stats'))
                ->instructions(t('Whether the editor should show character, word, and line counts.'))
                ->readOnly($readOnly),
            TextInput::make('placeholder')
                ->label(t('Placeholder Text'))
                ->instructions(t('The text that will be shown if the field doesn’t have a value.'))
                ->readOnly($readOnly),
            NumberInput::make('initialRows')
                ->label(t('Initial Rows'))
                ->min(1)
                ->readOnly($readOnly),
            NumberInput::make('charLimit')
                ->label(t('Character Limit'))
                ->instructions(t('The maximum number of characters the field is allowed to have.'))
                ->min(1)
                ->readOnly($readOnly),
            NumberInput::make('byteLimit')
                ->label(t('Byte Limit'))
                ->instructions(t('The maximum number of bytes the field is allowed to have.'))
                ->min(1)
                ->readOnly($readOnly),
            Group::fromDefinition(
                FormDefinition::make($this->linkSettingsFormElements($readOnly)),
                'linkSettings',
            )->key('link-settings'),
            $availableVolumes,
            LightswitchInput::make('showUnpermittedVolumes')
                ->label(t('Show unpermitted volumes'))
                ->instructions(t('Whether to show volumes that the user doesn’t have permission to view.'))
                ->readOnly($readOnly),
            LightswitchInput::make('showUnpermittedFiles')
                ->label(t('Show unpermitted files'))
                ->instructions(t('Whether to show files that the user doesn’t have permission to view, per the “View files uploaded by other users” permission.'))
                ->readOnly($readOnly),
            ...($volumeOptions === [] ? [] : [
                SelectInput::make('uploadVolume')
                    ->label(t('Upload Volume'))
                    ->instructions(t('The volume where pasted or dropped files should be uploaded.'))
                    ->options([
                        ['label' => t('No uploads'), 'value' => ''],
                        ...$volumeOptions,
                    ])
                    ->readOnly($readOnly),
            ]),
            LightswitchInput::make('encode')
                ->label(t('Encode HTML'))
                ->instructions(t('Whether HTML should be encoded before rendering the Markdown.'))
                ->warning(t('Enabling this will enforce the Original Markdown flavor.'))
                ->readOnly($readOnly),
            LightswitchInput::make('sanitizeHtml')
                ->label(t('Sanitize HTML'))
                ->instructions(t('Removes any potentially-malicious code on save, by running the submitted data through an HTML sanitizer.'))
                ->warning(t('Disable this at your own risk!'))
                ->readOnly($readOnly),
            SelectInput::make('htmlSanitizer')
                ->label(t('HTML Sanitizer'))
                ->instructions(t('You can register custom HTML sanitizers as {ext} files in {path}.', [
                    'ext' => '`.php`',
                    'path' => '`config/craft/sanitizers/`',
                ]))
                ->options($this->htmlSanitizerOptions()->all())
                ->visibleWhen(Condition::equals('sanitizeHtml', true))
                ->readOnly($readOnly),
        ]);
    }

    private function settingsHtml(bool $readOnly): string
    {
        return template('_components/fieldtypes/Markdown/settings', [
            'field' => $this,
            'flavorOptions' => self::flavorOptions(),
            'htmlSanitizerOptions' => $this->htmlSanitizerOptions(),
            'linkSettings' => $this->linkSettingsProps($readOnly),
            'toolbarButtonOptions' => self::toolbarButtonOptions(),
            'volumeOptions' => $this->volumeOptions(),
            'readOnly' => $readOnly,
        ]);
    }

    protected function linkSettingsNamespace(): ?string
    {
        return 'linkSettings';
    }

    protected function supportedLinkAdvancedFields(): array
    {
        return self::SUPPORTED_LINK_ADVANCED_FIELDS;
    }

    private function htmlSanitizerOptions(): Collection
    {
        return collect(HtmlSanitizers::names())
            ->map(fn (string $name) => [
                'label' => $name === 'default' ? t('Default') : $name,
                'value' => $name,
            ])
            ->values();
    }

    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): ?MarkdownData
    {
        return $this->normalizeMarkdownValue($value, false);
    }

    #[Override]
    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): ?MarkdownData
    {
        return $this->normalizeMarkdownValue($value, true);
    }

    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->input($value, $element, $inline, false);
    }

    #[Override]
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return $this->input($value, $element, false, true);
    }

    private function input(mixed $value, ?ElementInterface $element, bool $inline, bool $static): string
    {
        $id = $this->getInputId();
        $placeholder = $this->placeholder !== null
            ? t(Str::unescapeShortcodes($this->placeholder), category: 'site')
            : null;
        $assetSourceKeys = $this->assetSourceKeys();
        $settings = [
            'assetCriteria' => $this->assetSelectionCriteria(),
            'assetSources' => $assetSourceKeys,
            'describedBy' => $this->describedBy === null ? null : collect(explode(' ', $this->describedBy))
                ->map(fn (string $id) => InputNamespace::namespaceId($id))
                ->implode(' '),
            'disabled' => $static,
            'encode' => $this->encode,
            'flavor' => $this->flavor,
            'inlineOnly' => $this->inlineOnly,
            'linkAdvancedFields' => $this->linkSettingsAdvancedFields,
            'linkTypes' => $this->linkPickerConfig(),
            'maxLength' => $static ? null : $this->charLimit,
            'placeholder' => $placeholder,
            'rows' => $this->initialRows,
            'sanitizeHtml' => $this->sanitizeHtml,
            'htmlSanitizer' => $this->htmlSanitizer,
            'showLinkLabelField' => $this->linkSettingsShowLabelField,
            'showStats' => $this->showStats,
            'showToolbar' => $this->showToolbar && ! $inline && ! $static,
            'toolbarButtons' => $assetSourceKeys === []
                ? array_values(array_diff($this->toolbarButtons, [self::TOOLBAR_ASSET]))
                : $this->toolbarButtons,
            'uploadFolderId' => $static ? null : $this->uploadFolderId(),
            'uploadSiteId' => $element->siteId ?? Sites::getCurrentSite()->id,
            'value' => $this->rawValue($value),
        ];

        return template('_components/fieldtypes/Markdown/input', [
            'id' => $id,
            'name' => $this->handle,
            'disabled' => $static,
            'settings' => $settings,
        ]);
    }

    private function assetSourceKeys(): array
    {
        return $this->availableAssetVolumes()
            ->map(fn (Volume $volume) => "volume:$volume->uid")
            ->all();
    }

    private function assetSelectionCriteria(): array
    {
        return $this->showUnpermittedFiles ? ['uploaderId' => null] : [];
    }

    private function uploadFolderId(): ?int
    {
        $volume = $this->uploadVolume();

        if (! $volume || ! Gate::check("saveAssets:$volume->uid")) {
            return null;
        }

        return Folders::getRootFolderByVolumeId($volume->id)?->id;
    }

    private function uploadVolume(): ?Volume
    {
        if (! $this->uploadVolume) {
            return null;
        }

        return Volumes::getVolumeByUid($this->uploadVolume);
    }

    private function availableAssetVolumes(): Collection
    {
        $volumes = Volumes::getAllVolumes();

        if ($this->availableVolumes !== '*') {
            $volumes = $volumes->filter(fn (Volume $volume) => in_array($volume->uid, $this->availableVolumes, true));
        }

        if (! $this->showUnpermittedVolumes) {
            $volumes = $volumes->filter(fn (Volume $volume) => Gate::check("viewAssets:$volume->uid"));
        }

        return $volumes->values();
    }

    #[Override]
    public function getElementRules(ElementInterface $element): array
    {
        return [
            function ($attribute, mixed $value, Closure $fail) {
                $value = $this->rawValue($value);

                if ($value === null) {
                    return;
                }

                if ($this->byteLimit && mb_strlen($value, '8bit') > $this->byteLimit) {
                    $fail(t('{attribute} should contain at most {max, number} {max, plural, one{character} other{characters}}.', [
                        'attribute' => $this->getUiLabel(),
                        'max' => $this->byteLimit,
                    ]));

                    return;
                }

                if ($this->charLimit && mb_strlen($value) > $this->charLimit) {
                    $fail(t('{attribute} should contain at most {max, number} {max, plural, one{character} other{characters}}.', [
                        'attribute' => $this->getUiLabel(),
                        'max' => $this->charLimit,
                    ]));
                }
            },
        ];
    }

    #[Override]
    public function serializeValue(mixed $value, ?ElementInterface $element): ?string
    {
        $value = $this->rawValue($value);

        if ($value === null) {
            return null;
        }

        $value = Str::escapeShortcodes($value);

        if (! DB::supportsMb4()) {
            return Str::emojiToShortcodes($value);
        }

        return $value;
    }

    #[Override]
    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        return $this->rawValue($value) === null;
    }

    #[Override]
    protected function searchKeywords(mixed $value, ElementInterface $element): string
    {
        return $this->rawValue($value) ?? '';
    }

    public function getElementConditionRuleType(): string
    {
        return TextFieldConditionRule::class;
    }

    #[Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        $raw = $this->rawValue($value);
        $html = $raw === null ? '' : $this->markdownData($raw)->getHtml();

        return Html::tag('div', $html, [
            'class' => 'markdown-field-preview',
        ]);
    }

    #[Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (! $value) {
            $value = '**Markdown**';
        }

        return $this->getPreviewHtml($value, $element ?? new Entry);
    }

    #[Override]
    public function getContentGqlType(): array
    {
        return [
            'name' => $this->handle,
            'type' => Type::string(),
            'args' => [
                'raw' => [
                    'name' => 'raw',
                    'type' => Type::boolean(),
                    'defaultValue' => false,
                    'description' => 'Whether to return the stored Markdown instead of rendered HTML.',
                ],
            ],
            'resolve' => function (mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed {
                $fieldName = GqlHelper::getFieldNameWithAlias($resolveInfo, $source, $context);
                $value = null;

                if (is_object($source) && method_exists($source, 'getFieldValue')) {
                    $value = $source->getFieldValue($fieldName);
                } elseif (is_array($source)) {
                    $value = $source[$fieldName] ?? null;
                }

                $raw = $this->rawValue($value);
                $resolved = ($arguments['raw'] ?? false) || $raw === null
                    ? $raw
                    : $this->markdownData($raw)->getHtml();

                return GqlHelper::applyDirectives($source, $resolveInfo, $resolved);
            },
        ];
    }

    private function normalizeMarkdownValue(mixed $value, bool $fromRequest): ?MarkdownData
    {
        if ($value instanceof MarkdownData) {
            return $this->markdownData($value->getRaw());
        }

        if ($value === null) {
            return null;
        }

        if (! $fromRequest) {
            $value = Str::unescapeShortcodes(Str::shortcodesToEmoji((string) $value));
        }

        $value = Str::convertLineBreaks((string) $value);

        if (trim($value) === '') {
            return null;
        }

        return $this->markdownData($value);
    }

    private function markdownData(string $value): MarkdownData
    {
        return new MarkdownData(
            $value,
            $this->encode ? MarkdownService::FLAVOR_PRE_ENCODED : $this->flavor,
            encode: $this->encode,
            inlineOnly: $this->inlineOnly,
            sanitizeHtml: $this->sanitizeHtml,
            htmlSanitizer: $this->htmlSanitizer,
        );
    }

    private function rawValue(mixed $value): ?string
    {
        if ($value instanceof MarkdownData) {
            return $value->getRaw();
        }

        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
