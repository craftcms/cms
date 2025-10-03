<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\auth\sso\ProviderInterface;

/**
 * SSO event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
class SsoEvent extends UserEvent
{
    /**
     * @var ProviderInterface The provider associated with the event.
     */
    public ProviderInterface $provider;
}
