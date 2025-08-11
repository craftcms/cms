<?php

namespace CraftCms\Cms\Element\Enums;

/**
 * AttributeStatus defines all possible attribute/field statuses for elements.
 *
 * @since 6.0.0
 */
enum AttributeStatus: string
{
    case Modified = 'modified';
    case Outdated = 'outdated';
}
