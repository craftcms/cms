<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\CrossSiteCopyableFieldInterface;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\InlineEditableFieldInterface;
use craft\base\MergeableFieldInterface;
use craft\base\ThumbableFieldInterface;
use craft\elements\Entry;
use craft\fields\data\IconData;
use craft\gql\types\generators\IconDataType;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Html;
use GraphQL\Type\Definition\Type;
use yii\db\Schema;

/**
 * Icon represents an icon picker field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class Icon extends Field implements InlineEditableFieldInterface, ThumbableFieldInterface, MergeableFieldInterface, CrossSiteCopyableFieldInterface
{
    /**
     * @var array Info about the available icons
     * @see iconStyles()
     */
    private static array $_icons;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Icon');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): string
    {
        return 'icons';
    }

    /**
     * @inheritdoc
     */
    public static function phpType(): string
    {
        return sprintf('\\%s|null', IconData::class);
    }

    /**
     * @inheritdoc
     */
    public static function dbType(): string
    {
        return Schema::TYPE_STRING;
    }

    /**
     * Returns a list of Font Awesome icon styles supported by the given icon.
     *
     * @param string $name
     * @return string[]
     */
    private static function iconStyles(string $name): array
    {
        if (!isset(self::$_icons)) {
            $indexPath = '@app/icons/index.php';
            self::$_icons = require Craft::getAlias($indexPath);
        }

        return self::$_icons[$name]['styles'] ?? [];
    }

    /**
     * @var bool Whether icons exclusive to Font Awesome Pro should be selectable.
     * @since 5.3.0
     */
    public bool $includeProIcons = false;

    /**
     * @var bool Whether GraphQL values should be returned as objects with `name` and `styles` keys.
     * @since 5.8.0
     */
    public bool $fullGraphqlData = true;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Default includeProIcons to true for existing Icon fields
        if (isset($config['id']) && !isset($config['includeProIcons'])) {
            $config['includeProIcons'] = true;
        }

        if (isset($config['graphqlMode'])) {
            $config['fullGraphqlData'] = ArrayHelper::remove($config, 'graphqlMode') === 'full';
        }

        // Default fullGraphqlData to false for existing fields
        if (isset($config['id']) && !isset($config['fullGraphqlData'])) {
            $config['fullGraphqlData'] = false;
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return $this->settingsHtml(false);
    }

    /**
     * @inheritdoc
     */
    public function getReadOnlySettingsHtml(): ?string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        $html = Cp::lightswitchFieldHtml([
            'label' => Craft::t('app', 'Include Pro icons'),
            'instructions' => Craft::t('app', 'Should icons that are exclusive to Font Awesome Pro be selectable? (<a href="{url}">View pricing</a>)', [
                'url' => 'https://fontawesome.com/plans',
            ]),
            'name' => 'includeProIcons',
            'on' => $this->includeProIcons,
            'disabled' => $readOnly,
        ]);

        if (Craft::$app->getConfig()->getGeneral()->enableGql) {
            $html .= Html::tag('hr') .
            Html::button(Craft::t('app', 'Advanced'), options: [
                'class' => 'fieldtoggle',
                'data' => ['target' => 'advanced'],
            ]) .
            Html::beginTag('div', [
                'id' => 'advanced',
                'class' => 'hidden',
            ]);

            $html .=
                Cp::selectFieldHtml([
                    'label' => Craft::t('app', 'GraphQL Mode'),
                    'id' => 'graphql-mode',
                    'name' => 'graphqlMode',
                    'options' => [
                        ['label' => Craft::t('app', 'Full data'), 'value' => 'full'],
                        ['label' => Craft::t('app', 'Name only'), 'value' => 'name'],
                    ],
                    'value' => $this->fullGraphqlData ? 'full' : 'name',
                    'disabled' => $readOnly,
                ]);

            $html .= Html::endTag('div');
        }

        return $html;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value instanceof IconData) {
            return $value;
        }

        if (!is_string($value) || $value === '') {
            return null;
        }

        return new IconData($value, self::iconStyles($value));
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        /** @var IconData|null $value */
        return Cp::iconPickerHtml([
            'id' => $this->getInputId(),
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'value' => $value?->name,
            'freeOnly' => !$this->includeProIcons,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        /** @var IconData|null $value */
        return Cp::iconPickerHtml([
            'static' => true,
            'value' => $value?->name,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        /** @var IconData|null $value */
        return $value ? Html::tag('div', Cp::iconSvg($value->name), ['class' => 'cp-icon']) : '';
    }

    /**
     * @inheritdoc
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        /** @var IconData|null $value */
        return $this->getPreviewHtml($value, $element ?? new Entry());
    }

    /**
     * @inheritdoc
     */
    public function getThumbHtml(mixed $value, ElementInterface $element, int $size): ?string
    {
        /** @var IconData|null $value */
        return $value ? Html::tag('div', Cp::iconSvg($value->name), ['class' => 'cp-icon']) : null;
    }

    /**
     * @inheritdoc
     * @since 5.8.0
     */
    public function getContentGqlType(): Type|array
    {
        if (!$this->fullGraphqlData) {
            return parent::getContentGqlType();
        }

        return IconDataType::generateType($this);
    }
}
