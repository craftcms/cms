<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\mockclasses\arrayable;

use yii\base\Arrayable;
use yii\base\ArrayableTrait;

/**
 * Class ExampleArrayble
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ExampleArrayble implements Arrayable
{
    // Traits
    // =========================================================================

    use ArrayableTrait;

    // Public Properties
    // =========================================================================

    /**
     * @var
     */
    public $exampleArrayableParam;

    /**
     * @var
     */
    public $extraField;

    // Public Methods
    // =========================================================================

    /**
     * @return array
     */
    public function extraFields(): array
    {
        return [
            'extraField'
        ];
    }

}
