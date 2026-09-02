<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Passkeys;

use CraftCms\Cms\Auth\Models\WebAuthn;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\Support\Json;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\CredentialRecord;
use Webauthn\Exception\InvalidDataException;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\Util\Base64;

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
    public function findOneByCredentialId(string $publicKeyCredentialId, bool $checkOldUserHandle = false): ?CredentialRecord
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

        if (! $checkOldUserHandle) {
            return $credentialRecord;
        }

        // if the record was created using webauthn v4 then the credential was run through Json::encode() before storing in the DB,
        // without the extra base64 encoding pass that webauthn 5's CredentialRecordDenormalizer::normalize() applies;
        // deserializing such a value therefore leaves the userHandle one decode pass short of where it should be,
        // so if we failed to log the user in based on the handle mismatch exception, we'll try again, decoding the
        // stored (old, singly-encoded) handle ourselves to get it to the same (raw) form the assertion response is in
        $credential = Json::decodeIfJson($model->credential);
        try {
            $credentialRecord->userHandle = Base64::decode($credential['userHandle']);
        } catch (InvalidDataException) {
            // not base64-encoded after all; fall back to using it as-is
            $credentialRecord->userHandle = $credential['userHandle'];
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
