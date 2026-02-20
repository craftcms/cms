<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\filters;

use Craft;
use yii\web\UnauthorizedHttpException;

/**
 * Trait BasicHttpAuthTrait
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.5.0
 */
trait BasicHttpAuthTrait
{
    /**
     * Detach behavior and manually handle exception so error handling
     * isn't called recursively when already handling an exception (e.g. 404s)
     */
    public function handleFailure($response): void
    {
        $this->detach();

        Craft::$app->getErrorHandler()->handleException(
            new UnauthorizedHttpException('Your request was made with invalid credentials.'),
        );
    }
}
