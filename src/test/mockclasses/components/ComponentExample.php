<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\mockclasses\components;

use craft\base\Component;

/**
 * Class ComponentExample.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ComponentExample extends Component
{
    /**
     * @return string
     */
    public static function displayName(): string
    {
        return 'Component example';
    }
}
