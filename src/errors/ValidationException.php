<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\errors;

use craft\base\Model;
use yii\base\Exception;

/**
 * Class ValidationException
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.exceptions
 * @since     3.0
 */
class ValidationException extends Exception
{

    /**
     * @var Model|null
     */
    private $_model;

    /**
     * Set the model that failed the validation.
     *
     * @param Model $model
     *
     * @return void
     */
    public function setModel(Model $model)
    {
        $this->_model = $model;
    }

    /**
     * Get the model that failed the validation.
     *
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->_model;
    }
}
