<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Cp\Components\Radio;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Controls\Lightswitch;
use CraftCms\Cms\Ui\Enums\ChoicePresentation;
use CraftCms\Cms\Ui\Nodes\Field as UiField;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use Override;

use function CraftCms\Cms\t;

/**
 * RadioButtons represents a Radio Buttons field.
 *
 * @since 6.0.0
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
    public function settingsUi(UiContext $context = new UiContext): Ui
    {
        return parent::settingsUi($context)->add(
            UiField::make(t('Icons only'))
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
    public function uiControl(FieldContext $context): Control
    {
        $control = parent::uiControl($context);

        if ($control instanceof Choice) {
            $control->iconsOnly($this->iconsOnly);
        }

        return $control;
    }

    #[Override]
    protected function uiPresentation(): ChoicePresentation
    {
        return ChoicePresentation::Buttons;
    }
}
