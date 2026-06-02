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
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * Passkey credential repository.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class CredentialRepository
{
    /**
     * Finds a webauthn record in the database for given id and returns the PublicKeyCredentialSource for its credential value.
     */
    public function findOneByCredentialId(string $publicKeyCredentialId, bool $checkOldUserHandle = false): ?PublicKeyCredentialSource
    {
        $record = $this->_findByCredentialId($publicKeyCredentialId);

        if ($record) {
            $serializer = Craft::$app->getAuth()->webauthnServer()->getSerializer();

            $publicKeyCredentialSource = $serializer->deserialize(
                $record->credential,
                PublicKeyCredentialSource::class,
                'json',
            );

            // if the record was created using webauthn v4 then the credential was run through Json::encode() before storing in the DB
            // deserialising such value base64 decodes the userHandle too, and leads to user handle mismatch;
            // so, if we failed to log user in based on the handle mismatch exception, we'll try again but using the encoded (old) handle
            if ($checkOldUserHandle) {
                $credential = Json::decodeIfJson($record->credential);
                $publicKeyCredentialSource->userHandle = $credential['userHandle'];
            }

            return $publicKeyCredentialSource;
        }

        return null;
    }

    /**
     * Finds all webauthn records for given user and returns an array of PublicKeyCredentialSources for their credential values.
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        // Get the user ID by their UID.
        $user = Craft::$app->getUsers()->getUserByUid($publicKeyCredentialUserEntity->id);

        $keySources = [];
        if ($user && $user->id) {
            $records = WebAuthn::findAll(['userId' => $user->id]);
            $serializer = Craft::$app->getAuth()->webauthnServer()->getSerializer();
            foreach ($records as $record) {
                $keySources[] = $serializer->deserialize(
                    $record->credential,
                    PublicKeyCredentialSource::class,
                    'json',
                );
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
        $publicKeyCredentialId = $publicKeyCredentialSource->publicKeyCredentialId;
        $record = $this->_findByCredentialId($publicKeyCredentialId);

        if (!$record) {
            $record = new WebAuthn();
            $record->userId = Craft::$app->getUser()->getIdentity()?->id;
            $record->credentialName = !empty($credentialName) ? $credentialName : Craft::t('app', 'Secure credential');
            $record->credentialId = Base64UrlSafe::encodeUnpadded($publicKeyCredentialId);
        }

        $record->dateLastUsed = Db::prepareDateForDb(DateTimeHelper::currentTimeStamp());
        $record->credential = Craft::$app->getAuth()->webauthnServer()->getSerializer()->serialize($publicKeyCredentialSource, 'json');
        $record->save();
    }

    /**
     * Saves credential source in the database
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
