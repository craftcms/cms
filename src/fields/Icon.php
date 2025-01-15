<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\InlineEditableFieldInterface;
use craft\base\MergeableFieldInterface;
use craft\base\ThumbableFieldInterface;
use craft\elements\Entry;
use craft\helpers\Cp;
use craft\helpers\Html;
use yii\db\Schema;

/**
 * Icon represents an icon picker field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class Icon extends Field implements InlineEditableFieldInterface, ThumbableFieldInterface, MergeableFieldInterface
{
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
        return 'string|null';
    }

    /**
     * @inheritdoc
     */
    public static function dbType(): string
    {
        return Schema::TYPE_STRING;
    }

    /**
     * @var bool Whether icons exclusive to Font Awesome Pro should be selectable.
     * @since 5.3.0
     */
    public bool $includeProIcons = false;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Default includeProIcons to true for existing Icon fields
        if (isset($config['id']) && !isset($config['includeProIcons'])) {
            $config['includeProIcons'] = true;
        }

        parent::__construct($config);
    }

    public function getSettingsHtml(): ?string
    {
        return Cp::lightswitchFieldHtml([
            'label' => Craft::t('app', 'Include Pro icons'),
            'instructions' => Craft::t('app', 'Should icons that are exclusive to Font Awesome Pro be selectable? (<a href="{url}">View pricing</a>)', [
                'url' => 'https://fontawesome.com/plans',
            ]),
            'name' => 'includeProIcons',
            'on' => $this->includeProIcons,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        return $value ?: null;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Cp::iconPickerHtml([
            'id' => $this->getInputId(),
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'value' => $value,
            'freeOnly' => !$this->includeProIcons,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return Cp::iconPickerHtml([
            'static' => true,
            'value' => $value,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        return $value ? Html::tag('div', Cp::iconSvg($value), ['class' => 'cp-icon']) : '';
    }

    /**
     * @inheritdoc
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (!$value) {
            $value = 'info';
        }

        return $this->getPreviewHtml($value, $element ?? new Entry());
    }

    /**
     * @inheritdoc
     */
    public function getThumbHtml(mixed $value, ElementInterface $element, int $size): ?string
    {
        return $value ? Html::tag('div', Cp::iconSvg($value), ['class' => 'cp-icon']) : null;
    }
}
