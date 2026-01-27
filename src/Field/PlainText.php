<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Craft;
use craft\base\ElementInterface;
use craft\fields\conditions\TextFieldConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use Override;

use function CraftCms\Cms\t;

/**
 * PlainText represents a Plain Text field.
 */
final class PlainText extends Field implements CrossSiteCopyableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface, SortableFieldInterface
{
    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function displayName(): string
    {
        return t('Plain Text');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function phpType(): string
    {
        return 'string|null';
    }

    /**
     * @var string The UI mode of the field.
     *
     * @phpstan-var 'normal'|'enlarged'
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
     */
    public ?int $byteLimit = null;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $config = [])
    {
        // Config normalization
        if (isset($config['limitUnit'], $config['fieldLimit'])) {
            if ($config['limitUnit'] === 'chars') {
                $config['charLimit'] = (int) $config['fieldLimit'] ?: null;
            } else {
                $config['byteLimit'] = (int) $config['fieldLimit'] ?: null;
            }
            unset($config['limitUnit'], $config['fieldLimit']);
        }

        // remove unused settings
        unset($config['maxLengthUnit'], $config['columnType']);

        parent::__construct($config);

        if (isset($this->placeholder)) {
            $this->placeholder = Str::shortcodesToEmoji($this->placeholder);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSettings(): array
    {
        $settings = parent::getSettings();
        if (isset($settings['placeholder']) && ! Craft::$app->getDb()->getSupportsMb4()) {
            $settings['placeholder'] = Str::emojiToShortcodes($settings['placeholder']);
        }

        return $settings;
    }

    #[Override]
    public static function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'initialRows' => ['nullable', 'integer', 'min:1'],
            'charLimit' => ['nullable', 'integer', 'min:1'],
            'byteLimit' => ['nullable', 'integer', 'min:1'],
        ]);
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
        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/PlainText/settings.twig', [
            'field' => $this,
            'readOnly' => $readOnly,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->_normalizeValueInternal($value, false);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->_normalizeValueInternal($value, true);
    }

    private function _normalizeValueInternal(mixed $value, bool $fromRequest): mixed
    {
        if ($value !== null) {
            if (! $fromRequest) {
                $value = Str::unescapeShortcodes(Str::shortcodesToEmoji($value));
            }

            $value = trim(Str::convertLineBreaks($value));
        }

        return $value !== '' ? $value : null;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->_inputHtml($value, $element, false);
    }

    #[Override]
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
            'placeholder' => $this->placeholder !== null ? t(Str::unescapeShortcodes($this->placeholder), category: 'site') : null,
            'orientation' => $this->getOrientation($element),
            'disabled' => $static,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
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
     * {@inheritdoc}
     */
    #[Override]
    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value !== null) {
            $value = Str::escapeShortcodes($value);
            if (! Craft::$app->getDb()->getSupportsMb4()) {
                $value = Str::emojiToShortcodes($value);
            }
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getElementConditionRuleType(): string
    {
        return TextFieldConditionRule::class;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        $previewHtml = parent::getPreviewHtml($value, $element);

        if (! $this->code) {
            return $previewHtml;
        }

        return Html::tag('div', $previewHtml, [
            'class' => 'code',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (! $value) {
            $value = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';
        }

        return $this->getPreviewHtml($value, $element ?? new Entry);
    }
}
