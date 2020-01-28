<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\console;

use craft\base\Model;

/**
 * Class CommandTestItem
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2.0
 */
class CommandTestItem extends Model
{
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

    /**
     * @var string
     */
    public $message;

    /**
     * @var bool
     */
    public $default;

    /**
     * @var mixed
     */
    public $returnValue;

    /**
     * @var string
     */
    public $withScriptName;
}
