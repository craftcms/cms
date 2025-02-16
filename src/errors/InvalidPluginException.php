<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use yii\base\Exception;

/**
 * Class InvalidPluginException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class InvalidPluginException extends Exception
{
    /**
     * Constructor.
     *
     * @param string $handle The plugin handle that doesn’t exist or doesn’t extend/implement [[\craft\base\PluginInterface]]
     * @param string|null $message The error message
     * @param int $code The error code
     */
    public function __construct(public string $handle, ?string $message = null, int $code = 0)
    {
        if ($message === null) {
            $message = "No plugin exists with the handle \"{$this->handle}\".";
        }

        parent::__construct($message, $code);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Invalid plugin';
    }
}
