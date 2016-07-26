<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\errors;

use yii\base\Exception;

/**
 * Class InvalidSubpathException
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.exceptions
 * @since     3.0
 */
class InvalidSubpathException extends Exception
{
    /**
     * @var string The invalid subpath
     */
    public $subpath;

    /**
     * Constructor.
     *
     * @param string  $subpath The invalid subpath
     * @param string  $message The error message
     * @param integer $code    The error code
     */
    public function __construct($subpath, $message = null, $code = 0)
    {
        $this->subpath = $subpath;

        if ($message === null) {
            $message = "Could not resolve the subpath “{$subpath}”.";
        }

        parent::__construct($message, $code);
    }
}
