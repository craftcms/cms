<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Enums;

use CraftCms\Cms\Cp\Concerns\CanSelect;

use function CraftCms\Cms\t;

/**
 * What an import should do when an incoming file has the same filename as an existing asset in the target folder.
 *
 * @since 6.0.0
 */
enum ImportFileConflict: string
{
    use CanSelect;

    case UseExisting = 'useExisting';
    case Replace = 'replace';
    case CreateNew = 'createNew';

    public function label(): string
    {
        return match ($this) {
            self::UseExisting => t('Use the existing asset'),
            self::Replace => t('Replace the existing file with the incoming one'),
            self::CreateNew => t('Create a new asset for the incoming file'),
        };
    }
}
