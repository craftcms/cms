<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Passkeys;

use CraftCms\Cms\Auth\Models\WebAuthn;
use CraftCms\Cms\Support\Facades\Users;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialUserEntity;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

readonly class CredentialRepository
{
    public function __construct(
        private Passkeys $passkeys,
    ) {}

    /**
     * Finds a webauthn record in the database for given id and returns the CredentialRecord for its credential value.
     */
    public function findOneByCredentialId(string $publicKeyCredentialId): ?CredentialRecord
    {
        $model = $this->findByCredentialId($publicKeyCredentialId);

        if (! $model) {
            return null;
        }

        $serializer = $this->passkeys->webauthnServer()->getSerializer();
        $credentialRecord = $serializer->deserialize(
            $model->credential,
            CredentialRecord::class,
            'json',
        );

        // if the userHandle is already a fully decoded user UID it means it was created with webauthn v4;
        // in that case, we should be able to find user by it
        $found = Users::getUserByUid($credentialRecord->userHandle) !== null;

        // and if that's the case, we want to base64 encode it again, so that we're comparing correct values
        if ($found) {
            $credentialRecord->userHandle = Base64UrlSafe::encodeUnpadded($credentialRecord->userHandle);
        }

        return $credentialRecord;
    }

    /**
     * Finds all webauthn records for given user and returns an array of CredentialRecords for their credential values.
     *
     * @return list<CredentialRecord>
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        $user = Users::getUserByUid($publicKeyCredentialUserEntity->id);

        if (! $user?->id) {
            return [];
        }

        $serializer = $this->passkeys->webauthnServer()->getSerializer();

        return WebAuthn::query()
            ->where('userId', $user->id)
            ->get()
            ->map(fn (WebAuthn $record) => $serializer->deserialize(
                $record->credential,
                CredentialRecord::class,
                'json',
            ))
            ->all();
    }

    public function savedNamedCredentialSource(CredentialRecord $credentialRecord, ?string $credentialName = null): void
    {
        $publicKeyCredentialId = $credentialRecord->publicKeyCredentialId;
        $model = $this->findByCredentialId($publicKeyCredentialId);

        if (! $model) {
            $model = new WebAuthn;
            $model->userId = currentUser()?->getCraftUserId();
            $model->credentialName = ! empty($credentialName) ? $credentialName : t('Secure credential');
            $model->credentialId = Base64UrlSafe::encodeUnpadded($publicKeyCredentialId);
        }

        $model->dateLastUsed = now();
        $model->credential = $this->passkeys->webauthnServer()->getSerializer()->serialize($credentialRecord, 'json');
        $model->save();
    }

    public function saveCredentialSource(CredentialRecord $credentialRecord): void
    {
        $this->savedNamedCredentialSource($credentialRecord);
    }

    private function findByCredentialId(string $publicKeyCredentialId): ?WebAuthn
    {
        return WebAuthn::query()
            ->where('credentialId', Base64UrlSafe::encodeUnpadded($publicKeyCredentialId))
            ->first();
    }
}
