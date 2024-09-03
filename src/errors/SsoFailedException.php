<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use craft\auth\sso\ProviderInterface;
use craft\elements\User;
use Throwable;
use yii\base\Exception;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
class SsoFailedException extends Exception
{
    /**
     * @var ProviderInterface
     */
    public ProviderInterface $provider;

    /**
     * @var User|null
     */
    public ?User $identity;

    /**
     * Constructor
     *
     * @param ProviderInterface $provider
     * @param User|null $identity
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(ProviderInterface $provider, ?User $identity = null, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        $this->provider = $provider;
        $this->identity = $identity;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Auth failed';
    }
}
