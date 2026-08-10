<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Conditions\TextFieldConditionRule;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
use Override;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * Email represents an Email field.
 */
class Email extends Field implements CrossSiteCopyableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface
{
    #[Override]
    public static function displayName(): string
    {
        return t('Email');
    }

    #[Override]
    public static function icon(): string
    {
        return 'at';
    }

    #[Override]
    public static function phpType(): string
    {
        return 'string|null';
    }

    #[Override]
    public static function dbType(): string
    {
        return Query::TYPE_STRING;
    }

    /**
     * @var string|null The input’s placeholder text
     */
    public ?string $placeholder = null;

    public function __construct($config = [])
    {
        if (($config['placeholder'] ?? null) === '') {
            unset($config['placeholder']);
        }
        parent::__construct($config);
    }

    #[Override]
    public function settingsForm(FormContext $context = new FormContext): Form
    {
        return Form::make([
            FormField::make(t('Placeholder Text'))
                ->instructions(t('The text that will be shown if the field doesn’t have a value.'))
                ->control(Text::make('placeholder')->value($this->placeholder)),
        ]);
    }

    #[Override]
    public function formControl(FieldContext $context): Control
    {
        return Text::make($context->path)
            ->inputType('email')
            ->placeholder(t($this->placeholder, category: 'site'))
            ->value($context->value);
    }

    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        return $value !== '' ? $value : null;
    }

    #[Override]
    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        return $value !== null ? Str::idnToUtf8Email($value) : null;
    }

    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return template('_includes/forms/text', [
            'type' => 'email',
            'id' => $this->getInputId(),
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'inputmode' => 'email',
            'placeholder' => t($this->placeholder, category: 'site'),
            'value' => $value,
        ]);
    }

    #[Override]
    public function prepareForElementValidation(mixed $value): mixed
    {
        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }

    /** @return list<string> */
    #[Override]
    public function getElementRules(ElementInterface $element): array
    {
        return [
            'email:rfc',
        ];
    }

    public function getElementConditionRuleType(): string
    {
        return TextFieldConditionRule::class;
    }

    #[Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        if (! $value) {
            return '';
        }
        $value = Html::encode($value);

        return "<a href=\"mailto:$value\">$value</a>";
    }

    #[Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (! $value) {
            $value = currentUser()?->asElement()->email;
        }

        return $this->getPreviewHtml($value, $element ?? new Entry);
    }
}
