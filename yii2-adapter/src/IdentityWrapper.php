<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter;

use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Utils;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB as DbFacade;
use Throwable;
use yii\base\Exception;
use yii\web\IdentityInterface;

class IdentityWrapper extends User implements IdentityInterface
{
    public function __construct($user)
    {
        foreach (Utils::getPublicProperties($user) as $attribute => $value) {
            try {
                $this->$attribute = $value;
            } catch (Throwable) {
                // Ignore
            }
        }
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
            !app(Impersonation::class)->isImpersonating()
        ) {
            return null;
        }

        return new self($user);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    public function getAuthKey(): string
    {
        $token = session()->id();

        if ($token === null) {
            throw new Exception('No user session token exists.');
        }

        $userAgent = request()->userAgent();

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

        [$token] = $data;

        $tokenId = DbFacade::table(Table::SESSIONS)
            ->where('token', $token)
            ->where('user_id', $this->id)
            ->value('id');

        if (!$tokenId) {
            return false;
        }

        // Update the session row's dateUpdated value so it doesn't get GC'd
        DbFacade::table(Table::SESSIONS)
            ->where('id', $tokenId)
            ->update(['last_activity' => now()]);

        return true;
    }
}
