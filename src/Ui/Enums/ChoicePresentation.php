<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Enums;

enum ChoicePresentation: string
{
    /**
     * A multi-select listing only the chosen options, each with a Remove
     * button, with the rest in an “Add item” menu.
     */
    case AddMenu = 'add-menu';
    case Buttons = 'buttons';
    /** Radios drawn as cards, each option's icon shown large above its label. */
    case Cards = 'cards';
    case Checkboxes = 'checkboxes';
    case Radios = 'radios';
    case Select = 'select';
}
