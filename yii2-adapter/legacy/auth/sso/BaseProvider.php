<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\sso;

use craft\base\Component;
use CraftCms\Cms\Edition;

/**
 * BaseProvider provides a base implementation for identity providers.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @internal
 * @since 5.3.0
 * @deprecated 6.0.0 use the Laravel Socialite {@see \CraftCms\Cms\Auth\OAuth\OAuth} implementation instead.
 */
abstract class BaseProvider extends Component implements ProviderInterface
{
    use ProviderTrait;

    /**
     * Constructor
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        Edition::require(Edition::Enterprise);
        parent::__construct($config);
    }

    /**
     * @return $this
     */
    protected function getProvider(): static
    {
        return $this;
    }
}
