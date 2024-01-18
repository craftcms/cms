<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\passkeys;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\records\WebAuthn;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;

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
        $record = $this->_findByCredentialId($publicKeyCredentialId);

        if ($record) {
            return PublicKeyCredentialSource::createFromArray(Json::decodeIfJson($record->credential));
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        // Get the user ID by their UID.
        $user = Craft::$app->getUsers()->getUserByUid($publicKeyCredentialUserEntity->getId());

        $keySources = [];
        if ($user && $user->id) {
            $records = WebAuthn::findAll(['userId' => $user->id]);
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
        $record = $this->_findByCredentialId($publicKeyCredentialId);

        if (!$record) {
            $record = new WebAuthn();
            $record->userId = Craft::$app->getUser()->getIdentity()?->id;
            $record->credentialName = !empty($credentialName) ? $credentialName : Craft::t('app', 'Secure credential');
            $record->credentialId = Base64UrlSafe::encodeUnpadded($publicKeyCredentialId);
        }

        $record->dateLastUsed = Db::prepareDateForDb(DateTimeHelper::currentTimeStamp());
        $record->credential = Json::encode($publicKeyCredentialSource);
        $record->save();
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
     *
     * @param string $publicKeyCredentialId
     * @return WebAuthn|null
     */
    private function _findByCredentialId(string $publicKeyCredentialId): ?WebAuthn
    {
        return WebAuthn::findOne(['credentialId' => Base64UrlSafe::encodeUnpadded($publicKeyCredentialId)]);
    }
}
