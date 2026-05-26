<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Closure;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Conditions\TextFieldConditionRule;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Field\Data\MarkdownData;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Markdown\Markdown as MarkdownService;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use CraftCms\Cms\View\LegacyAssets\MarkdownFieldAsset;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Override;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class Markdown extends Field implements CrossSiteCopyableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface, SortableFieldInterface
{
    public const string TOOLBAR_BOLD = 'bold';

    public const string TOOLBAR_ITALIC = 'italic';

    public const string TOOLBAR_STRIKETHROUGH = 'strikethrough';

    public const string TOOLBAR_HEADING = 'heading';

    public const string TOOLBAR_HEADING_SMALLER = 'heading-smaller';

    public const string TOOLBAR_HEADING_BIGGER = 'heading-bigger';

    public const string TOOLBAR_HEADING_1 = 'heading-1';

    public const string TOOLBAR_HEADING_2 = 'heading-2';

    public const string TOOLBAR_HEADING_3 = 'heading-3';

    public const string TOOLBAR_QUOTE = 'quote';

    public const string TOOLBAR_CODE = 'code';

    public const string TOOLBAR_UNORDERED_LIST = 'unordered-list';

    public const string TOOLBAR_ORDERED_LIST = 'ordered-list';

    public const string TOOLBAR_CHECK_LIST = 'check-list';

    public const string TOOLBAR_CLEAN_BLOCK = 'clean-block';

    public const string TOOLBAR_LINK = 'link';

    public const string TOOLBAR_ASSET = 'asset';

    public const string TOOLBAR_IMAGE = 'image';

    public const string TOOLBAR_TABLE = 'table';

    public const string TOOLBAR_HORIZONTAL_RULE = 'horizontal-rule';

    public const string TOOLBAR_PREVIEW = 'preview';

    public const string TOOLBAR_SIDE_BY_SIDE = 'side-by-side';

    public const string TOOLBAR_FULLSCREEN = 'fullscreen';

    public const string TOOLBAR_GUIDE = 'guide';

    public const string TOOLBAR_UNDO = 'undo';

    public const string TOOLBAR_REDO = 'redo';

    public const array DEFAULT_TOOLBAR_BUTTONS = [
        self::TOOLBAR_BOLD,
        self::TOOLBAR_ITALIC,
        self::TOOLBAR_HEADING,
        self::TOOLBAR_QUOTE,
        self::TOOLBAR_CODE,
        self::TOOLBAR_UNORDERED_LIST,
        self::TOOLBAR_ORDERED_LIST,
        self::TOOLBAR_LINK,
        self::TOOLBAR_ASSET,
        self::TOOLBAR_IMAGE,
        self::TOOLBAR_TABLE,
        self::TOOLBAR_PREVIEW,
        self::TOOLBAR_SIDE_BY_SIDE,
        self::TOOLBAR_FULLSCREEN,
    ];

    public string $flavor = MarkdownService::FLAVOR_GFM;

    public array $toolbarButtons = self::DEFAULT_TOOLBAR_BUTTONS;

    public ?string $placeholder = null;

    public int $initialRows = 8;

    public ?int $charLimit = null;

    public ?int $byteLimit = null;

    public array|string $availableVolumes = '*';

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

        if (array_key_exists('availableVolumes', $config)) {
            $config['availableVolumes'] = match (true) {
                $config['availableVolumes'] === '*',
                is_array($config['availableVolumes']) => $config['availableVolumes'],
                $config['availableVolumes'] === null,
                $config['availableVolumes'] === '' => [],
                default => [$config['availableVolumes']],
            };
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
            ['label' => t('Heading'), 'value' => self::TOOLBAR_HEADING, 'icon' => 'heading'],
            ['label' => t('Smaller Heading'), 'value' => self::TOOLBAR_HEADING_SMALLER, 'icon' => 'heading'],
            ['label' => t('Bigger Heading'), 'value' => self::TOOLBAR_HEADING_BIGGER, 'icon' => 'heading'],
            ['label' => t('Big Heading'), 'value' => self::TOOLBAR_HEADING_1, 'icon' => 'heading'],
            ['label' => t('Medium Heading'), 'value' => self::TOOLBAR_HEADING_2, 'icon' => 'heading'],
            ['label' => t('Small Heading'), 'value' => self::TOOLBAR_HEADING_3, 'icon' => 'heading'],
            ['label' => t('Quote'), 'value' => self::TOOLBAR_QUOTE, 'icon' => 'quotes-left'],
            ['label' => t('Code'), 'value' => self::TOOLBAR_CODE, 'icon' => 'code'],
            ['label' => t('Bulleted List'), 'value' => self::TOOLBAR_UNORDERED_LIST, 'icon' => 'list-ul'],
            ['label' => t('Numbered List'), 'value' => self::TOOLBAR_ORDERED_LIST, 'icon' => 'list-ol'],
            ['label' => t('Check List'), 'value' => self::TOOLBAR_CHECK_LIST, 'icon' => 'list-check'],
            ['label' => t('Clean Block'), 'value' => self::TOOLBAR_CLEAN_BLOCK, 'icon' => 'eraser'],
            ['label' => t('Link'), 'value' => self::TOOLBAR_LINK, 'icon' => 'link'],
            ['label' => t('Asset'), 'value' => self::TOOLBAR_ASSET, 'icon' => 'paperclip'],
            ['label' => t('Image'), 'value' => self::TOOLBAR_IMAGE, 'icon' => 'image'],
            ['label' => t('Table'), 'value' => self::TOOLBAR_TABLE, 'icon' => 'table'],
            ['label' => t('Horizontal Rule'), 'value' => self::TOOLBAR_HORIZONTAL_RULE, 'icon' => 'minus'],
            ['label' => t('Preview'), 'value' => self::TOOLBAR_PREVIEW, 'icon' => 'eye'],
            ['label' => t('Side-by-side Preview'), 'value' => self::TOOLBAR_SIDE_BY_SIDE, 'icon' => 'split'],
            ['label' => t('Full Screen'), 'value' => self::TOOLBAR_FULLSCREEN, 'icon' => 'expand'],
            ['label' => t('Markdown Guide'), 'value' => self::TOOLBAR_GUIDE, 'icon' => 'circle-question'],
            ['label' => t('Undo'), 'value' => self::TOOLBAR_UNDO, 'icon' => 'rotate-left'],
            ['label' => t('Redo'), 'value' => self::TOOLBAR_REDO, 'icon' => 'rotate-right'],
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
        ]);
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

    private function settingsHtml(bool $readOnly): string
    {
        return template('_components/fieldtypes/Markdown/settings', [
            'field' => $this,
            'flavorOptions' => self::flavorOptions(),
            'toolbarButtonOptions' => self::toolbarButtonOptions(),
            'volumeOptions' => $this->volumeOptions(),
            'readOnly' => $readOnly,
        ]);
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

        if (! $static) {
            $assetSourceKeys = $this->assetSourceKeys();

            app(InternalAssetRegistry::class)->register(MarkdownFieldAsset::class);
            HtmlStack::jsWithVars(fn ($id, $settings) => "new Craft.MarkdownField($id, $settings);", [
                InputNamespace::namespaceId($id),
                [
                    'flavor' => $this->flavor,
                    'previewAction' => 'app/render-markdown',
                    'readOnly' => $static,
                    'toolbar' => ! $inline,
                    'toolbarButtons' => $assetSourceKeys === []
                        ? array_values(array_diff($this->toolbarButtons, [self::TOOLBAR_ASSET]))
                        : $this->toolbarButtons,
                    'toolbarIcons' => $this->toolbarIcons(),
                    'assetElementType' => Asset::class,
                    'assetRefHandle' => Asset::refHandle(),
                    'assetSources' => $assetSourceKeys,
                    'assetCriteria' => $this->assetSelectionCriteria(),
                ],
            ]);
        }

        return template('_components/fieldtypes/Markdown/input', [
            'id' => $id,
            'name' => $this->handle,
            'value' => $this->rawValue($value),
            'field' => $this,
            'placeholder' => $this->placeholder !== null ? t(Str::unescapeShortcodes($this->placeholder),
                category: 'site') : null,
            'orientation' => $this->getOrientation($element),
            'disabled' => $static,
        ]);
    }

    private function toolbarIcons(): array
    {
        return collect(Arr::pluck(self::toolbarButtonOptions(), 'icon'))
            ->unique()
            ->mapWithKeys(fn (string $icon) => [$icon => Icons::svg($icon)])
            ->all();
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
        $html = $value instanceof MarkdownData ? $value->getHtml() : (string) $this->normalizeValue($value, $element);

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
                    : new MarkdownData($raw, $this->flavor)->getHtml();

                return GqlHelper::applyDirectives($source, $resolveInfo, $resolved);
            },
        ];
    }

    private function normalizeMarkdownValue(mixed $value, bool $fromRequest): ?MarkdownData
    {
        if ($value instanceof MarkdownData) {
            return $value->getFlavor() === $this->flavor
                ? $value
                : new MarkdownData($value->getRaw(), $this->flavor);
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

        return new MarkdownData($value, $this->flavor);
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
