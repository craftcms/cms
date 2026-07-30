<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use function CraftCms\Cms\t;

class Flash
{
    /** @param array<string, mixed> $settings */
    public static function notice(?string $default = null, array $settings = []): ?string
    {
        $message = request('notice', $default);

        if (is_null($message)) {
            return null;
        }

        if (! request()->isCpRequest()) {
            session()->flash('notice', $message);

            return $message;
        }

        session()->flash('cp-notification-notice', [$message, $settings + [
            'icon' => 'info',
            'iconLabel' => t('Notice'),
        ]]);

        return $message;
    }

    /** @param array<string, mixed> $settings */
    public static function success(?string $default = null, array $settings = []): ?string
    {
        $message = request()->getSigned('successMessage', $default);

        if (is_null($message)) {
            return null;
        }

        if (! request()->isCpRequest()) {
            session()->flash('success', $message);

            return $message;
        }

        session()->flash('cp-notification-success', [$message, $settings + [
            'icon' => 'check',
            'iconLabel' => t('Success'),
        ]]);

        return $message;
    }

    /** @param array<string, mixed> $settings */
    public static function error(?string $default = null, array $settings = []): ?string
    {
        $message = request()->getSigned('failMessage', $default);

        if ($message === null) {
            return null;
        }

        if (! request()->isCpRequest()) {
            session()->flash('error', $message);

            return $message;
        }

        session()->flash('cp-notification-error', [$message, $settings + [
            'icon' => 'alert',
            'iconLabel' => t('Error'),
        ]]);

        return $message;
    }

    public static function getNotice(): ?string
    {
        if (request()->isCpRequest()) {
            return session()->get('cp-notification-notice')[0] ?? session()->get('notice');
        }

        return session()->get('notice');
    }

    public static function getSuccess(): ?string
    {
        if (request()->isCpRequest()) {
            return session()->get('cp-notification-success')[0] ?? session()->get('success');
        }

        return session()->get('success');
    }

    public static function getError(): ?string
    {
        if (request()->isCpRequest()) {
            return session()->get('cp-notification-error')[0] ?? session()->get('error');
        }

        return session()->get('error');
    }
}
