<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Enums;

/**
 * Where a field layout tab's contents are placed within edit forms.
 *
 * @since 6.0.0
 */
enum TabType: string
{
    case Content = 'content';
    case Sidebar = 'sidebar';
}
