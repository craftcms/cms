<?php

namespace CraftCms\Cms\Element\Enums;

/**
 * PropagationMethod defines all possible site propagation methods for element values.
 */
enum PropagationMethod: string
{
    case None = 'none';
    case SiteGroup = 'siteGroup';
    case Language = 'language';
    case Custom = 'custom';
    case All = 'all';
}
