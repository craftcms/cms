<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Cp\IconSets;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\ThumbableFieldInterface;
use CraftCms\Cms\Field\Data\IconData;
use CraftCms\Cms\Gql\Types\Generators\IconDataType;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\CmsAssets;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Controls\IconPicker;
use CraftCms\Cms\Ui\Controls\Lightswitch;
use CraftCms\Cms\Ui\Nodes\Field as UiField;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use GraphQL\Type\Definition\Type;
use Override;

use function CraftCms\Cms\t;

/**
 * Icon represents an icon picker field.
 *
 * @since 6.0.0
 */
class Icon extends Field implements InlineEditableFieldInterface, MergeableFieldInterface, ThumbableFieldInterface
{
    /**
     * @var array<string, array{name: string, terms: string, pro: bool, styles: list<string>}> Info about the available icons
     *
     * @see iconStyles()
     */
    private static array $_icons;

    #[Override]
    public static function displayName(): string
    {
        return t('Icon');
    }

    #[Override]
    public static function icon(): string
    {
        return 'icons';
    }

    #[Override]
    public static function phpType(): string
    {
        return sprintf('\\%s|null', IconData::class);
    }

    #[Override]
    public static function dbType(): string
    {
        return Query::TYPE_STRING;
    }

    /**
     * Returns a list of Font Awesome icon styles supported by the given icon.
     *
     * @return string[]
     */
    private static function iconStyles(string $name): array
    {
        if (! isset(self::$_icons)) {
            self::$_icons = require CmsAssets::resourcesPath('icons/index.php');
        }

        return self::$_icons[$name]['styles'] ?? [];
    }

    /**
     * @var bool Whether icons exclusive to Font Awesome Pro should be selectable.
     */
    public bool $includeProIcons = false;

    /**
     * @var string[] The icon sets that should be selectable.
     *
     * {@see IconSets::FONT_AWESOME} refers to the built-in Font Awesome icons.
     *
     * @since 5.12.0
     */
    public array $iconSets = [IconSets::FONT_AWESOME];

    /**
     * @var bool Whether GraphQL values should be returned as objects with `name` and `styles` keys.
     */
    public bool $fullGraphqlData = true;

    public function __construct($config = [])
    {
        // Default includeProIcons to true for existing Icon fields
        if (isset($config['id']) && ! isset($config['includeProIcons'])) {
            $config['includeProIcons'] = true;
        }

        if (isset($config['graphqlMode'])) {
            $config['fullGraphqlData'] = Arr::pull($config, 'graphqlMode') === 'full';
        }

        if (isset($config['iconSets']) && ! is_array($config['iconSets'])) {
            $config['iconSets'] = array_filter([$config['iconSets']]);
        }

        // Default fullGraphqlData to false for existing fields
        if (isset($config['id']) && ! isset($config['fullGraphqlData'])) {
            $config['fullGraphqlData'] = false;
        }

        parent::__construct($config);
    }

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'iconSets' => [
                'required',
                'array',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (is_array($value) && array_diff($value, array_keys(app(IconSets::class)->getIconSetNames()))) {
                        $fail('validation.in')->translate();
                    }
                },
            ],
        ]);
    }

    #[Override]
    public function getMessages(): array
    {
        return array_merge(parent::getMessages(), [
            'iconSets.required' => t('At least one icon set must be selected.'),
        ]);
    }

    #[Override]
    public function settingsUi(UiContext $context = new UiContext): Ui
    {
        $iconSets = app(IconSets::class);
        $hasCustomIconSets = ! empty($iconSets->getIconSets());
        $options = [];

        foreach ($iconSets->getIconSetNames() as $handle => $name) {
            $options[] = ['label' => $name, 'value' => $handle];
        }

        return Ui::make([
            UiField::make(t('Icon Sets'))
                ->instructions(t('Which icon sets should be selectable?'))
                ->control(Choice::make('iconSets')
                    ->multiple()
                    ->sortable()
                    ->options($options)
                    ->value($this->iconSets)
                    ->reactive())
                ->visible($hasCustomIconSets),
            UiField::make(t('Include Pro icons'))
                ->instructions(t('Should icons that are exclusive to Font Awesome Pro be selectable?'))
                ->control(Lightswitch::make('includeProIcons')->value($this->includeProIcons))
                ->visible(in_array(IconSets::FONT_AWESOME, $this->iconSets, true)),
        ])->when(
            Cms::config()->enableGql,
            fn (Ui $ui): Ui => $ui->add(
                UiField::make(t('GraphQL Mode'))
                    ->control(Choice::make('graphqlMode')->options([
                        ['label' => t('Full data'), 'value' => 'full'],
                        ['label' => t('Name only'), 'value' => 'name'],
                    ])->value($this->fullGraphqlData ? 'full' : 'name')),
            ),
        );
    }

    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value instanceof IconData) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        if ($customIcon = IconSets::parse($value)) {
            [$set, $name] = $customIcon;

            return new IconData($name, [], $set);
        }

        return new IconData($value, self::iconStyles($value));
    }

    #[Override]
    public function uiControl(FieldContext $context): Control
    {
        return IconPicker::make($context->path)
            ->freeOnly(! $this->includeProIcons)
            ->sets($this->iconSets)
            ->value($context->value instanceof IconData ? $context->value->serialize() : $context->value);
    }

    #[Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        /** @var IconData|null $value */
        return $value ? Html::tag('div', Icons::svg($value->serialize()), ['class' => 'cp-icon']) : '';
    }

    #[Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        /** @var IconData|null $value */
        return $this->getPreviewHtml($value, $element ?? new Entry);
    }

    public function getThumbHtml(mixed $value, ElementInterface $element, int $size, ImageTransformMode $mode = ImageTransformMode::Fit): ?string
    {
        /** @var IconData|null $value */
        return $value ? Html::tag('div', Icons::svg($value->serialize()), ['class' => 'cp-icon']) : null;
    }

    #[Override]
    public function getContentGqlType(): Type
    {
        if (! $this->fullGraphqlData) {
            return parent::getContentGqlType();
        }

        return IconDataType::generateType($this);
    }
}
