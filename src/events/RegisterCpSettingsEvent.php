<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * RegisterCpSettingsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1
 */
class RegisterCpSettingsEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array The registered CP settings
     */
    public $settings = [];
}
