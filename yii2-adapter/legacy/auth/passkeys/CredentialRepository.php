<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\passkeys;

use CraftCms\Cms\Auth\Models\WebAuthn;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\Support\Json;
use Illuminate\Support\Facades\Auth;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;
use function CraftCms\Cms\t;

/**
 * Passkey credential repository.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class CredentialRepository implements PublicKeyCredentialSourceRepository
{
    /**
     * @inheritdoc
     */
    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        $model = $this->_findByCredentialId($publicKeyCredentialId);

        if ($model) {
            return PublicKeyCredentialSource::createFromArray(Json::decodeIfJson($model->credential));
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        // Get the user ID by their UID.
        $user = Users::getUserByUid($publicKeyCredentialUserEntity->getId());

        $keySources = [];
        if ($user && $user->id) {
            $records = WebAuthn::where('userId', $user->id)->get();
            foreach ($records as $record) {
                $keySources[] = PublicKeyCredentialSource::createFromArray(Json::decodeIfJson($record->credential));
            }
        }

        return $keySources;
    }

    /**
     * Save credential source with name
     *
     * @param PublicKeyCredentialSource $publicKeyCredentialSource
     * @param string|null $credentialName
     */
    public function savedNamedCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource, ?string $credentialName = null): void
    {
        $publicKeyCredentialId = $publicKeyCredentialSource->getPublicKeyCredentialId();
        $model = $this->_findByCredentialId($publicKeyCredentialId);

        if (!$model) {
            $model = new WebAuthn();
            $model->userId = Auth::user()?->id;
            $model->credentialName = !empty($credentialName) ? $credentialName : t('Secure credential');
            $model->credentialId = Base64UrlSafe::encodeUnpadded($publicKeyCredentialId);
        }

        $model->dateLastUsed = now();
        $model->credential = Json::encode($publicKeyCredentialSource);
        $model->save();
    }

    /**
     * @inheritdoc
     */
    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $this->savedNamedCredentialSource($publicKeyCredentialSource);
    }

    /**
     * Find user by public key credential id
     */
    private function _findByCredentialId(string $publicKeyCredentialId): ?WebAuthn
    {
        return WebAuthn::where('credentialId', Base64UrlSafe::encodeUnpadded($publicKeyCredentialId))->first();
    }
}
