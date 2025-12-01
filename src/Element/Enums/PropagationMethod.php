<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Enums;

/**
 * PropagationMethod defines all possible site propagation methods for element values.
 */
enum PropagationMethod: string
{
    /**
     * Only save entries in the site they were created in
     */
    case None = 'none';

    /**
     * Save entries to other sites in the same site group
     */
    case SiteGroup = 'siteGroup';

    /**
     * Save entries to other sites with the same language
     */
    case Language = 'language';

    /**
     * Let each entry choose which sites it should be saved to
     */
    case Custom = 'custom';

    /*
     * Save entries to all sites supported by the owner element
     */
    case All = 'all';
}
