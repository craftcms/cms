<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\test\mockclasses\models;

use craft\base\Model;
use DateTime;

/**
 * Class ExampleModel.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.2
 */
class ExampleModel extends Model
{
    /**
     * @var mixed
     */
    public mixed $exampleParam = null;

    /**
     * @var DateTime|string|null
     */
    public DateTime|string|null $exampleDateParam = null;

    /**
     * @var DateTime|null
     */
    public ?DateTime $dateCreated = null;

    /**
     * @var DateTime|null
     */
    public ?DateTime $dateUpdated = null;

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attr = parent::datetimeAttributes();
        $attr[] = 'exampleDateParam';

        return $attr;
    }
}
