<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Passkeys;

use CraftCms\Cms\Auth\Models\WebAuthn;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\Support\Json;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;

use function CraftCms\Cms\t;

final class CredentialRepository implements PublicKeyCredentialSourceRepository
{
    /**
     * {@inheritdoc}
     */
    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        $model = $this->findByCredentialId($publicKeyCredentialId);

        if ($model) {
            return PublicKeyCredentialSource::createFromArray(Json::decodeIfJson($model->credential));
        }

        return null;
    }

    /**
     * {@inheritdoc}
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
     */
    public function savedNamedCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource, ?string $credentialName = null): void
    {
        $publicKeyCredentialId = $publicKeyCredentialSource->getPublicKeyCredentialId();
        $model = $this->findByCredentialId($publicKeyCredentialId);

        if (! $model) {
            $model = new WebAuthn;
            $model->userId = auth('craft')->user()?->id;
            $model->credentialName = ! empty($credentialName) ? $credentialName : t('Secure credential');
            $model->credentialId = Base64UrlSafe::encodeUnpadded($publicKeyCredentialId);
        }

        $model->dateLastUsed = now();
        $model->credential = Json::encode($publicKeyCredentialSource);
        $model->save();
    }

    /**
     * {@inheritdoc}
     */
    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $this->savedNamedCredentialSource($publicKeyCredentialSource);
    }

    private function findByCredentialId(string $publicKeyCredentialId): ?WebAuthn
    {
        return WebAuthn::where('credentialId', Base64UrlSafe::encodeUnpadded($publicKeyCredentialId))->first();
    }
}
