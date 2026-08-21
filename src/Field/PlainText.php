<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Conditions\TextFieldConditionRule;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Controls\Textarea;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Form\Nodes\Group;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\DB;
use Override;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * PlainText represents a Plain Text field.
 */
class PlainText extends Field implements CrossSiteCopyableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface, SortableFieldInterface
{
    #[Override]
    public static function displayName(): string
    {
        return t('Plain Text');
    }

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

    /** @param array<string, mixed> $config */
    public function __construct(array $config = [])
    {
        // Config normalization
        if (isset($config['limitUnit'], $config['fieldLimit'])) {
            if ($config['limitUnit'] === 'chars') {
                $config['charLimit'] = (int) $config['fieldLimit'] ?: null;
            } else {
                $config['byteLimit'] = (int) $config['fieldLimit'] ?: null;
            }
        }

        // remove unused settings
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
            'initialRows' => ['nullable', 'integer', 'min:1'],
            'charLimit' => ['nullable', 'integer', 'min:1'],
            'byteLimit' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    public function settingsForm(FormContext $context = new FormContext): Form
    {
        return Form::make([
            FormField::make(t('UI Mode'))
                ->instructions(t('How the field should be presented in the control panel.'))
                ->control(Choice::make('uiMode')->value($this->uiMode)->options([
                    ['label' => t('Normal'), 'value' => 'normal'],
                    ['label' => t('Enlarged'), 'value' => 'enlarged'],
                ])),
            FormField::make(t('Placeholder Text'))
                ->instructions(t('The text that will be shown if the field doesn’t have a value.'))
                ->control(Text::make(['placeholder'])->value($this->placeholder)),
            Group::make('plain-text-field-limit', [
                FormField::make(t('Maximum'))
                    ->instructions(t('The maximum number of characters or bytes the field is allowed to have.'))
                    ->control(Number::make('fieldLimit')
                        ->value($this->charLimit ?? $this->byteLimit)
                        ->deltaGroupAtNamespace()
                        ->min(1)),
                FormField::make(t('Unit'))
                    ->control(Choice::make(['limitUnit'])
                        ->value($this->byteLimit ? 'bytes' : 'chars')
                        ->deltaGroupAtNamespace()
                        ->options([
                            ['label' => t('Characters'), 'value' => 'chars'],
                            ['label' => t('Bytes'), 'value' => 'bytes'],
                        ])),
            ])->label(t('Field Limit')),
            Group::make('plain-text-behavior', [
                FormField::make(t('Use a monospaced font'))
                    ->control(Lightswitch::make('code')->value($this->code)),
                FormField::make(t('Allow line breaks'))
                    ->control(Lightswitch::make(['multiline'])->value($this->multiline)),
            ]),
        ])->when($this->multiline, fn (Form $form) => $form->add(
            FormField::make(t('Initial Rows'))
                ->control(Number::make('initialRows')
                    ->value($this->initialRows)
                    ->min(1)),
        ));
    }

    #[Override]
    public function formControl(FieldContext $context): Control
    {
        $control = $this->multiline
            ? Textarea::make($context->path)->rows($this->initialRows)
            : Text::make($context->path);

        return $control
            ->value($context->value)
            ->maxLength($this->charLimit)
            ->placeholder($this->placeholder === null ? null : t($this->placeholder, category: 'site'))
            ->monospace($this->code);
    }

    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->_normalizeValueInternal($value, false);
    }

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

    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->_inputHtml($value, $element, false);
    }

    private function _inputHtml(mixed $value, ?ElementInterface $element, bool $static): string
    {
        return template('_components/fieldtypes/PlainText/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'placeholder' => $this->placeholder !== null ? t(Str::unescapeShortcodes($this->placeholder),
                category: 'site') : null,
            'orientation' => $this->getOrientation($element),
            'disabled' => $static,
        ]);
    }

    /** @return list<Closure|string> */
    #[Override]
    public function getElementRules(ElementInterface $element): array
    {
        $rules = ['string'];

        if ($this->byteLimit) {
            $rules[] = function ($attribute, $value, $fail) {
                if (mb_strlen($value, '8bit') > $this->byteLimit) {
                    $fail(t('{attribute} should contain at most {max, number} {max, plural, one{character} other{characters}}.', [
                        'attribute' => $this->getUiLabel(),
                        'max' => $this->byteLimit,
                    ]));
                }
            };
        } elseif ($this->charLimit) {
            $rules[] = "max:$this->charLimit";
        }

        return $rules;
    }

    #[Override]
    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value !== null) {
            $value = Str::escapeShortcodes($value);
            if (! DB::supportsMb4()) {
                $value = Str::emojiToShortcodes($value);
            }
        }

        return $value;
    }

    public function getElementConditionRuleType(): string
    {
        return TextFieldConditionRule::class;
    }

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

    #[Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (! $value) {
            $value = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';
        }

        return $this->getPreviewHtml($value, $element ?? new Entry);
    }
}
