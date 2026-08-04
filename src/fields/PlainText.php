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
use craft\base\SortableFieldInterface;
use craft\elements\Entry;
use craft\fields\conditions\TextFieldConditionRule;
use craft\helpers\Html;
use craft\helpers\StringHelper;

/**
 * PlainText represents a Plain Text field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class PlainText extends Field implements InlineEditableFieldInterface, SortableFieldInterface, MergeableFieldInterface, CrossSiteCopyableFieldInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Plain Text');
    }

    /**
     * @inheritdoc
     */
    public static function phpType(): string
    {
        return 'string|null';
    }

    /**
     * @var string The UI mode of the field.
     * @phpstan-var 'normal'|'enlarged'
     * @since 3.5.0
     */
    public string $uiMode = 'normal';

    /**
     * @var string|null The input’s placeholder text
     */
    public ?string $placeholder = null;

    /**
     * @var bool Whether the input should use monospace font
     */
    public bool $code = false;

    /**
     * @var bool Whether the input should allow line breaks
     */
    public bool $multiline = false;

    /**
     * @var int The minimum number of rows the input should have, if multi-line
     */
    public int $initialRows = 4;

    /**
     * @var int|null The maximum number of characters allowed in the field
     */
    public ?int $charLimit = null;

    /**
     * @var int|null The maximum number of bytes allowed in the field
     * @since 3.4.0
     */
    public ?int $byteLimit = null;

    /**
     * @inheritdoc
     */
    public function __construct(array $config = [])
    {
        // Config normalization
        if (isset($config['limitUnit'], $config['fieldLimit'])) {
            if ($config['limitUnit'] === 'chars') {
                $config['charLimit'] = (int)$config['fieldLimit'] ?: null;
            } else {
                $config['byteLimit'] = (int)$config['fieldLimit'] ?: null;
            }
            unset($config['limitUnit'], $config['fieldLimit']);
        }

        // remove unused settings
        unset($config['maxLengthUnit'], $config['columnType']);

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (isset($this->placeholder)) {
            $this->placeholder = StringHelper::shortcodesToEmoji($this->placeholder);
        }
    }

    /**
     * @inheritdoc
     */
    public function getSettings(): array
    {
        $settings = parent::getSettings();
        if (isset($settings['placeholder']) && !Craft::$app->getDb()->getSupportsMb4()) {
            $settings['placeholder'] = StringHelper::emojiToShortcodes($settings['placeholder']);
        }
        return $settings;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['initialRows', 'charLimit', 'byteLimit'], 'integer', 'min' => 1];
        return $rules;
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
        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/PlainText/settings.twig', [
            'field' => $this,
            'readOnly' => $readOnly,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->_normalizeValueInternal($value, $element, false);
    }

    /**
     * @inheritdoc
     */
    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->_normalizeValueInternal($value, $element, true);
    }

    private function _normalizeValueInternal(mixed $value, ?ElementInterface $element, bool $fromRequest): mixed
    {
        if ($value !== null) {
            if (!$fromRequest) {
                $value = StringHelper::unescapeShortcodes(StringHelper::shortcodesToEmoji($value));
            }

            $value = trim(StringHelper::convertLineBreaks($value));
        }

        return $value !== '' ? $value : null;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->_inputHtml($value, $element, false);
    }

    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return $this->_inputHtml($value, $element, true);
    }

    private function _inputHtml(mixed $value, ?ElementInterface $element, bool $static): string
    {
        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/PlainText/input.twig', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'placeholder' => $this->placeholder !== null ? Craft::t('site', StringHelper::unescapeShortcodes($this->placeholder)) : null,
            'orientation' => $this->getOrientation($element),
            'disabled' => $static,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return [
            [
                'string',
                'max' => $this->byteLimit ?? $this->charLimit ?? null,
                'encoding' => $this->byteLimit ? '8bit' : 'UTF-8',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value !== null) {
            $value = StringHelper::escapeShortcodes($value);
            if (!Craft::$app->getDb()->getSupportsMb4()) {
                $value = StringHelper::emojiToShortcodes($value);
            }
        }
        return $value;
    }

    /**
     * @inheritdoc
     */
    public function getElementConditionRuleType(): ?string
    {
        return TextFieldConditionRule::class;
    }

    /**
     * @inheritdoc
     */
    public function getPreviewHtml(mixed $value, ?ElementInterface $element = null): string
    {
        $previewHtml = parent::getPreviewHtml($value, $element);

        if (!$this->code) {
            return $previewHtml;
        }

        return Html::tag('div', $previewHtml, [
            'class' => 'code',
        ]);
    }
    
    /**
     * @inheritdoc
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (!$value) {
            $value = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';
        }

        return $this->getPreviewHtml($value, $element ?? new Entry());
    }
}
