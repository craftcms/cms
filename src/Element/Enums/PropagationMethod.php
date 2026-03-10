<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Enums;

use CraftCms\Cms\Cp\Concerns\CanSelect;

use function CraftCms\Cms\t;

/**
 * PropagationMethod defines all possible site propagation methods for element values.
 */
enum PropagationMethod: string
{
    use CanSelect;

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

    public function label(): string
    {
        return match ($this) {
            self::None => t('Only save entries to the site they were created in'),
            self::SiteGroup => t('Save entries to other sites in the same site group'),
            self::Language => t('Save entries to other sites with the same language'),
            self::All => t('Save entries to all sites enabled for this section'),
            self::Custom => t('Let each entry choose which sites it should be saved to'),
        };
    }
}
