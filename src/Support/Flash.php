<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use function CraftCms\Cms\t;

class Flash
{
    public static function notice(?string $default = null, array $settings = []): void
    {
        $message = request('notice', $default);

        if (is_null($message)) {
            return;
        }

        if (! request()->isCpRequest()) {
            session()->flash('notice', $message);

            return;
        }

        session()->flash('cp-notification-notice', [$message, $settings + [
            'icon' => 'info',
            'iconLabel' => t('Notice'),
        ]]);
    }

    public static function success(?string $default = null, array $settings = []): void
    {
        $message = request('successMessage', $default);

        if (is_null($message)) {
            return;
        }

        if (! request()->isCpRequest()) {
            session()->flash('success', $message);

            return;
        }

        session()->flash('cp-notification-success', [$message, $settings + [
            'icon' => 'check',
            'iconLabel' => t('Success'),
        ]]);
    }

    public static function error(?string $default = null, array $settings = []): void
    {
        $message = request('failMessage', $default);

        if ($message === null) {
            return;
        }

        if (! request()->isCpRequest()) {
            session()->flash('error', $message);

            return;
        }

        session()->flash('cp-notification-error', [$message, $settings + [
            'icon' => 'alert',
            'iconLabel' => t('Error'),
        ]]);
    }

    public static function getNotice(): ?string
    {
        if (request()->isCpRequest()) {
            return session()->get('cp-notification-notice')[0] ?? null;
        }

        return session()->get('notice');
    }

    public static function getSuccess(): ?string
    {
        if (request()->isCpRequest()) {
            return session()->get('cp-notification-success')[0] ?? null;
        }

        return session()->get('success');
    }

    public static function getError(): ?string
    {
        if (request()->isCpRequest()) {
            return session()->get('cp-notification-error')[0] ?? null;
        }

        return session()->get('error');
    }
}
