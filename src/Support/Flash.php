<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use Craft;

class Flash
{
    public static function success(?string $default = null, array $settings = []): void
    {
        $message = request('successMessage', $default);

        if ($message !== null) {
            Craft::$app->getSession()->setSuccess($message, $settings);
        }
    }

    public static function fail(?string $default = null, array $settings = []): void
    {
        $message = request('failMessage', $default);

        if ($message !== null) {
            Craft::$app->getSession()->setError($message, $settings);
        }
    }
}
