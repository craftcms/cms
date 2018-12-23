<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */
namespace craft\test\mockclasses\models;


use craft\base\Model;

/**
 * Class ExampleModel.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class ExampleModel extends Model
{
    public $exampleParam;
    public $exampleDateParam;

    public $dateCreated;
    public $dateUpdated;

    /**
     * @return array
     */
    public function datetimeAttributes(): array
    {
        $attr = parent::datetimeAttributes();

        $attr[] = 'exampleDateParam';

        return $attr;
    }
}