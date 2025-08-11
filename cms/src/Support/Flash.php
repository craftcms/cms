<?php

namespace CraftCms\Cms\Support;

/** @since 6.0.0 */
class Flash
{
    public static function success(?string $default = null, array $settings = []): void
    {
        $message = request('successMessage', $default);

        if ($message !== null) {
            /** @var \craft\web\Application $craft */
            $craft = app('Craft');
            $craft->getSession()->setSuccess($message, $settings);
        }
    }

    public static function fail(?string $default = null, array $settings = []): void
    {
        $message = request('failMessage', $default);

        if ($message !== null) {
            /** @var \craft\web\Application $craft */
            $craft = app('Craft');
            $craft->getSession()->setError($message, $settings);
        }
    }
}
