<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use craft\auth\ProviderInterface;
use Throwable;
use yii\base\Exception;
use yii\web\IdentityInterface;

/**
 * Class RouteNotFoundException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class AuthFailedException extends Exception
{
    /**
     * @var ProviderInterface
     */
    public ProviderInterface $provider;

    /**
     * @var ?IdentityInterface
     */
    public ?IdentityInterface $identity;

    /**
     * Constructor
     *
     * @param ProviderInterface $provider
     * @param IdentityInterface|null $identity
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(ProviderInterface $provider, ?IdentityInterface $identity = null, string $message = '', int $code = 0, ?Throwable $previous = null)
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
