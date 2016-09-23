<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\enums;

/**
 * The PatchManifestFileAction class is an abstract class that defines all of the different path manifest file actions
 * that are available in Craft during an auto-update.
 *
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
abstract class PatchManifestFileAction extends BaseEnum
{
    // Constants
    // =========================================================================

    const Add = 'Add';
    const Remove = 'Remove';
}
