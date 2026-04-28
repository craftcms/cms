<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Passkeys;

use CraftCms\Cms\Auth\Models\WebAuthn;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\Support\Json;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

use function CraftCms\Cms\t;

readonly class CredentialRepository
{
    public function __construct(
        private Passkeys $passkeys,
    ) {}

    /**
     * Finds a webauthn record in the database for given id and returns the PublicKeyCredentialSource for its credential value.
     */
    public function findOneByCredentialId(string $publicKeyCredentialId, bool $checkOldUserHandle = false): ?PublicKeyCredentialSource
    {
        $model = $this->findByCredentialId($publicKeyCredentialId);

        if (! $model) {
            return null;
        }

        $serializer = $this->passkeys->webauthnServer()->getSerializer();
        $credentialSource = $serializer->deserialize(
            $model->credential,
            PublicKeyCredentialSource::class,
            'json',
        );

        if (! $checkOldUserHandle) {
            return $credentialSource;
        }

        // if the record was created using webauthn v4 then the credential was run through Json::encode() before storing in the DB
        // deserializing such value base64 decodes the userHandle too, and leads to user handle mismatch;
        // so, if we failed to log user in based on the handle mismatch exception, we'll try again but using the encoded (old) handle
        $credential = Json::decodeIfJson($model->credential);
        $credentialSource->userHandle = $credential['userHandle'];

        return $credentialSource;
    }

    /**
     * Finds all webauthn records for given user and returns an array of PublicKeyCredentialSources for their credential values.
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
                PublicKeyCredentialSource::class,
                'json',
            ))
            ->all();
    }

    public function savedNamedCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource, ?string $credentialName = null): void
    {
        $publicKeyCredentialId = $publicKeyCredentialSource->publicKeyCredentialId;
        $model = $this->findByCredentialId($publicKeyCredentialId);

        if (! $model) {
            $model = new WebAuthn;
            $model->userId = auth('craft')->user()?->id;
            $model->credentialName = ! empty($credentialName) ? $credentialName : t('Secure credential');
            $model->credentialId = Base64UrlSafe::encodeUnpadded($publicKeyCredentialId);
        }

        $model->dateLastUsed = now();
        $model->credential = $this->passkeys->webauthnServer()->getSerializer()->serialize($publicKeyCredentialSource, 'json');
        $model->save();
    }

    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $this->savedNamedCredentialSource($publicKeyCredentialSource);
    }

    private function findByCredentialId(string $publicKeyCredentialId): ?WebAuthn
    {
        return WebAuthn::query()
            ->where('credentialId', Base64UrlSafe::encodeUnpadded($publicKeyCredentialId))
            ->first();
    }
}
