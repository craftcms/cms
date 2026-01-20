<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\filters;

use CraftCms\Cms\Cms;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Yii2Adapter\IdentityWrapper;
use Illuminate\Support\Facades\Hash;
use yii\filters\auth\HttpBasicAuth;
use yii\web\IdentityInterface;

/**
 * Filter for adding basic HTTP authentication user credentials to site requests.
 *
 * @see https://www.yiiframework.com/doc/api/2.0/yii-filters-auth-httpbasicauth
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.5.0
 */
class BasicHttpAuthLogin extends HttpBasicAuth
{
    use SiteFilterTrait, BasicHttpAuthTrait;

    /**
     * @inheritdoc
     */
    public $realm;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config + [
            'auth' => [$this, 'auth'],
            'realm' => Cms::systemName(),
        ]);
    }

    protected function auth($username, $password): ?IdentityInterface
    {
        if (!$username || !$password) {
            return null;
        }

        $user = User::find()->username($username)->first();

        /** @var ?IdentityWrapper $identity */
        $identity = $user ? new IdentityWrapper($user)->findIdentity($user->id) : null;

        if ($identity && Hash::check($password, $identity->password)) {
            return $identity;
        }

        return null;
    }
}
