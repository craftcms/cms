<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Enums;

use CraftCms\Cms\Asset\Events\AssetFileChanged;

/**
 * What happened to an existing asset’s file.
 *
 * @see AssetFileChanged
 * @since 6.0.0
 */
enum AssetFileChange: string
{
    /** A new file was saved over the asset, at the same location or a new one. */
    case Replaced = 'replaced';

    /** The file moved to another folder, volume or filename. */
    case Moved = 'moved';

    /** The file was deleted or moved to the trash. */
    case Deleted = 'deleted';

    /** The file was moved back from the trash when its asset was restored, to the location it was deleted from. */
    case Restored = 'restored';
}
