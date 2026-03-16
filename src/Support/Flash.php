<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use function CraftCms\Cms\t;

class Flash
{
    public static function success(?string $default = null, array $settings = []): void
    {
        $message = request('successMessage', $default);

        if ($message !== null) {
            if (request()->isCpRequest()) {
                session()->flash('cp-notification-success', [$message, $settings + [
                    'icon' => 'check',
                    'iconLabel' => t('Success'),
                ]]);
            } else {
                session()->flash('success', $message);
            }
        }
    }

    public static function fail(?string $default = null, array $settings = []): void
    {
        $message = request('failMessage', $default);

        if ($message !== null) {
            if (request()->isCpRequest()) {
                session()->flash('cp-notification-error', [$message, $settings + [
                    'icon' => 'alert',
                    'iconLabel' => t('Error'),
                ]]);
            } else {
                session()->flash('error', $message);
            }
        }
    }
}
