<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Mixins;

use Closure;
use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Json;
use SensitiveParameter;
use Webauthn\PublicKeyCredentialRequestOptions;

class UserMixin
{
    public function authenticate(): Closure
    {
        return function(#[SensitiveParameter] string $password) {
            Deprecator::log('User-authenticate', 'Calling ->authenticate on a User is deprecated. Use app(Auth::class)->authenticate() instead.');

            /** @phpstan-ignore-next-line */
            return app(AuthMethods::class)->authenticate($this, [
                'password' => $password,
            ]);
        };
    }

    public function authenticateWithPasskey(): Closure
    {
        return function(PublicKeyCredentialRequestOptions|array|string $requestOptions, string $response): bool {
            Deprecator::log('User-authenticateWithPasskey', 'Calling ->authenticateWithPasskey on a User is deprecated. Use app(AuthMethods::class)->authenticateWithPasskey() instead.');

            if (is_array($requestOptions)) {
                $requestOptions = Json::encode($requestOptions);
            }

            if (!is_string($requestOptions)) {
                $requestOptions = app(Passkeys::class)
                    ->webauthnServer()
                    ->getSerializer()
                    ->serialize($requestOptions, 'json');
            }

            /** @phpstan-ignore-next-line */
            return app(AuthMethods::class)->authenticateWithPasskey($this, $requestOptions, $response);
        };
    }

    public function handleInvalidLoginParam(): Closure
    {
        return function(): void {
            Deprecator::log('User-handleInvalidLoginParam', 'Calling ->handleInvalidLoginParam on a User is deprecated. Use app(Auth::class)->handleInvalidLogin($user) instead.');

            /** @phpstan-ignore-next-line */
            app(AuthMethods::class)->handleInvalidLogin($this);
        };
    }

    public function getFullName(): Closure
    {
        return function(): ?string {
            Deprecator::log('User-getFullName', 'Calling ->getFullName on a User is deprecated. Use $user->fullName instead.');

            /** @phpstan-ignore-next-line */
            return $this->fullName;
        };
    }
}
