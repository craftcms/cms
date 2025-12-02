<?php

declare(strict_types=1);

namespace CraftCms\Cms\Updates\Commands;

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
        if (resolve(License::class)->key()) {
            return null;
        }

        if (defined('CRAFT_LICENSE_KEY')) {
            $this->components->error('The license key defined by the CRAFT_LICENSE_KEY PHP constant is invalid.');

            return self::FAILURE;
        }

        $this->components->warn('No license key found.');

        $email = text(
            label: 'Enter your email address to request a new license key',
            required: true,
            validate: ['email:strict']
        );

        spin(
            fn () => resolve(Api::class)->getLicenseInfo(headers: [
                'X-Craft-User-Email' => $email,
            ]),
            'Requesting new license',
        );

        if (resolve(License::class)->key() === null) {
            $this->components->error('License key creation was unsuccessful.');

            return self::FAILURE;
        }

        $this->components->success('License key requested successfully.');

        return null;
    }
}
