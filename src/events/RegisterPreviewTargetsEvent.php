<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * RegisterPreviewTargetsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
class RegisterPreviewTargetsEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array The additional locations that should be available for previewing the element.
     */
    public $previewTargets;
}
