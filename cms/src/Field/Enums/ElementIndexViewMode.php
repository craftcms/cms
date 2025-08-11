<?php

namespace CraftCms\Cms\Field\Enums;

/**
 * ElementIndexViewMode defines the element index view modes supported in core.
 *
 * @since 6.0.0
 */
enum ElementIndexViewMode: string
{
    case Cards = 'cards';
    case Structure = 'structure';
    case Table = 'table';
    case Thumbs = 'thumbs';
}
