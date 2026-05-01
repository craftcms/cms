<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * RegisterCpSettingsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Cp\Events\RegisterCpSettings} or {@see \CraftCms\Cms\Cp\Events\RegisterReadonlyCpSettings} instead.
 */
class RegisterCpSettingsEvent extends Event
{
    /**
     * @var array The registered control panel settings
     */
    public array $settings = [];
}
