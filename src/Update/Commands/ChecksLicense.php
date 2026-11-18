<?php

declare(strict_types=1);

namespace CraftCms\Cms\Update\Commands;

use CraftCms\Cms\License\License;
use CraftCms\Cms\Support\Api;

use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

/**
 * @internal
 */
trait ChecksLicense
{
    protected function checkLicense(): ?int
    {
        $license = app(License::class);

        if ($license->key()) {
            return null;
        }

        if ($license->environmentKey() !== null) {
            $this->components->error('The license key set by CRAFT_LICENSE_KEY is invalid.');

            return self::FAILURE;
        }

        $this->components->warn('No license key found.');

        $email = text(
            label: 'Enter your email address to request a new license key',
            required: true,
            validate: ['email:strict']
        );

        spin(
            fn () => app(Api::class)->getLicenseInfo(headers: [
                'X-Craft-User-Email' => $email,
            ]),
            'Requesting new license',
        );

        if (app(License::class)->key() === null) {
            $this->components->error('License key creation was unsuccessful.');

            return self::FAILURE;
        }

        $this->components->success('License key requested successfully.');

        return null;
    }
}
