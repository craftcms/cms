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
use CraftCms\Cms\Gql\Types\Generators\IconDataType;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use GraphQL\Type\Definition\Type;
use Override;
use yii\db\Schema;

use function CraftCms\Cms\t;

/**
 * Icon represents an icon picker field.
 */
class Icon extends Field implements CrossSiteCopyableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface, ThumbableFieldInterface
{
    /**
     * @var array Info about the available icons
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
        return Schema::TYPE_STRING;
    }

    /**
     * Returns a list of Font Awesome icon styles supported by the given icon.
     *
     * @return string[]
     */
    private static function iconStyles(string $name): array
    {
        if (! isset(self::$_icons)) {
            $indexPath = '@craftcms/resources/icons/index.php';
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
        $html = FormFields::lightswitchFieldHtml([
            'label' => t('Include Pro icons'),
            'instructions' => t('Should icons that are exclusive to Font Awesome Pro be selectable? (<a href="{url}">View pricing</a>)', [
                'url' => 'https://fontawesome.com/plans',
            ]),
            'name' => 'includeProIcons',
            'on' => $this->includeProIcons,
            'disabled' => $readOnly,
        ]);

        if (Cms::config()->enableGql) {
            $html .= Html::tag('hr').
            Html::button(t('Advanced'), attributes: [
                'class' => 'fieldtoggle',
                'data' => ['target' => 'advanced'],
            ]).
            Html::beginTag('div', [
                'id' => 'advanced',
                'class' => 'hidden',
            ]);

            $html .=
                FormFields::selectFieldHtml([
                    'label' => t('GraphQL Mode'),
                    'id' => 'graphql-mode',
                    'name' => 'graphqlMode',
                    'options' => [
                        ['label' => t('Full data'), 'value' => 'full'],
                        ['label' => t('Name only'), 'value' => 'name'],
                    ],
                    'value' => $this->fullGraphqlData ? 'full' : 'name',
                    'disabled' => $readOnly,
                ]);

            $html .= Html::endTag('div');
        }

        return $html;
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
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        /** @var IconData|null $value */
        return FormFields::iconPickerHtml([
            'static' => true,
            'value' => $value?->name,
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
    public function getContentGqlType(): Type|array
    {
        if (! $this->fullGraphqlData) {
            return parent::getContentGqlType();
        }

        return IconDataType::generateType($this);
    }
}
