<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\sso;

use Craft;
use craft\base\Component;
use craft\enums\CmsEdition;

/**
 * BaseProvider provides a base implementation for identity providers.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @internal
 * @since 5.3.0
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
        Craft::$app->requireEdition(CmsEdition::Enterprise);
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
