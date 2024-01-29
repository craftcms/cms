<?php

namespace craft\ui\components;

use Closure;
use Craft;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\ui\Component;
use craft\ui\concerns\CanBeDisabled;
use craft\ui\concerns\CanBeRequired;
use craft\ui\concerns\HasId;
use craft\ui\concerns\HasLabel;
use craft\ui\concerns\HasName;
use craft\ui\concerns\HasOrientation;
use craft\ui\concerns\HasValue;
use craft\ui\concerns\IsSiteAware;
use Exception;
use craft\ui\concerns\HasExtraAttributes;
use yii\helpers\Markdown;

class Field extends Component
{
    use HasId;
    use HasLabel;
    use HasName;
    use HasValue;
    use HasOrientation;
    use CanBeRequired;
    use CanBeDisabled;
    use IsSiteAware;
    use HasExtraAttributes;

    /**
     * @inerhitdoc
     */
    protected string $view = '_ui/field.twig';

    /**
     * @var string|null The field handle
     */
    protected ?string $attribute = null;

    /**
     * @var bool Whether the attribute should be shown
     */
    protected bool $showAttribute = false;

    /**
     * @var bool Whether the field is translatable
     */
    protected bool $translatable = false;

    /**
     * @var Input|Closure|null The input component
     */
    protected Input|Closure|null $input = null;

    /**
     * @var array Array of errors
     */
    protected array $errors = [];

    protected string|Closure|null $instructions = null;

    /**
     * @var string Position of the instructions. Can be 'before' or 'after'
     */
    protected string $instructionsPosition = 'before';

    /**
     * @var ?array Status array. First key is one of AttributeStatus::*, second key is the status type, and the value is the status message.
     */
    protected ?array $status = null;

    /**
     * @var string|null The tip related to the field
     */
    protected ?string $tip = null;

    /**
     * @var string|null The warning related to the field.
     */
    protected ?string $warning = null;

    /**
     * @var string|Closure|null Extra label content
     */
    protected string|Closure|null $labelExtra = null;

    public function input(Input|Closure|null $input): static
    {
        $this->input = $input;
        return $this;
    }

    public function attribute(?string $attribute): static
    {
        $this->attribute = $attribute;
        return $this;
    }

    public function translatable(?bool $value = true): static
    {
        $this->translatable = (bool)$value;
        return $this;
    }

    public function errors(?array $errors = []): static
    {
        if ($errors) {
            $this->errors = [
                ...$this->errors,
                ...$errors
            ];
        }

        return $this;
    }

    public function instructions(string|Closure|null $instructions): static
    {
        $this->instructions = $instructions;
        return $this;
    }

    public function instructionsPosition(string $instructionsPosition): static
    {
        $this->instructionsPosition = $instructionsPosition;
        return $this;
    }

    public function showAttribute(bool $value = true): static
    {
        $this->showAttribute = $value;
        return $this;
    }

    public function tip(?string $tip): static
    {
        $this->tip = $tip;
        return $this;
    }

    public function warning(?string $warning = null): static
    {
        $this->warning = $warning;
        return $this;
    }

    public function labelExtra(string|Closure|null $labelExtra): static
    {
        $this->labelExtra = $labelExtra;
        return $this;
    }

    public function getAttribute(): ?string
    {
        return $this->attribute;
    }

    public function getTranslatable(): bool
    {
        return Craft::$app->getIsMultiSite() && $this->translatable;
    }

    public function getInput(): Input|string|null
    {
        $input = $this->evaluate($this->input);

        if (is_string($input)) {
            return $input;
        }

        if (!$input) {
            $input = Input::make();
        }

        $input->name($this->getName())
            ->id($this->getId())
            ->value($this->getValue())
            ->orientation($this->getOrientation())
            ->value($this->getValue());

        return $input->render();
    }

    public function getRequiredIndicator(): string
    {
        return $this->required ? Html::tag('span', Craft::t('app', 'Required'), [
                'class' => ['visually-hidden'],
            ]) .
            Html::tag('span', '', [
                'class' => ['required'],
                'aria' => [
                    'hidden' => 'true',
                ],
            ]) : '';
    }

    public function getTranslationIndicator(): string
    {
        return $this->translatable ? TranslationIndicator::make()->render() : '';
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getLabel(): string
    {
        return Html::label($this->label, $this->getId(), [
            'class' => 'label',
        ]);
    }

    public function getInstructions(): ?string
    {
        $content = $this->evaluate($this->instructions);

        if (!$content) {
            return null;
        }

        return Html::tag('div', preg_replace('/&amp;(\w+);/', '&$1;', Markdown::process(Html::encodeInvalidTags($content), 'gfm-comment')), [
            'id' => $this->getId() . '-instructions',
            'class' => ['instructions'],
        ]);
    }

    public function getInstructionsPosition(): string
    {
        return $this->instructionsPosition;
    }

    public function getShowAttribute(): bool
    {
        if ($this->showAttribute && $currentUser = Craft::$app->getUser()->getIdentity()) {
            return $currentUser->admin && $currentUser->getPreference('showFieldHandles');
        }

        return false;
    }

    public function getStatus(): ?string
    {
        if (!$this->status) {
            return null;
        }

        return Html::beginTag('div', [
                'id' => $this->getId() . '-status',
                'class' => ['status-badge', StringHelper::toString($this->status[0])],
                'title' => $this->status[1],
            ]) .
            Html::tag('span', $this->status[1], [
                'class' => 'visually-hidden',
            ]) .
            Html::endTag('div');
    }

    public function getTip(): ?string
    {
        $tip = $this->evaluate($this->tip);

        if (!$tip) {
            return null;
        }

        return Notice::make()
            ->id($this->getId() . '-tip')
            ->label('Tip:')
            ->message($tip)
            ->render();

    }

    public function getWarning(): ?string
    {
        $warning = $this->evaluate($this->warning);

        if (!$warning) {
            return null;
        }

        return Notice::make()
            ->id($this->getId() . '-warning')
            ->type('warning')
            ->label('Warnings:')
            ->message($warning)
            ->render();
    }

    public function getErrorList()
    {
        if (!$this->errors) {
            return null;
        }

        return Cp::renderTemplate('_includes/forms/errorList', [
            'id' => $this->getId() . '-errors',
            'errors' => $this->errors,
        ]);
    }

    public function getLabelExtra()
    {
        return $this->evaluate($this->labelExtra);
    }
}