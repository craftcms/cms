<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\console;

use craft\base\Model;

/**
 * Class ConsoleTestItem
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.1
 */
class ConsoleTestItem extends Model
{
    // Public properties
    // =========================================================================

    /**
     * @var string
     */
    public $desiredOutput;

    /**
     * @var array
     */
    public $options;

    /**
     * @var string
     */
    public $prompt;

    /**
     * @var string
     */
    public $type;
}
