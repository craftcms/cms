<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter;

use Craft;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB as DbFacade;
use Illuminate\Support\Traits\ForwardsCalls;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\web\IdentityInterface;

/**
 * @mixin User
 */
final class IdentityWrapper implements IdentityInterface
{
    use ForwardsCalls;

    public function __construct(
        private readonly User $user,
    ) {
    }

    public static function __callStatic(string $name, array $arguments)
    {
        return User::$name($arguments);
    }

    public function __get($name)
    {
        return $this->user->$name;
    }

    public function __set($name, $value): void
    {
        $this->user->$name = $value;
    }

    public function __isset($name): bool
    {
        return isset($this->user->$name);
    }

    public function __call($name, $params)
    {
        return $this->forwardDecoratedCallTo($this->user, $name, $params);
    }

    public static function findIdentity($id): ?self
    {
        $user = User::find()
            ->addSelect(['users.password'])
            ->id($id)
            ->status(null)
            ->one();

        if ($user === null) {
            return null;
        }

        // Only accept active users, unless they're being impersonated
        if (
            $user->getStatus() !== User::STATUS_ACTIVE &&
            !Craft::$app->getUser()->getImpersonator()
        ) {
            return null;
        }

        return new self($user);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    public function getId(): int|null
    {
        return $this->user->getId();
    }

    public function getAuthKey(): string
    {
        $token = Craft::$app->getUser()->getToken();

        if ($token === null) {
            throw new Exception('No user session token exists.');
        }

        $userAgent = Craft::$app->getRequest()->getUserAgent();

        // The auth key is a combination of the hashed token, its row's UID, and the user agent string
        return Json::encode([
            $token,
            null,
            md5($userAgent),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function validateAuthKey($authKey): bool
    {
        $data = Json::decodeIfJson($authKey);

        if (!is_array($data) || count($data) !== 3 || !isset($data[0], $data[2])) {
            return false;
        }

        [$token, , $userAgent] = $data;

        if (!$this->_validateUserAgent($userAgent)) {
            return false;
        }

        $tokenId = DbFacade::table(Table::SESSIONS)
            ->where('token', $token)
            ->where('userId', $this->id)
            ->value('id');

        if (!$tokenId) {
            return false;
        }

        // Update the session row's dateUpdated value so it doesn't get GC'd
        DbFacade::table(Table::SESSIONS)
            ->where('id', $tokenId)
            ->update([
                'dateUpdated' => now(),
            ]);

        return true;
    }

    /**
     * Validates a cookie's stored user agent against the current request's user agent string,
     * if the 'requireMatchingUserAgentForSession' config setting is enabled.
     */
    private function _validateUserAgent(string $userAgent): bool
    {
        if (!Cms::config()->requireMatchingUserAgentForSession) {
            return true;
        }

        $requestUserAgent = Craft::$app->getRequest()->getUserAgent();

        if (!$requestUserAgent) {
            return false;
        }

        if (!hash_equals($userAgent, md5($requestUserAgent))) {
            Craft::warning('Tried to restore session from the the identity cookie, but the saved user agent (' . $userAgent . ') does not match the current request’s (' . $requestUserAgent . ').', __METHOD__);

            return false;
        }

        return true;
    }
}
