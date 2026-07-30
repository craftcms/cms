<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Enums;

use CraftCms\Cms\Support\Facades\I18N;

use function CraftCms\Cms\t;

/**
 * ElementIndexViewMode defines the element index view modes supported in core.
 */
enum ElementIndexViewMode: string
{
    case Structure = 'structure';
    case Table = 'table';
    case Thumbs = 'thumbs';
    case Cards = 'cards';

    public function title(): string
    {
        return match ($this) {
            self::Cards => t('Display as cards'),
            self::Structure => t('Display in a structured table'),
            self::Table => t('Display in a table'),
            self::Thumbs => t('Display as thumbnails'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Cards => 'custom-icons/element-cards',
            self::Structure => I18N::getLocale()->getOrientation() === 'rtl'
                ? 'structurertl'
                : 'structure',
            self::Table => 'list',
            self::Thumbs => 'grid',
        };
    }

    /** @return array{mode: string, title: string, icon: string} */
    public function toArray(): array
    {
        return [
            'mode' => $this->value,
            'title' => $this->title(),
            'icon' => $this->icon(),
        ];
    }
}
