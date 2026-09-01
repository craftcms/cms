<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Cp\Components\Button;
use CraftCms\Cms\Cp\Components\ButtonGroup as ButtonGroupComponent;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Field\Data\SingleOptionFieldData;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Enums\ChoicePresentation;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\DeltaRegistry;
use CraftCms\Cms\Support\Html;
use Override;

use function CraftCms\Cms\t;

/**
 * RadioButtons represents a Radio Buttons field.
 */
class ButtonGroup extends BaseOptionsField implements SortableFieldInterface
{
    #[Override]
    protected static bool $optionIcons = true;

    #[Override]
    public static function displayName(): string
    {
        return t('Button Group');
    }

    #[Override]
    public static function icon(): string
    {
        return 'hand-pointer';
    }

    /**
     * @var bool Whether buttons should only show their icons, hiding their text labels
     */
    public bool $iconsOnly = false;

    #[Override]
    public function settingsForm(FormContext $context = new FormContext): Form
    {
        return parent::settingsForm($context)->add(
            FormField::make(t('Icons only'))
                ->instructions(t('Whether buttons should only show their icons, hiding their text labels.'))
                ->control(Lightswitch::make('iconsOnly')->value($this->iconsOnly)),
        );
    }

    #[Override]
    public function useFieldset(): bool
    {
        return true;
    }

    #[Override]
    protected function formPresentation(): ChoicePresentation
    {
        return ChoicePresentation::Buttons;
    }

    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->_inputHtml($value, $element, false);
    }

    private function _inputHtml(SingleOptionFieldData $value, ?ElementInterface $element, bool $static): string
    {
        if (! $value->valid) {
            DeltaRegistry::setInitialValue($this->handle, null);
        }

        $id = $this->getInputId();
        $options = $this->translatedOptions(true, $value, $element);

        if ($this->iconsOnly) {
            foreach ($options as &$option) {
                if (! empty($option['icon']) || ($option['icon'] ?? null) === '0') {
                    $option['attributes']['title'] = $option['attributes']['aria']['label'] = $option['label'];
                    unset($option['label']);
                }
            }
            unset($option);
        }

        $selectedValue = $this->encodeValue($value);

        $buttons = [];
        foreach ($options as $option) {
            $optionValue = $option['value'] ?? null;
            $selected = $optionValue !== null && $optionValue == $selectedValue;

            $buttons[] = Button::make()
                ->label($option['label'] ?? null)
                ->icon($option['icon'] ?? null)
                ->variant('outline')
                ->active($selected)
                ->disabled($static)
                ->attributes(Arr::merge([
                    'class' => Html::explodeClass($option['class'] ?? []),
                    'value' => $optionValue,
                    'data' => ['value' => $optionValue],
                    'aria' => ['pressed' => $selected ? 'true' : 'false'],
                ], $option['attributes'] ?? []));
        }

        return ButtonGroupComponent::make()
            ->id($id)
            ->name($this->handle)
            ->value($selectedValue !== null ? (string) $selectedValue : null)
            ->disabled($static)
            ->buttons($buttons)
            ->toHtml();
    }
}
