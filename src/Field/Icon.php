<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\ThumbableFieldInterface;
use CraftCms\Cms\Field\Data\IconData;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\IconPicker;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Gql\Types\Generators\IconDataType;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Query;
use GraphQL\Type\Definition\Type;
use Override;

use function CraftCms\Cms\t;

/**
 * Icon represents an icon picker field.
 */
class Icon extends Field implements CrossSiteCopyableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface, ThumbableFieldInterface
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
            $indexPath = '@cmsAssets/resources/icons/index.php';
            self::$_icons = require Aliases::get($indexPath);
        }

        return self::$_icons[$name]['styles'] ?? [];
    }

    /**
     * @var bool Whether icons exclusive to Font Awesome Pro should be selectable.
     */
    public bool $includeProIcons = false;

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

        // Default fullGraphqlData to false for existing fields
        if (isset($config['id']) && ! isset($config['fullGraphqlData'])) {
            $config['fullGraphqlData'] = false;
        }

        parent::__construct($config);
    }

    #[Override]
    public function settingsForm(FormContext $context = new FormContext): Form
    {
        return Form::make([
            FormField::make(t('Include Pro icons'))
                ->instructions(t('Should icons that are exclusive to Font Awesome Pro be selectable?'))
                ->control(Lightswitch::make('includeProIcons')->value($this->includeProIcons)),
        ])->when(
            Cms::config()->enableGql,
            fn (Form $form): Form => $form->add(
                FormField::make(t('GraphQL Mode'))
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

        return new IconData($value, self::iconStyles($value));
    }

    #[Override]
    public function formControl(FieldContext $context): Control
    {
        return IconPicker::make($context->path)
            ->freeOnly(! $this->includeProIcons)
            ->value($context->value instanceof IconData ? $context->value->name : $context->value);
    }

    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        /** @var IconData|null $value */
        return FormFields::iconPickerHtml([
            'id' => $this->getInputId(),
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'value' => $value?->name,
            'freeOnly' => ! $this->includeProIcons,
        ]);
    }

    #[Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        /** @var IconData|null $value */
        return $value ? Html::tag('div', Icons::svg($value->name), ['class' => 'cp-icon']) : '';
    }

    #[Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        /** @var IconData|null $value */
        return $this->getPreviewHtml($value, $element ?? new Entry);
    }

    public function getThumbHtml(mixed $value, ElementInterface $element, int $size): ?string
    {
        /** @var IconData|null $value */
        return $value ? Html::tag('div', Icons::svg($value->name), ['class' => 'cp-icon']) : null;
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
