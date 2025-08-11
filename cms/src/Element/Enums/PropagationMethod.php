<?php

namespace CraftCms\Cms\Element\Enums;

/**
 * PropagationMethod defines all possible site propagation methods for element values.
 *
 * @since 6.0.0
 */
enum PropagationMethod: string
{
    case None = 'none';
    case SiteGroup = 'siteGroup';
    case Language = 'language';
    case Custom = 'custom';
    case All = 'all';
}
