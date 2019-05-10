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
 * Unit tests for ExampleArrayble
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class ExampleArrayble implements Arrayable
{
    use ArrayableTrait;

    public $exampleArrayableParam;

    public $extraField;

    public function extraFields(): array
    {
        return [
            'extraField'
        ];
    }

}