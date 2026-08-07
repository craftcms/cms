<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Closure;
use CraftCms\Cms\Asset\Data\Volume;
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
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Markdown as MarkdownControl;
use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Form\Nodes\Group;
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

    /** @var list<string> */
    public array $toolbarButtons = self::DEFAULT_TOOLBAR_BUTTONS;

    /** @var list<string> */
    public array $linkSettingsTypes = self::DEFAULT_LINK_TYPES;

    /** @var array<string, array<string, mixed>> */
    public array $linkSettingsTypeSettings = [];

    public bool $linkSettingsShowLabelField = false;

    /** @var list<string> */
    public array $linkSettingsAdvancedFields = [];

    public ?string $placeholder = null;

    public int $initialRows = 8;

    public ?int $charLimit = null;

    public ?int $byteLimit = null;

    /** @var list<string>|'*' */
    public array|string $availableVolumes = '*';

    public ?string $uploadVolume = null;

    public bool $showUnpermittedVolumes = false;

    public bool $showUnpermittedFiles = false;

    /** @param array<string, mixed> $config */
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
                $config['availableVolumes'] === ['*'] => '*',
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

    #[Override]
    public function formControl(FieldContext $context): Control
    {
        return MarkdownControl::make($context->path)
            ->rows($this->initialRows)
            ->placeholder($this->placeholder === null ? null : t($this->placeholder, category: 'site'))
            ->maxLength($this->charLimit)
            ->toolbarButtons($this->toolbarButtons)
            ->showToolbar($this->showToolbar)
            ->value($context->value instanceof MarkdownData ? $context->value->getRaw() : $context->value);
    }

    #[Override]
    public function settingsForm(FormContext $context = new FormContext): Form
    {
        $volumeOptions = $this->volumeOptions();

        return Form::make([
            FormField::make(t('Markdown Flavor'))
                ->instructions(t('The Markdown flavor that should be used when rendering this field.'))
                ->control(Choice::make('flavor')->options(self::flavorOptions())->value($this->flavor)),
            FormField::make(t('Inline Only'))
                ->instructions(t('Whether the field should only render inline Markdown, without wrapping paragraphs.'))
                ->control(Lightswitch::make('inlineOnly')->value($this->inlineOnly)),
            FormField::make(t('Show Toolbar'))
                ->instructions(t('Whether the editor toolbar should be visible.'))
                ->control(Lightswitch::make('showToolbar')->value($this->showToolbar)),
            FormField::make(t('Toolbar Buttons'))
                ->instructions(t('Choose which buttons should be available in the editor toolbar.'))
                ->control(Choice::make('toolbarButtons')
                    ->multiple()
                    ->options(array_map(fn (array $option): array => Arr::only($option, ['label', 'value']), self::toolbarButtonOptions()))
                    ->value($this->toolbarButtons)),
            FormField::make(t('Show Stats'))
                ->instructions(t('Whether the editor should show character, word, and line counts.'))
                ->control(Lightswitch::make('showStats')->value($this->showStats)),
            FormField::make(t('Placeholder Text'))
                ->instructions(t('The text that will be shown if the field doesn’t have a value.'))
                ->control(Text::make('placeholder')->value($this->placeholder)),
            FormField::make(t('Initial Rows'))
                ->control(Number::make('initialRows')->min(1)->value($this->initialRows)),
        ])->addGroup(t('Field Limit'), [
            FormField::make(t('Maximum'))
                ->instructions(t('The maximum number of characters or bytes the field is allowed to have.'))
                ->control(Number::make('fieldLimit')
                    ->min(1)
                    ->deltaGroupAtNamespace()
                    ->value($this->charLimit ?? $this->byteLimit)),
            FormField::make(t('Unit'))
                ->control(Choice::make('limitUnit')
                    ->deltaGroupAtNamespace()
                    ->options([
                        ['label' => t('Characters'), 'value' => 'chars'],
                        ['label' => t('Bytes'), 'value' => 'bytes'],
                    ])
                    ->value($this->byteLimit ? 'bytes' : 'chars')),
        ], 'markdown-field-limit')
            ->addGroup(t('Links'), fn (Group $group): Group => $group
                ->collapsible()
                ->add(...$this->linkSettingsNodes()), 'markdown-link-settings')
            ->addGroup(t('Assets'), fn (Group $group): Group => $group
                ->collapsible()
                ->add(
                    FormField::make(t('Available Volumes'))
                        ->instructions(t('The volumes that should be available when selecting assets.'))
                        ->control(Choice::make('availableVolumes')
                            ->multiple()
                            ->options([
                                ['label' => t('All'), 'value' => '*'],
                                ...$volumeOptions,
                            ])
                            ->value($this->availableVolumes === '*' ? ['*'] : $this->availableVolumes)),
                    FormField::make(t('Show unpermitted volumes'))
                        ->instructions(t('Whether to show volumes that the user doesn’t have permission to view.'))
                        ->control(Lightswitch::make('showUnpermittedVolumes')->value($this->showUnpermittedVolumes)),
                    FormField::make(t('Show unpermitted files'))
                        ->instructions(t('Whether to show files that the user doesn’t have permission to view, per the “View files uploaded by other users” permission.'))
                        ->control(Lightswitch::make('showUnpermittedFiles')->value($this->showUnpermittedFiles)),
                    FormField::make(t('Upload Volume'))
                        ->instructions(t('The volume where pasted or dropped files should be uploaded.'))
                        ->control(Choice::make('uploadVolume')->options([
                            ['label' => t('No uploads'), 'value' => ''],
                            ...$volumeOptions,
                        ])->value($this->uploadVolume)),
                ), 'markdown-asset-settings')
            ->addGroup(t('Advanced'), fn (Group $group): Group => $group
                ->collapsible()
                ->add(
                    FormField::make(t('Encode HTML'))
                        ->instructions(t('Whether HTML should be encoded before rendering the Markdown.'))
                        ->warning(t('Enabling this will enforce the Original Markdown flavor.'))
                        ->control(Lightswitch::make('encode')->value($this->encode)),
                    FormField::make(t('Sanitize HTML'))
                        ->instructions(t('Removes any potentially-malicious code on save, by running the submitted data through an HTML sanitizer.'))
                        ->warning(t('Disable this at your own risk!'))
                        ->control(Lightswitch::make('sanitizeHtml')->value($this->sanitizeHtml)),
                    FormField::make(t('HTML Sanitizer'))
                        ->control(Choice::make('htmlSanitizer')
                            ->options($this->htmlSanitizerOptions()->all())
                            ->value($this->htmlSanitizer ?? 'default')),
                ), 'markdown-advanced-settings');
    }

    /** @return list<array{label: string, value: string, icon: string}> */
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

    /** @return list<array{label: string, value: string}> */
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

    /** @return list<array{label: string, value: string}> */
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

    protected function linkSettingsNamespace(): ?string
    {
        return 'linkSettings';
    }

    /** @return list<string> */
    protected function supportedLinkAdvancedFields(): array
    {
        return self::SUPPORTED_LINK_ADVANCED_FIELDS;
    }

    /** @return Collection<int, array{label: string, value: string}> */
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

    /** @return list<string> */
    private function assetSourceKeys(): array
    {
        return $this->availableAssetVolumes()
            ->map(fn (Volume $volume) => "volume:$volume->uid")
            ->all();
    }

    /** @return array{}|array{uploaderId: null} */
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

    /** @return Collection<int, Volume> */
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

    /** @return list<Closure> */
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

    /**
     * @return array{
     *     name: string,
     *     type: Type,
     *     args: array{raw: array{name: string, type: Type, defaultValue: bool, description: string}},
     *     resolve: Closure(mixed, array{raw?: bool}, mixed, ResolveInfo): mixed,
     * }
     */
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
