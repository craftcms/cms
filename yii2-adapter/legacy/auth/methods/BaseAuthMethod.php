<?php

namespace craft\auth\methods;

/**
 * BaseAuthMethod provides a base implementation of an authentication method.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 * @deprecated 6.0.0
 */
abstract class BaseAuthMethod extends \CraftCms\Cms\Auth\Methods\BaseAuthMethod
{
    use \craft\base\LegacyEventConstants;
}
