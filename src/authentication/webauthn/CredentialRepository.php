<?php
declare(strict_types=1);

namespace craft\authentication\webauthn;

use Craft;
use craft\authentication\type\mfa\WebAuthn;
use craft\db\Table;
use craft\errors\MissingComponentException;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\records\AuthWebAuthn;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;

class CredentialRepository implements PublicKeyCredentialSourceRepository
{
    /**
     * Find a credential by its ID.
     *
     * @param string $publicKeyCredentialId
     * @return PublicKeyCredentialSource|null
     */
    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        $record = AuthWebAuthn::findOne(['credentialId' => base64_encode($publicKeyCredentialId)]);
        if ($record) {
            return PublicKeyCredentialSource::createFromArray(Json::decodeIfJson($record->credential));
        }

        return null;
    }

    /**
     * Find all credentials for a given user entity.
     *
     * @return PublicKeyCredentialSource[]
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        // Get the user ID by their UID.
        $userId = Db::idByUid(Table::ELEMENTS, $publicKeyCredentialUserEntity->getId());

        $sources = [];
        if ($userId) {
            $records = AuthWebAuthn::findAll(['userId' => $userId]);
            foreach ($records as $record) {
                $sources[] = PublicKeyCredentialSource::createFromArray(Json::decodeIfJson($record->credential));
            }
        }

        return $sources;
    }

    /**
     * Save a named credential source.
     *
     * @param string $credentialName
     * @param PublicKeyCredentialSource $publicKeyCredentialSource
     * @throws MissingComponentException
     */
    public function saveNamedCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource, string $credentialName = ''): void
    {
        // The credential gets re-saved on use. Allow setting userId and credentialId only for new credentials.
        if (!($record = AuthWebAuthn::findOne(['credentialId' => base64_encode($publicKeyCredentialSource->getPublicKeyCredentialId())]))) {
            $record = new AuthWebAuthn();
            $record->userId = Db::idByUid(Table::ELEMENTS, $publicKeyCredentialSource->getUserHandle());
            $record->name = !empty($credentialName) ? $credentialName : Craft::t('app', 'Secure credentials');
            $record->credentialId = base64_encode($publicKeyCredentialSource->getPublicKeyCredentialId());
        }

        $record->dateLastUsed = Db::prepareDateForDb($publicKeyCredentialSource->getCounter());
        $record->credential = Json::encode($publicKeyCredentialSource);
        $record->save();

        Craft::$app->getSession()->remove(WebAuthn::WEBAUTHN_CREDENTIAL_OPTION_KEY);
        Craft::$app->getSession()->remove(WebAuthn::WEBAUTHN_CREDENTIAL_REQUEST_OPTION_KEY);
    }

    /**
     * Save a credential source.
     *
     * @param PublicKeyCredentialSource $publicKeyCredentialSource
     * @throws MissingComponentException
     */
    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $this->saveNamedCredentialSource($publicKeyCredentialSource);
    }
}
