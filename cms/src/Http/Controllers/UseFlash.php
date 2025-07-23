<?php

namespace CraftCms\Cms\Http\Controllers;

trait UseFlash
{
    public function flashSuccess(?string $default = null, array $settings = []): void
    {
        $message = request('successMessage', $default);

        if ($message !== null) {
            /** @var \craft\web\Application $craft */
            $craft = app('Craft');
            $craft->getSession()->setSuccess($message, $settings);
        }
    }

    public function flashFail(?string $default = null, array $settings = []): void
    {
        $message = request('failMessage', $default);

        if ($message !== null) {
            /** @var \craft\web\Application $craft */
            $craft = app('Craft');
            $craft->getSession()->setError($message, $settings);
        }
    }
}
