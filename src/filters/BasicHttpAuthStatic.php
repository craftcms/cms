<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\filters;

use Craft;
use craft\helpers\App;
use yii\base\InvalidConfigException;
use yii\filters\auth\HttpBasicAuth;

/**
 * Filter for adding basic HTTP authentication with static credentials to site requests.
 *
 * @see https://www.yiiframework.com/doc/api/2.0/yii-filters-auth-httpbasicauth
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.5.0
 */
class BasicHttpAuthStatic extends HttpBasicAuth
{
    use SiteFilterTrait, BasicHttpAuthTrait;

    public ?string $username = null;
    public ?string $password = null;

    /**
     * @inheritdoc
     */
    public $realm;

    /**
     * @inheritDoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config + [
            'username' => App::env('CRAFT_HTTP_BASIC_AUTH_USERNAME'),
            'password' => App::env('CRAFT_HTTP_BASIC_AUTH_PASSWORD'),
            'realm' => Craft::$app->getSystemName(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function beforeAction($action): bool
    {
        if (!$this->username || !$this->password) {
            throw new InvalidConfigException('Basic authentication is not configured.');
        }

        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($currentUser) {
            return true;
        }

        [$username, $password] = Craft::$app->getRequest()->getAuthCredentials();

        if ($username === $this->username && $password === $this->password) {
            return true;
        }

        $response = Craft::$app->getResponse();
        $this->challenge($response);
        $this->handleFailure($response);

        return false;
    }
}
