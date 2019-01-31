<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use craft\elements\User;
use Throwable;
use yii\base\Exception;

/**
 * Class UserLockedException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.7
 */
class UserLockedException extends Exception
{
    /**
     * @var User The user that's locked.
     */
    public $user;

    /**
     * Constructor
     *
     * @param User $user
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(User $user, string $message = '', int $code = 0, Throwable $previous = null)
    {
        $this->user = $user;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'User locked';
    }
}
