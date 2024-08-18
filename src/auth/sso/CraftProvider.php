<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\sso;

use Craft;
use craft\web\View;
use yii\base\Exception;
use yii\web\Request;
use yii\web\Response;

/**
 * This provider is a wrapper for The PHP League's OAuth2 client providers.
 *
 * ---
 * ```php
 *
 * 'craft' => [
 *     'class' => \craft\auth\provider\CraftProvider::class,
 * ]
 * ```
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @internal
 * @since 5.3.0
 */
class CraftProvider extends BaseProvider
{
    /**
     * @inheritDoc
     */
    public function getSiteLoginHtml(): string
    {
        return Craft::$app->getView()->renderPageTemplate(
            "_login/form",
            [
                'provider' => $this,
                'actionPath' => 'auth/request',
            ],
            View::TEMPLATE_MODE_CP
        );
    }

    /**
     * @inheritDoc
     */
    public function handleRequest(Request $request, Response $response): Response
    {
        $loginName = $request->getBodyParam('username');
        $password = $request->getBodyParam('password');
        $rememberMe = (bool)$request->getBodyParam('rememberMe');

        throw new Exception("Request handling is not implemented");
    }

    /**
     * @inheritDoc
     */
    public function handleResponse(Request $request, Response $response): bool
    {
        throw new Exception("Response handling is not implemented");
    }
}
