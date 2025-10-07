<?php

namespace CraftCms\Cms\Element\Enums;

/**
 * AttributeStatus defines all possible attribute/field statuses for elements.
 */
enum AttributeStatus: string
{
    case Modified = 'modified';
    case Outdated = 'outdated';
}
