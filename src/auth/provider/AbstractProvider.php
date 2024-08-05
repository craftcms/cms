<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\provider;

use craft\base\Component;

/**
 *
 */
abstract class AbstractProvider extends Component implements ProviderInterface
{
    use AuthProviderTrait;

    /**
     * @return $this
     */
    protected function getProvider(): static
    {
        return $this;
    }
}
