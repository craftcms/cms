<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Entry;
use craft\gql\types\generators\IconDataType;
use craft\helpers\Cp;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\ThumbableFieldInterface;
use CraftCms\Cms\Field\Data\IconData;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use GraphQL\Type\Definition\Type;
use yii\db\Schema;

use function CraftCms\Cms\t;

/**
 * Icon represents an icon picker field.
 */
final class Icon extends Field implements CrossSiteCopyableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface, ThumbableFieldInterface
{
    /**
     * @var array Info about the available icons
     *
     * @see iconStyles()
     */
    private static array $_icons;

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function displayName(): string
    {
        return t('Icon');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function icon(): string
    {
        return 'icons';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function phpType(): string
    {
        return sprintf('\\%s|null', IconData::class);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
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
            self::$_icons = require Craft::getAlias($indexPath);
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getSettingsHtml(): string
    {
        return $this->settingsHtml(false);
    }

    /**
     * {@inheritdoc}
     */
    public function getReadOnlySettingsHtml(): string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        $html = Cp::lightswitchFieldHtml([
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
                Cp::selectFieldHtml([
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

    /**
     * {@inheritdoc}
     */
    #[\Override]
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

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        /** @var IconData|null $value */
        return Cp::iconPickerHtml([
            'id' => $this->getInputId(),
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'value' => $value?->name,
            'freeOnly' => ! $this->includeProIcons,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        /** @var IconData|null $value */
        return Cp::iconPickerHtml([
            'static' => true,
            'value' => $value?->name,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        /** @var IconData|null $value */
        return $value ? Html::tag('div', Cp::iconSvg($value->name), ['class' => 'cp-icon']) : '';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        /** @var IconData|null $value */
        return $this->getPreviewHtml($value, $element ?? new Entry);
    }

    /**
     * {@inheritdoc}
     */
    public function getThumbHtml(mixed $value, ElementInterface $element, int $size): ?string
    {
        /** @var IconData|null $value */
        return $value ? Html::tag('div', Cp::iconSvg($value->name), ['class' => 'cp-icon']) : null;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getContentGqlType(): Type|array
    {
        if (! $this->fullGraphqlData) {
            return parent::getContentGqlType();
        }

        return IconDataType::generateType($this);
    }
}
