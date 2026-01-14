<?php

namespace craft\auth\passkeys;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Auth\Passkeys\CredentialRepository} instead.
     */
    class WebauthnServer
    {
    }
}

class_alias(\CraftCms\Cms\Auth\Passkeys\WebauthnServer::class, WebauthnServer::class);
