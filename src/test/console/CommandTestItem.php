<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\console;

use craft\base\Model;
use Traversable;

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
     * @var string|string[]|Traversable
     */
    public string|array|Traversable $desiredOutput;

    /**
     * @var array
     */
    public array $options;

    /**
     * @var string
     */
    public string $prompt;

    /**
     * @var string
     */
    public string $type;

    /**
     * @var string
     */
    public string $message;

    /**
     * @var bool
     */
    public bool $default;

    /**
     * @var mixed
     */
    public mixed $returnValue = null;

    /**
     * @var bool
     */
    public bool $withScriptName;
}
